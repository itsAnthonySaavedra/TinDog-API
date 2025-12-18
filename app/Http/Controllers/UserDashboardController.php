<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class UserDashboardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
            }

            // 1. Greeting
            $hour = Carbon::now()->hour;
            if ($hour < 12) {
                $greeting = 'Good Morning!';
            } elseif ($hour < 18) {
                $greeting = 'Good Afternoon!';
            } else {
                $greeting = 'Good Evening!';
            }

            // 2. Stats (Real Data)
            $newMatchesCount = \App\Models\UserMatch::where(function($q) use ($user) {
                    $q->where('user_id_1', $user->id)->orWhere('user_id_2', $user->id);
                })
                ->where('created_at', '>=', Carbon::now()->subDays(7))
                ->count();

            // Unread Messages (Sum of unread_count in participants table)
            $unreadMessagesCount = \App\Models\ConversationParticipant::where('user_id', $user->id)
                ->sum('unread_count');

            // Unread Conversations (Count of participants rows with unread_count > 0)
            $unreadConversationsCount = \App\Models\ConversationParticipant::where('user_id', $user->id)
                ->where('unread_count', '>', 0)
                ->count();
                
            $stats = [
                'newMatches' => $newMatchesCount,
                'unreadMessages' => (int)$unreadMessagesCount,
                'unreadConversations' => $unreadConversationsCount,
                'profileViews' => $user->profile_views ?? 0, 
                'currentPlan' => ucfirst($user->plan ?? 'free'),
            ];

            // 3. Pups Nearby (Safeguarded)
            // 3. Pups Nearby (Safeguarded)
            // Treat 0.0 as invalid/null (default in some DB setups)
            $lat = (float)$user->latitude;
            $lng = (float)$user->longitude;
            
            $currentUserLatitude = ($lat != 0.0) ? $lat : 10.3157;
            $currentUserLongitude = ($lng != 0.0) ? $lng : 123.8854;

            // Haversine Formula for distance (in kilometers)
            // Handle nulls/zeros in DB by coalescing to 0 to prevent SQL math errors if any
            // And use NULLIF to treat 0 as null in the calculation comparisons if needed, 
            // but effectively we just want to ensure we don't return crazy distances for people at 0,0
            $rawSql = "(6371 * acos(least(1.0, greatest(-1.0, cos(radians(?)) * cos(radians(COALESCE(NULLIF(latitude, 0), 10.3157))) * cos(radians(COALESCE(NULLIF(longitude, 0), 123.8854)) - radians(?)) + sin(radians(?)) * sin(radians(COALESCE(NULLIF(latitude, 0), 10.3157)))))))";

            // OPTIMIZED: Combine all exclusion queries into ONE using UNION
            // This reduces 3 DB round-trips to 1
            $excludedIds = DB::table('swipes')
                ->where('user_id', $user->id)
                ->select('target_user_id as excluded_id')
                ->union(
                    DB::table('matches')
                        ->where('user_id_1', $user->id)
                        ->select('user_id_2 as excluded_id')
                )
                ->union(
                    DB::table('matches')
                        ->where('user_id_2', $user->id)
                        ->select('user_id_1 as excluded_id')
                )
                ->pluck('excluded_id')
                ->push($user->id)
                ->unique()
                ->toArray();

            // OPTIMIZED: Select only needed columns (not SELECT *)
            $pupsNearby = User::select(['id', 'dog_name', 'display_name', 'dog_avatar', 'role', 'latitude', 'longitude'])
                ->selectRaw("{$rawSql} as distance_km", [$currentUserLatitude, $currentUserLongitude, $currentUserLatitude])
                ->whereNotIn('id', $excludedIds)
                ->where('role', 'user')
                ->orderBy('distance_km', 'asc')
                ->take(3)
                ->get()
                ->map(function ($pup) {
                    return [
                        'id' => $pup->id,
                        'name' => $pup->dog_name ?? $pup->display_name ?? 'Unknown',
                        'avatar' => $pup->dog_avatar, 
                        'distance' => (isset($pup->distance_km) && !is_null($pup->distance_km)) ? number_format($pup->distance_km, 1) . ' km away' : 'Unknown',
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
                        'avatar' => $user->owner_avatar,
                        'dog_avatar' => $user->dog_avatar,
                        'plan' => ucfirst($user->plan ?? 'free'),
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
                        'dog_photos' => $user->dog_photos,
                    ],
                    'greeting' => $greeting,
                    'stats' => $stats,
                    'pupsNearby' => $pupsNearby,
                    'tip' => $tip,
                ]
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Dashboard Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Server Error',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
