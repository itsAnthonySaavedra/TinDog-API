<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\Conversation;
use App\Models\ConversationParticipant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

// Your Events
use App\Events\MessageSentEvent;
use App\Events\MessageSeenEvent;
use App\Events\TypingEvent;

class MessageController extends Controller
{
    public function getMessages($conversationId)
    {
        $messages = Message::where('conversation_id', $conversationId)
            ->orderBy('created_at', 'ASC')
            ->with('sender:id,first_name,last_name,display_name,owner_avatar')
            ->get();

        return response()->json($messages);
    }

    public function sendMessage(Request $request, $conversationId)
    {
        $request->validate([
            'message' => 'required|string'
        ]);

        $senderId = Auth::id();

        // Save message
        $msg = Message::create([
            'conversation_id' => $conversationId,
            'sender_id' => $senderId,
            'message' => $request->message,
        ]);

        // Update chat list preview
        Conversation::where('id', $conversationId)->update([
            'last_message' => $request->message,
            'last_message_at' => now(),
        ]);

        // Mark unread for the recipient
        ConversationParticipant::where('conversation_id', $conversationId)
            ->where('user_id', '!=', $senderId)
            ->increment('unread_count');

        // 🔥 Broadcast to the other user
        broadcast(new MessageSentEvent($msg))->toOthers();

        return response()->json([
            'success' => true,
            'message' => $msg
        ]);
    }

    public function sendImage(Request $request, $conversationId)
    {
        $request->validate([
            'image_url' => 'required|string'
        ]);

        $senderId = Auth::id();

        $msg = Message::create([
            'conversation_id' => $conversationId,
            'sender_id' => $senderId,
            'image_url' => $request->image_url,
        ]);

        Conversation::where('id', $conversationId)->update([
            'last_message' => '[Image]',
            'last_message_at' => now(),
        ]);

        ConversationParticipant::where('conversation_id', $conversationId)
            ->where('user_id', '!=', $senderId)
            ->increment('unread_count');

        // 🔥 Broadcast image message
        broadcast(new MessageSentEvent($msg))->toOthers();

        return response()->json([
            'success' => true,
            'message' => $msg
        ]);
    }

    public function markAsSeen($conversationId)
    {
        $userId = Auth::id();

        Message::where('conversation_id', $conversationId)
            ->where('sender_id', '!=', $userId)
            ->update(['is_seen' => true]);

        ConversationParticipant::where('conversation_id', $conversationId)
            ->where('user_id', $userId)
            ->update(['unread_count' => 0]);

        // 🔥 Broadcast seen event
        broadcast(new MessageSeenEvent($conversationId, $userId))->toOthers();

        return response()->json(['success' => true]);
    }

    public function typing($conversationId)
    {
        broadcast(new TypingEvent($conversationId, Auth::id()))->toOthers();

        return response()->json(['typing' => true]);
    }
}