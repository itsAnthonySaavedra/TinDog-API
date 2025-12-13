<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\ConversationParticipant;

Broadcast::channel('conversation.{id}', function ($user, $id) {
    return ConversationParticipant::where('conversation_id', $id)
        ->where('user_id', $user->id)
        ->exists();
});


