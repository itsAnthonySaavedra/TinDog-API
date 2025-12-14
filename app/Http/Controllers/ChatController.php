<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\User;

class ChatController extends Controller
{
    /**
     * Get all conversations for the logged-in user.
     * Sorted by most recent messages.
     */
    public function getUserChats()
    {
        $userId = Auth::id();

        $conversations = Conversation::whereHas('participants', function ($q) use ($userId) {
                $q->where('user_id', $userId);
            })
            ->with(['participants'])
            ->orderBy('last_message_at', 'DESC')
            ->get();

        $formatted = $conversations->map(function ($chat) use ($userId) {
            // Get the other participant (User model)
            $other = $chat->participants->where('id', '!=', $userId)->first();

            return [
                'id' => $chat->id,
                'last_message' => $chat->last_message,
                'last_message_at' => $chat->last_message_at,
                'unread_count' => ConversationParticipant::where('conversation_id', $chat->id)
                                    ->where('user_id', $userId)
                                    ->value('unread_count') ?? 0,
                'participant' => $other ? [
                    'id' => $other->id,
                    // prefer display_name if available, fallback to first+last
                    'name' => $other->display_name ?? trim(($other->first_name ?? '') . ' ' . ($other->last_name ?? '')),
                    'email' => $other->email ?? null,
                    'avatar' => $other->owner_avatar ?? null,
                ] : null,
                'is_support_chat' => $chat->is_support_chat,
            ];
        });

        return response()->json($formatted);
    }

    /**
     * Start or get an existing conversation.
     * Prevents duplicate chat threads.
     */
    public function startConversation(Request $request)
    {
        try {
            $request->validate([
                'user_id' => 'required|integer', // the person you want to chat with
            ]);

            $userId = Auth::id();
            $otherId = $request->user_id;

            if ($userId == $otherId) {
                return response()->json(['error' => true, 'message' => 'Cannot start conversation with yourself'], 400);
            }

            // Check if conversation exists
            $existing = Conversation::whereHas('participants', function ($q) use ($userId) {
                    $q->where('user_id', $userId);
                })
                ->whereHas('participants', function ($q) use ($otherId) {
                    $q->where('user_id', $otherId);
                })
                ->first();

            if ($existing) {
                return response()->json(['conversation_id' => $existing->id]);
            }

            // Create new conversation
            $conversation = Conversation::create([
                'is_support_chat' => false,
            ]);

            // Add participants (ensure unread_count default 0)
            ConversationParticipant::create([
                'conversation_id' => $conversation->id,
                'user_id' => $userId,
                'unread_count' => 0
            ]);

            ConversationParticipant::create([
                'conversation_id' => $conversation->id,
                'user_id' => $otherId,
                'unread_count' => 0
            ]);

            return response()->json(['conversation_id' => $conversation->id]);

        } catch (\Exception $e) {
            // Return the error message so the frontend (and your logs) show what went wrong.
            return response()->json([
                'error' => true,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
