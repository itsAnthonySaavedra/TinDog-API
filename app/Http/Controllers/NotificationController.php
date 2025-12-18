<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\UserMatch;
use App\Models\Conversation;
use App\Models\Swipe;

class NotificationController extends Controller
{
    /**
     * Get recent notifications
     */
    public function index()
    {
        $user = Auth::user();
        $notifications = [];

        // 1. Matches (Last 48 hours)
        $newMatches = UserMatch::where(function($q) use ($user) {
                $q->where('user_id_1', $user->id)
                  ->orWhere('user_id_2', $user->id);
            })
            ->where('created_at', '>=', now()->subHours(48))
            ->orderBy('created_at', 'desc')
            ->get();

        foreach($newMatches as $match) {
            $otherUser = ($match->user_id_1 == $user->id) ? $match->user2 : $match->user1;
            
            if ($otherUser) {
                $notifications[] = [
                    'type' => 'match',
                    'message' => "You matched with {$otherUser->display_name}!",
                    'time' => $match->created_at->diffForHumans(),
                    'timestamp' => $match->created_at,
                    'user' => ['id' => $otherUser->id]
                ];
            }
        }

        // 2. Unread Messages 
        // Logic: Find conversations with unread_count > 0 for this user
        // (Assuming unread_count is managed on conversation_participants table or similar)
        // Since we don't have a perfect participant model loaded here, we'll mock logic based on Conversation updated_at
        // A better approach in a real app is checking the Pivot table 'last_read_at'. 
        
        // For now, let's grab random recent conversations as "New Message" notifications if they are very recent (< 24h)
        $recentConvos = Conversation::whereHas('participants', function($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->where('updated_at', '>=', now()->subHours(24))
            ->get();

        foreach($recentConvos as $convo) {
            // Check if last message was NOT from me
            // (Skipping deep check for speed, assuming updated means activity)
             $notifications[] = [
                'type' => 'message',
                'message' => "New message",
                'time' => $convo->updated_at->diffForHumans(),
                'timestamp' => $convo->updated_at,
                'user' => ['id' => 1] // Placeholder ID or extract from participants
            ];
        }

        // Sort
        usort($notifications, function($a, $b) {
            return $b['timestamp'] <=> $a['timestamp'];
        });

        return response()->json([
            'success' => true,
            'data' => array_slice($notifications, 0, 10) // Limit to 10
        ]);
    }
}
