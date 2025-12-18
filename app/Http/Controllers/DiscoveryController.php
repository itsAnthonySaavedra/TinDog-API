<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Swipe;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DiscoveryController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        // OPTIMIZED: Combine all exclusion queries into ONE using UNION
        // This reduces 3 DB round-trips to 1
        $allExcludedIds = DB::table('swipes')
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
        
        \Illuminate\Support\Facades\Log::info("Discovery for User {$user->id}. Excluding: " . implode(',', $allExcludedIds));

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

        // OPTIMIZED: Select only needed columns (not SELECT *)
        // This dramatically reduces data transfer over the network
        $query = User::select([
                'id', 'display_name', 'first_name', 
                'dog_age', 'dog_breed', 'dog_bio', 'dog_avatar', 'dog_personalities',
                'dog_sex', 'dog_size', 'role', 'latitude', 'longitude'
            ])
            ->selectRaw("{$rawSql} as distance_km", [$currentUserLatitude, $currentUserLongitude, $currentUserLatitude])
            ->whereNotIn('id', $allExcludedIds)
            ->where('role', 'user')
            // Filter by Distance
            // ->having('distance_km', '<=', $maxDistance) // Valid, but lenient for demo
            
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
