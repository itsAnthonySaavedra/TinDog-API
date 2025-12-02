<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Swipe;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DiscoveryController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        // Get IDs of users already swiped on by the current user
        $swipedUserIds = Swipe::where('user_id', $user->id)->pluck('target_user_id')->toArray();

        // Add current user's ID to exclude list
        $swipedUserIds[] = $user->id;

        // Fetch potential matches (users not in the exclusion list)
        // You might want to add filters here (location, age, etc.) later
        $candidates = User::whereNotIn('id', $swipedUserIds)
            ->where('role', 'user') // Ensure we only show regular users
            ->inRandomOrder()
            ->limit(10) // Limit to 10 at a time for performance
            ->get()
            ->map(function ($candidate) {
                return [
                    'id' => $candidate->id,
                    'name' => $candidate->display_name ?? $candidate->first_name,
                    'age' => $candidate->dog_age,
                    'breed' => $candidate->dog_breed,
                    'distance' => '5 km', // Placeholder for now, requires geolocation logic
                    'bio' => $candidate->dog_bio,
                    'avatar' => $candidate->dog_avatar, // Let frontend handle default
                    'personalities' => $candidate->dog_personalities ? explode(',', $candidate->dog_personalities) : [],
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $candidates
        ]);
    }
}
