<?php

namespace App\Http\Controllers;

use App\Models\Swipe;
use App\Models\UserMatch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SwipeController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'target_user_id' => 'required|exists:users,id',
            'type' => 'required|in:like,nope,super_like',
        ]);

        $user = Auth::user();
        $targetUserId = $request->target_user_id;
        $type = $request->type;

        // Check if already swiped
        $existingSwipe = Swipe::where('user_id', $user->id)
            ->where('target_user_id', $targetUserId)
            ->first();

        if ($existingSwipe) {
            return response()->json([
                'success' => false,
                'message' => 'You have already swiped on this user.',
            ], 409);
        }

        // Record the swipe
        Swipe::create([
            'user_id' => $user->id,
            'target_user_id' => $targetUserId,
            'type' => $type,
        ]);

        $isMatch = false;

        // If it's a like, check for mutual like
        if ($type === 'like' || $type === 'super_like') {
            $mutualSwipe = Swipe::where('user_id', $targetUserId)
                ->where('target_user_id', $user->id)
                ->whereIn('type', ['like', 'super_like'])
                ->first();

            if ($mutualSwipe) {
                $isMatch = true;
                // Create match record
                // Ensure consistent ordering of IDs to avoid duplicates if unique constraint is (min, max)
                // But our migration uses (user_id_1, user_id_2) unique. 
                // Let's just store them. To enforce uniqueness regardless of order, we usually sort them.
                $id1 = min($user->id, $targetUserId);
                $id2 = max($user->id, $targetUserId);

                UserMatch::firstOrCreate([
                    'user_id_1' => $id1,
                    'user_id_2' => $id2,
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'is_match' => $isMatch,
        ]);
    }
}
