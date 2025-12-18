<?php

namespace App\Http\Controllers;

use App\Models\UserMatch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class MatchController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        
        // Cache key unique to this user
        $cacheKey = "matches_user_{$user->id}";
        
        // Cache for 2 minutes to reduce DB load
        return Cache::store('file')->remember($cacheKey, 120, function () use ($user) {
            
            // OPTIMIZED: Single query to get matches with only needed user columns
            $matches = DB::table('matches')
                ->where('user_id_1', $user->id)
                ->orWhere('user_id_2', $user->id)
                ->get();

            if ($matches->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'data' => []
                ]);
            }

            // Get all other user IDs
            $otherUserIds = $matches->map(function ($match) use ($user) {
                return $match->user_id_1 == $user->id ? $match->user_id_2 : $match->user_id_1;
            })->unique()->toArray();

            // OPTIMIZED: Fetch only essential user columns in ONE query
            $users = DB::table('users')
                ->whereIn('id', $otherUserIds)
                ->select(['id', 'display_name', 'first_name', 'dog_avatar'])
                ->get()
                ->keyBy('id');

            // OPTIMIZED: Get conversation data in ONE query
            $conversations = DB::table('conversation_participants as cp1')
                ->join('conversation_participants as cp2', 'cp1.conversation_id', '=', 'cp2.conversation_id')
                ->join('conversations as c', 'c.id', '=', 'cp1.conversation_id')
                ->where('cp1.user_id', $user->id)
                ->whereIn('cp2.user_id', $otherUserIds)
                ->where('cp2.user_id', '!=', $user->id)
                ->select([
                    'cp2.user_id as other_user_id',
                    'c.last_message',
                    'c.last_message_at',
                    'cp1.unread_count'
                ])
                ->get()
                ->keyBy('other_user_id');

            // Build result
            $result = $matches->map(function ($match) use ($user, $users, $conversations) {
                $otherUserId = $match->user_id_1 == $user->id ? $match->user_id_2 : $match->user_id_1;
                $otherUser = $users[$otherUserId] ?? null;
                
                if (!$otherUser) return null;

                $conversation = $conversations[$otherUserId] ?? null;
                
                $lastMessageText = $conversation ? ($conversation->last_message ?? 'Start chatting') : 'Say hello!';
                $lastMessageTime = null;
                $unreadCount = 0;

                if ($conversation && $conversation->last_message_at) {
                    try {
                        $lastMessageTime = \Carbon\Carbon::parse($conversation->last_message_at)->diffForHumans();
                    } catch (\Exception $e) {
                        $lastMessageTime = null;
                    }
                    $unreadCount = $conversation->unread_count ?? 0;
                }

                return [
                    'id' => $match->id,
                    'user' => [
                        'id' => $otherUser->id,
                        'name' => $otherUser->display_name ?? $otherUser->first_name,
                        'avatar' => $otherUser->dog_avatar ?? 'https://placedog.net/500/500',
                        'last_message' => $lastMessageText,
                        'last_message_time' => $lastMessageTime,
                        'unread_count' => $unreadCount,
                    ],
                    'created_at' => $match->created_at,
                ];
            })->filter()->values();

            return response()->json([
                'success' => true,
                'data' => $result
            ]);
        });
    }
}

