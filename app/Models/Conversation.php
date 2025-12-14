<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Conversation extends Model
{
    protected $fillable = [
        'is_support_chat',
        'last_message',
        'last_message_at'
    ];

    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'conversation_participants')
            ->withPivot('unread_count', 'created_at'); // REMOVE updated_at
    }


    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }
}
