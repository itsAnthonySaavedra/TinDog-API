<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;

class MessageSentEvent implements ShouldBroadcast
{
    use InteractsWithSockets, SerializesModels;

    public $message;
    public $conversationId;

    public function __construct(Message $message)
    {
        // Load sender to return a complete message object
        $this->message = $message->load('sender');
        $this->conversationId = $message->conversation_id;
    }

    public function broadcastOn()
    {
        return new PrivateChannel("conversation.{$this->conversationId}");
    }

    // IMPORTANT: consistent event name for the frontend
    public function broadcastAs()
    {
        return 'MessageSentEvent';
    }
}
