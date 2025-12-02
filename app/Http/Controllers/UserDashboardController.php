<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\User;
use Carbon\Carbon;

class UserDashboardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        // 1. Greeting
        $hour = Carbon::now()->hour;
        if ($hour < 12) {
            $greeting = 'Good Morning!';
        } elseif ($hour < 18) {
            $greeting = 'Good Afternoon!';
        } else {
            $greeting = 'Good Evening!';
        }

        // 2. Stats (Placeholders for now until tables exist)
        $stats = [
            'newMatches' => 0,
            'unreadMessages' => 0,
            'unreadConversations' => 0,
            'profileViews' => 0, // We could implement a simple counter on User model later
            'currentPlan' => ucfirst($user->plan ?? 'free'),
        ];

        // 3. Pups Nearby (Random other users)
        // 3. Pups Nearby (Real users only)
        $pupsNearby = User::where('id', '!=', $user->id)
            ->where('role', 'user')
            ->whereNotNull('dog_name') // Only show users who have set up their profile
            ->inRandomOrder()
            ->take(3)
            ->get()
            ->map(function ($pup) {
                return [
                    'id' => $pup->id,
                    'name' => $pup->dog_name ?? $pup->display_name,
                    'avatar' => $pup->dog_avatar, 
                    'distance' => rand(1, 10) . ' km away', // Mock distance is fine for now
                ];
            });

        // 4. Tip of the Day
        $tips = [
            "Engage your dog's mind with a 'sniffari,' allowing them to explore scents at their own pace.",
            "Rotate your dog's toys weekly to keep them exciting.",
            "Check your dog's paws for cracks or cuts after walks.",
            "Use puzzle feeders to turn feeding time into a fun challenge.",
            "Reinforce basic commands like 'sit' and 'stay' in short, fun sessions.",
            "Always check before sharing human food; some items like grapes are toxic.",
            "Regular brushing with dog-specific toothpaste prevents dental disease.",
            "Tailor exercise to your dog's breed and age.",
            "A sudden increase in thirst can be a sign of health issues.",
            "Create a designated 'safe space' for your dog to retreat to.",
        ];
        $tip = $tips[array_rand($tips)];

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'name' => $user->display_name ?? $user->first_name . ' ' . $user->last_name,
                    'avatar' => $user->owner_avatar, // Main avatar should be Owner, not Dog
                    'dog_avatar' => $user->dog_avatar, // Explicitly return for profile loader
                    'plan' => ucfirst($user->plan ?? 'free'),
                    // Profile Data
                    'dog_name' => $user->dog_name,
                    'dog_breed' => $user->dog_breed,
                    'dog_sex' => $user->dog_sex,
                    'dog_size' => $user->dog_size,
                    'dog_age' => $user->dog_age,
                    'dog_bio' => $user->dog_bio,
                    'dog_personalities' => $user->dog_personalities,
                    'location' => $user->location,
                    'owner_bio' => $user->owner_bio,
                    'owner_avatar' => $user->owner_avatar,
                    'dog_cover_photo' => $user->dog_cover_photo,
                    'dog_photos' => $user->dog_photos, // Add Gallery Photos
                ],
                'greeting' => $greeting,
                'stats' => $stats,
                'pupsNearby' => $pupsNearby,
                'tip' => $tip,
            ]
        ]);
    }
}
