<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     * Added some field that must be field -kirk
     * @var list<string>
     */
    protected $fillable = [
    'first_name',
    'last_name',
    'display_name',
    'email',
    'password',
    'role',
    'status',
    'is_master_admin',
    'plan',
    'location',
    'owner_bio',
    'signup_date',
    'last_seen',
    'dog_name',
    'dog_breed',
    'dog_age',
    'dog_sex',
    'dog_size',
    'dog_bio',
    'dog_avatar',
    'dog_cover_photo',
    'owner_avatar',
    'dog_photos',
    'dog_personalities',
    'instance_id'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function conversations()
    {
        return $this->belongsToMany(Conversation::class, 'conversation_participants', 'user_id', 'conversation_id')
                    ->withPivot('unread_count')
                    ->withTimestamps();
    }

    public function messages()
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

}