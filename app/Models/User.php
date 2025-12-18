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
     *
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
        'instance_id',
        // Discovery Preferences
        'discovery_distance',
        'discovery_age_max',
        'discovery_dog_sex',
        'discovery_dog_size',
        'is_visible',
        'preferences', // JSON column
        'plan_status',
        'next_billing_date',
    ];

    /**
     * Users I have blocked.
     */
    public function blockedUsers()
    {
        return $this->belongsToMany(User::class, 'blocked_users', 'user_id', 'blocked_user_id')
                    ->withTimestamps();
    }

    /**
     * My Invoices.
     */
    public function invoices()
    {
        return $this->hasMany(Invoice::class)->orderBy('created_at', 'desc');
    }

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'preferences' => 'array',
            'dog_photos' => 'array',
            'dog_personalities' => 'array',
            'is_visible' => 'boolean',
        ];
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function messagesSent()
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    // public function messagesReceived()
    // {
    //     // DEPRECATED: Schema changed to conversations
    //     return $this->hasMany(Message::class, 'receiver_id');
    // }
}