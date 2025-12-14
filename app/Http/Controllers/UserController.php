<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Exception;

class UserController extends Controller
{
    /**
     * List all users (Admins & Standard Users)
     */
    public function index(): JsonResponse
    {
        try {
            $users = User::select(
                'id', 'first_name', 'last_name', 'display_name', 
                'email', 'role', 'plan', 'status', 'location', 
                'dog_avatar', 'created_at'
            )->get();

            return response()->json($users);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error retrieving users',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * View a single user
     */
    /**
     * View a single user
     */
    public function show($id): JsonResponse
    {
        try {
            $user = User::find($id);

            if (!$user) {
                return response()->json(['message' => 'User not found'], 404);
            }

            return response()->json([
                'success' => true, 
                'data' => $user
            ]);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error fetching user',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create New User (Admin function)
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $currentUser = $request->user();

            // Permission Check: Only Admins can create users
            if ($currentUser->role !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized: Only Administrators can create accounts.'
                ], 403);
            }

            // Validate
            $validated = $request->validate([
                'role' => 'required|in:user,admin',
                'firstName' => 'required|string|max:255',
                'lastName' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|string|min:8',
                // Optional fields for standard users
                'location' => 'nullable|string|max:255',
                'dogName' => 'nullable|string|max:255',
                'dogBreed' => 'nullable|string|max:255',
                'dogSex' => 'nullable|in:male,female',
                'dogSize' => 'nullable|in:small,medium,large',
                'plan' => 'nullable|in:free,labrador,mastiff',
            ]);

            // Map frontend fields to database columns
            $userData = [
                'first_name' => $validated['firstName'],
                'last_name' => $validated['lastName'],
                'email' => $validated['email'],
                'password' => bcrypt($validated['password']),
                'role' => $validated['role'],
                'display_name' => $validated['firstName'] . ' ' . $validated['lastName'],
                'status' => 'active',
            ];

            if ($validated['role'] === 'user') {
                $userData['location'] = $validated['location'] ?? null;
                $userData['dog_name'] = $validated['dogName'] ?? null;
                $userData['dog_breed'] = $validated['dogBreed'] ?? null;
                $userData['dog_sex'] = $validated['dogSex'] ?? null;
                $userData['dog_size'] = $validated['dogSize'] ?? null;
                $userData['plan'] = $validated['plan'] ?? 'free';
            }

            $user = User::create($userData);

            return response()->json([
                'success' => true,
                'message' => 'User created successfully',
                'userId' => $user->id,
                'data' => $user
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Creation failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update User (Edit)
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $currentUser = $request->user();
            $targetUser = User::findOrFail($id);

            // 1. Permission Check
            if ($targetUser->role === 'admin' && !$currentUser->is_master_admin) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized: Only Master Admins can edit Administrator accounts.'
                ], 403);
            }

            // 2. Validate
            $validated = $request->validate([
                'first_name' => 'sometimes|string|max:255',
                'last_name'  => 'sometimes|string|max:255',
                'display_name' => 'nullable|string|max:255',
                'email'      => 'sometimes|email|unique:users,email,' . $id,
                'status'     => 'sometimes|string|in:active,suspended,banned',
                'location'   => 'nullable|string|max:255',
                // Dog Profile Fields
                'dog_name'   => 'nullable|string|max:255',
                'dog_breed'  => 'nullable|string|max:255',
                'dog_age'    => 'nullable|integer|min:0',
                'dog_sex'    => 'nullable|string|in:male,female',
                'dog_size'   => 'nullable|string|in:small,medium,large',
                'dog_bio'    => 'nullable|string',
                'dog_personalities' => 'nullable|string',
                'dog_avatar' => 'nullable|string',
                'owner_avatar' => 'nullable|string',
                'dog_cover_photo' => 'nullable|string',
                'dog_photos' => 'nullable|array',
                // Discovery Preferences
                'discovery_distance' => 'nullable|integer|min:1|max:100',
                'discovery_age_max' => 'nullable|integer|min:1|max:20',
                'discovery_dog_sex' => 'nullable|in:any,male,female',
                'discovery_dog_size' => 'nullable|in:any,small,medium,large',
                'is_visible' => 'boolean',
                'preferences' => 'nullable|array', // Allow JSON/Array update
            ]);

            // 3. Auto-generate Display Name for Standard Users
            if ($targetUser->role !== 'admin') {
                $firstName = $request->input('first_name', $targetUser->first_name);
                $lastName = $request->input('last_name', $targetUser->last_name);
                $validated['display_name'] = trim($firstName . ' ' . $lastName);
            }

            $targetUser->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'User updated successfully',
                'data' => $targetUser
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Update failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete User
     */
    public function destroy(Request $request, $id): JsonResponse
    {
        try {
            $currentUser = $request->user();
            $targetUser = User::findOrFail($id);

            // Permission Check: Only Master Admins can delete other Admins
            if ($targetUser->role === 'admin' && !$currentUser->is_master_admin) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized: Only Master Admins can delete Administrator accounts.'
                ], 403);
            }

            $targetUser->delete();

            return response()->json([
                'success' => true,
                'message' => 'User deleted successfully'
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Delete failed: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get current user info (Lightweight for Sidebar)
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->load('blockedUsers'); // Load blocked users
        
        return response()->json([
            'success' => true,
            'data' => $user
        ]);
    }

    /**
     * Block a user
     */
    public function block(Request $request, $id)
    {
        try {
            $user = $request->user();
            
            if ($user->id == $id) {
                return response()->json(['message' => 'You cannot block yourself.'], 400);
            }

            $user->blockedUsers()->syncWithoutDetaching([$id]);

            return response()->json([
                'success' => true, 
                'message' => 'User blocked successfully',
                'blocked_users' => $user->blockedUsers
            ]);
        } catch (Exception $e) {
            return response()->json(['message' => 'Block failed', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Unblock a user
     */
    public function unblock(Request $request, $id)
    {
        try {
            $user = $request->user();
            $user->blockedUsers()->detach($id);

            return response()->json([
                'success' => true, 
                'message' => 'User unblocked successfully',
                'blocked_users' => $user->blockedUsers
            ]);
        } catch (Exception $e) {
            return response()->json(['message' => 'Unblock failed', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get blocked users list
     */
    public function blocked(Request $request)
    {
        $user = $request->user();
        return response()->json([
            'success' => true,
            'data' => $user->blockedUsers
        ]);
    }
}