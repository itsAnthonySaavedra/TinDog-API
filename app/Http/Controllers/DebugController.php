<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class DebugController extends Controller
{
    /**
     * Debug endpoint to measure actual query performance
     * Access via: GET /api/debug/performance
     */
    public function performance(Request $request)
    {
        $results = [];
        
        // Test 1: Simple user count
        $start = microtime(true);
        $count = User::where('role', 'user')->count();
        $results['user_count'] = [
            'time_ms' => round((microtime(true) - $start) * 1000, 2),
            'result' => $count
        ];

        // Get all user IDs for debugging
        $results['user_ids'] = User::pluck('id')->toArray();

        // Test 2: Get 5 users with SELECT * (SLOW - fetches all columns including TEXT)
        $start = microtime(true);
        $users = User::where('role', 'user')->take(5)->get();
        $results['select_all_users'] = [
            'time_ms' => round((microtime(true) - $start) * 1000, 2),
            'result' => count($users),
            'note' => 'SELECT * - includes large TEXT columns'
        ];

        // Test 3: Get 5 users with specific columns (FAST - only needed columns)
        $start = microtime(true);
        $users2 = User::select(['id', 'display_name', 'dog_avatar', 'role'])
            ->where('role', 'user')
            ->take(5)
            ->get();
        $results['select_specific_users'] = [
            'time_ms' => round((microtime(true) - $start) * 1000, 2),
            'result' => count($users2),
            'note' => 'SELECT specific columns - much faster'
        ];

        // Test 3: Swipes query
        $start = microtime(true);
        $swipes = DB::table('swipes')->take(10)->get();
        $results['get_10_swipes'] = [
            'time_ms' => round((microtime(true) - $start) * 1000, 2),
            'result' => count($swipes)
        ];

        // Test 4: Matches query
        $start = microtime(true);
        $matches = DB::table('matches')->take(10)->get();
        $results['get_10_matches'] = [
            'time_ms' => round((microtime(true) - $start) * 1000, 2),
            'result' => count($matches)
        ];

        // Test 5: Conversation participants table
        $start = microtime(true);
        $participants = DB::table('conversation_participants')->count();
        $results['conversation_participants'] = [
            'time_ms' => round((microtime(true) - $start) * 1000, 2),
            'result' => $participants
        ];

        // Test 6: Personal access tokens (Sanctum)
        $start = microtime(true);
        $tokens = DB::table('personal_access_tokens')->count();
        $results['tokens_count'] = [
            'time_ms' => round((microtime(true) - $start) * 1000, 2),
            'result' => $tokens
        ];

        // Calculate total
        $totalTime = 0;
        foreach ($results as $key => $value) {
            if (isset($value['time_ms'])) {
                $totalTime += $value['time_ms'];
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Performance test results - Each query takes ~100-1000ms due to remote Supabase',
            'data' => $results,
            'total_time_ms' => $totalTime,
            'analysis' => 'Users table queries (~1000ms) are slower than other tables (~100ms). This is expected for remote DBs. 40+ second page loads suggest 40+ queries per page.',
        ]);
    }

    /**
     * Debug endpoint to test messages functionality
     * Access via: GET /api/debug/messages/:userId/:otherUserId
     */
    public function testMessages($userId, $otherUserId)
    {
        try {
            $currentUser = User::findOrFail($userId);
            $otherUser = User::findOrFail($otherUserId);

            // Test the conversation query
            $conversationId = DB::table('conversation_participants')
                ->whereIn('user_id', [$currentUser->id, $otherUser->id])
                ->groupBy('conversation_id')
                ->havingRaw('COUNT(DISTINCT user_id) = 2')
                ->value('conversation_id');

            $conversation = $conversationId ? \App\Models\Conversation::find($conversationId) : null;

            $messages = [];
            if ($conversation) {
                $messages = $conversation->messages()
                    ->orderBy('created_at', 'asc')
                    ->take(10)
                    ->get()
                    ->toArray();
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'currentUser' => $currentUser->only(['id', 'display_name']),
                    'otherUser' => $otherUser->only(['id', 'display_name']),
                    'conversationId' => $conversationId,
                    'conversationFound' => $conversation !== null,
                    'messageCount' => count($messages),
                    'messages' => $messages,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ], 500);
        }
    }
}
