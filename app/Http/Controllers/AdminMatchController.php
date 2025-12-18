<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminMatchController extends Controller
{
    public function index()
    {
        // 1. Fetch all matches with user details
        $matches = \App\Models\UserMatch::with(['user1', 'user2'])
            ->orderBy('created_at', 'desc')
            ->get();

        // 2. Format details
        $formattedMatches = $matches->map(function ($match) {
            // Determine active status based on recent messages? 
            // For now, let's assume they are "Active" if they exist.
            
            // We can fetch the latest message for this pair if we want to be fancy, 
            // but for a quick fix, listing the participants is the priority.
            
            return [
                'id' => $match->id,
                'user_1' => [
                    'id' => $match->user1->id,
                    'name' => $match->user1->display_name ?? $match->user1->first_name,
                    'avatar' => $match->user1->dog_avatar ?? 'https://placedog.net/500/500',
                ],
                'user_2' => [
                    'id' => $match->user2->id,
                    'name' => $match->user2->display_name ?? $match->user2->first_name,
                    'avatar' => $match->user2->dog_avatar ?? 'https://placedog.net/500/500',
                ],
                'created_at' => $match->created_at->format('M d, Y'),
                'time_ago' => $match->created_at->diffForHumans(),
                'status' => 'Active', // Placeholder
            ];
        });

        // 3. calculate Stats
        $stats = [
            'total' => $matches->count(),
            'new_this_week' => \App\Models\UserMatch::where('created_at', '>=', now()->subDays(7))->count(),
            'active' => $matches->count(), // Simply total for now
            'inactive' => 0,
            'ended' => 0,
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'matches' => $formattedMatches,
                'stats' => $stats
            ]
        ]);
    }
}
