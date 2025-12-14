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

        $currentUserLatitude = $user->latitude ?? 10.3157; // Default to Cebu
        $currentUserLongitude = $user->longitude ?? 123.8854;

        // Preferences (Default if not set in DB)
        $maxDistance = $user->discovery_distance ?? 100;
        $maxAge = $user->discovery_age_max ?? 20;
        $prefSex = $user->discovery_dog_sex ?? 'any';
        $prefSize = $user->discovery_dog_size ?? 'any';

        // Haversine Formula for distance (in kilometers)
        // 6371 = Earth radius in km
        $rawSql = "(6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude))))";

        $query = User::select('*')
            ->selectRaw("{$rawSql} as distance_km", [$currentUserLatitude, $currentUserLongitude, $currentUserLatitude])
            ->whereNotIn('id', $swipedUserIds)
            ->where('role', 'user')
            // Filter by Distance
            ->having('distance_km', '<=', $maxDistance)
            // Filter by Age
            ->where('dog_age', '<=', $maxAge);

        // Filter by Sex
        if ($prefSex !== 'any' && !empty($prefSex)) {
            $query->where('dog_sex', $prefSex);
        }

        // Filter by Size
        if ($prefSize !== 'any' && !empty($prefSize)) {
            $query->where('dog_size', $prefSize);
        }

        $candidates = $query->orderBy('distance_km', 'asc') // Show nearest first
            ->limit(20)
            ->get()
            ->map(function ($candidate) {
                return [
                    'id' => $candidate->id,
                    'name' => $candidate->display_name ?? $candidate->first_name,
                    'age' => $candidate->dog_age,
                    'breed' => $candidate->dog_breed,
                    'distance' => number_format($candidate->distance_km, 1) . ' km',
                    'bio' => $candidate->dog_bio,
                    'avatar' => $candidate->dog_avatar,
                    'personalities' => $candidate->dog_personalities ? explode(',', $candidate->dog_personalities) : [],
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $candidates
        ]);
    }
}
