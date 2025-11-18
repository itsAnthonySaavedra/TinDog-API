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
}