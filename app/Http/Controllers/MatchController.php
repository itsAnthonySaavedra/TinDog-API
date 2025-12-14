<?php

namespace App\Http\Controllers;

use App\Models\UserMatch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MatchController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // Find matches where the current user is either user_id_1 or user_id_2
        $matches = UserMatch::where('user_id_1', $user->id)
            ->orWhere('user_id_2', $user->id)
            ->with(['user1', 'user2'])
            ->get()
            ->map(function ($match) use ($user) {
                $otherUser = $match->user_id_1 == $user->id ? $match->user2 : $match->user1;

                // Find Conversation
                $conversation = \App\Models\Conversation::whereHas('participants', function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                })->whereHas('participants', function ($q) use ($otherUser) {
                    $q->where('user_id', $otherUser->id);
                })->with('participants')->first();

                $lastMessageText = 'Say hello!';
                $lastMessageTime = null;
                $unreadCount = 0;

                if ($conversation) {
                    $lastMessageText = $conversation->last_message ?? 'Start chatting';
                    $lastMessageTime = $conversation->last_message_at ? $conversation->last_message_at->diffForHumans() : null;
                    
                    // Get Unread Count for Current User
                    $participant = $conversation->participants->where('user_id', $user->id)->first();
                    $unreadCount = $participant ? $participant->unread_count : 0;
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
            });

        return response()->json([
            'success' => true,
            'data' => $matches
        ]);
    }
}
