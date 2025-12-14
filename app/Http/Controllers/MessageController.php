<?php

namespace App\Http\Controllers;

use App\Models\Message;
use Illuminate\Support\Facades\Auth;

class MessageController extends Controller
{
    public function index($userId)
    {
        $currentUser = Auth::user();
        $otherUser = \App\Models\User::findOrFail($userId);

        // Find the conversation shared by these two users
        // We need to look for a conversation that has both participants
        $conversation = \App\Models\Conversation::whereHas('participants', function ($q) use ($currentUser) {
            $q->where('user_id', $currentUser->id);
        })->whereHas('participants', function ($q) use ($otherUser) {
            $q->where('user_id', $otherUser->id);
        })->first();

        if (!$conversation) {
            return response()->json([
                'success' => true,
                'data' => [
                    'messages' => [],
                    'user' => $otherUser
                ]
            ]);
        }

        // Mark messages as seen for current user (reset unread count)
        \App\Models\ConversationParticipant::where('conversation_id', $conversation->id)
            ->where('user_id', $currentUser->id)
            ->update(['unread_count' => 0]);
        
        // Also update individual messages is_seen status (for messages from the other user)
        $conversation->messages()
            ->where('sender_id', '!=', $currentUser->id)
            ->update(['is_seen' => true]);

        $messages = $conversation->messages()
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($msg) use ($currentUser) {
                return [
                    'id' => $msg->id,
                    'sender' => $msg->sender_id, // For frontend compatibility
                    'text' => $msg->message,
                    'time' => $msg->created_at->format('h:i A'),
                    'is_seen' => (bool)$msg->is_seen,
                    'is_me' => $msg->sender_id === $currentUser->id,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $messages // Frontend expects array of messages as 'data' directly or wrapped? Checking old code: 'data' => $messages
        ]);
        // Note: Old code returned 'data' => $messages. But I saw separate 'user' object in my thought. 
        // Let's stick to what frontend expects. Frontend "messages.js" calls "loadMessages" and expects array or object?
        // Looking at messages.js (viewed earlier):
        /*
          if (response.ok) {
            const result = await response.json();
            if (result.success) {
               renderMessages(result.data); // data is the array
            }
          }
        */
        // So just returning messages array is safer.
    }

    public function store(Request $request)
    {
        $request->validate([
            'receiver_id' => 'required|exists:users,id',
            'message' => 'required|string',
        ]);

        $sender = Auth::user();
        $receiverId = $request->receiver_id;
        $text = $request->message;

        // 1. Find or Create Conversation
        $conversation = \App\Models\Conversation::whereHas('participants', function ($q) use ($sender) {
            $q->where('user_id', $sender->id);
        })->whereHas('participants', function ($q) use ($receiverId) {
            $q->where('user_id', $receiverId);
        })->first();

        if (!$conversation) {
            $conversation = \App\Models\Conversation::create([
                'is_support_chat' => false,
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
            'is_seen' => false,
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'sender' => $message->sender_id,
                'text' => $message->message,
                'time' => $message->created_at->format('h:i A'),
            ]
        ]);
    }
}
