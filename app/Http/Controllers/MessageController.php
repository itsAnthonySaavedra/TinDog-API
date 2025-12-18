<?php

namespace App\Http\Controllers;

use App\Models\Message;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function index($userId)
    {
        try {
            $currentUser = Auth::user();
            
            // Check if we have an authenticated user
            if (!$currentUser) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated - no current user found'
                ], 401);
            }
            
            $otherUser = \App\Models\User::find($userId);
            if (!$otherUser) {
                return response()->json([
                    'success' => false,
                    'message' => 'Target user not found: ' . $userId
                ], 404);
            }

            // Find the conversation shared by these two users
            // We need to look for a conversation that has both participants
            // Optimized Query: Find conversation shared by both users
            // This avoids the slow whereHas nested query
            $conversationId = \Illuminate\Support\Facades\DB::table('conversation_participants')
                ->whereIn('user_id', [$currentUser->id, $otherUser->id])
                ->groupBy('conversation_id')
                ->havingRaw('COUNT(DISTINCT user_id) = 2')
                ->value('conversation_id');

            $conversation = $conversationId ? \App\Models\Conversation::find($conversationId) : null;

            if (!$conversation) {
                return response()->json([
                    'success' => true,
                    'data' => []
                ]);
            }

            // Mark messages as seen for current user (reset unread count)
            try {
                \App\Models\ConversationParticipant::where('conversation_id', $conversation->id)
                    ->where('user_id', $currentUser->id)
                    ->update(['unread_count' => 0]);
            } catch (\Exception $e) {
                // Log but continue - marking as seen is not critical
                \Log::warning("Failed to mark messages as seen: " . $e->getMessage());
            }
            
            // Also update individual messages is_seen status (for messages from the other user)
            try {
                $conversation->messages()
                    ->where('sender_id', '!=', $currentUser->id)
                    ->update(['is_seen' => 'true']);
            } catch (\Exception $e) {
                \Log::warning("Failed to update is_seen: " . $e->getMessage());
            }

            $messages = $conversation->messages()
                ->orderBy('created_at', 'asc')
                ->get()
                ->map(function ($msg) use ($currentUser) {
                    // Safely handle datetime
                    $time = '';
                    if ($msg->created_at) {
                        try {
                            $time = \Carbon\Carbon::parse($msg->created_at)->format('h:i A');
                        } catch (\Exception $e) {
                            $time = (string)$msg->created_at;
                        }
                    }
                    
                    return [
                        'id' => $msg->id,
                        'sender' => $msg->sender_id, // For frontend compatibility
                        'text' => $msg->message,
                        'time' => $time,
                        'is_seen' => (bool)$msg->is_seen,
                        'is_me' => $msg->sender_id === $currentUser->id,
                    ];
                });

            return response()->json([
                'success' => true, // Frontend checks this
                'data' => $messages
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'receiver_id' => 'required|exists:users,id',
                'message' => 'required|string',
            ]);

            $sender = Auth::user();
            $receiverId = $request->receiver_id;
            $text = $request->message;

            // 1. Find or Create Conversation (Optimized)
            $conversationId = \Illuminate\Support\Facades\DB::table('conversation_participants')
                ->whereIn('user_id', [$sender->id, $receiverId])
                ->groupBy('conversation_id')
                ->havingRaw('COUNT(DISTINCT user_id) = 2')
                ->value('conversation_id');

            $conversation = $conversationId ? \App\Models\Conversation::find($conversationId) : null;

            if (!$conversation) {
                $conversation = \App\Models\Conversation::create([
                    'is_support_chat' => 'false',
                    'last_message' => $text,
                    'last_message_at' => now(),
                ]);

                // Add Participants
                \App\Models\ConversationParticipant::create([
                    'conversation_id' => $conversation->id,
                    'user_id' => $sender->id,
                    'unread_count' => 0,
                ]);
                
                \App\Models\ConversationParticipant::create([
                    'conversation_id' => $conversation->id,
                    'user_id' => $receiverId,
                    'unread_count' => 1, // Start with 1 unread
                ]);
            } else {
                // Update Conversation
                $conversation->update([
                    'last_message' => $text,
                    'last_message_at' => now(),
                ]);

                // Increment Unread Count for Receiver
                \App\Models\ConversationParticipant::where('conversation_id', $conversation->id)
                    ->where('user_id', $receiverId)
                    ->increment('unread_count');
            }

            // 2. Create Message
            $message = \App\Models\Message::create([
                'conversation_id' => $conversation->id,
                'sender_id' => $sender->id,
                'message' => $text,
                'is_seen' => 'false',
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'sender' => $message->sender_id,
                    'text' => $message->message,
                    'time' => $message->created_at->format('h:i A'),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ], 500);
        }
    }
}
