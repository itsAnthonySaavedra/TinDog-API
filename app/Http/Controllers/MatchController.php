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
                // Determine which user is the "other" user
                $otherUser = $match->user_id_1 == $user->id ? $match->user2 : $match->user1;

                return [
                    'id' => $match->id,
                    'user' => [
                        'id' => $otherUser->id,
                        'name' => $otherUser->display_name ?? $otherUser->first_name,
                        'avatar' => $otherUser->dog_avatar ?? 'https://placedog.net/500/500',
                        'last_message' => 'Say hello!', // Placeholder for Phase 3
                        'unread_count' => 0, // Placeholder for Phase 3
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
