<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Handle a login request for an administrator.
     */
    public function adminLogin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Invalid input.'], 422);
        }

        $validated = $validator->validated();

        try {
            $admin = User::where('email', $validated['email'])
                ->where('role', 'admin')
                ->first();

            if (!$admin) {
                return response()->json(['success' => false, 'message' => 'Invalid administrator credentials.'], 401);
            }

            // 1. Check if password matches Plain Text (Legacy Way - Migration)
            if ($validated['password'] === $admin->password) {
                // It matched plain text! Secure it immediately.
                $admin->password = Hash::make($validated['password']);
                $admin->save();
            } 
            // 2. Check if password matches the Hash (Secure Way)
            // We check if it's a valid hash first to avoid "This password does not use the Bcrypt algorithm" error
            elseif (password_get_info($admin->password)['algoName'] !== 'unknown' && Hash::check($validated['password'], $admin->password)) {
                // Password is secure and correct
            }
            // 3. Password is wrong
            else {
                return response()->json(['success' => false, 'message' => 'Invalid administrator credentials.'], 401);
            }

            // Create a token for the admin
            $token = $admin->createToken('admin-token')->plainTextToken;

            return response()->json([
                'success' => true,
                'adminId' => $admin->id,
                'role' => $admin->role,
                'is_master_admin' => $admin->is_master_admin,
                'token' => $token
            ]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'A server error occurred: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Handle a login request for a standard user.
     */
    public function userLogin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Invalid input.'], 422);
        }

        $validated = $validator->validated();

        try {
            $user = User::where('email', $validated['email'])
                ->where('role', 'user')
                ->first();

            if (!$user) {
                return response()->json(['success' => false, 'message' => 'Invalid user credentials.'], 401);
            }

            // 1. Check if password matches Plain Text (Legacy Way - Migration)
            if ($validated['password'] === $user->password) {
                // It matched plain text! Secure it immediately.
                $user->password = Hash::make($validated['password']);
                $user->save();
            } 
            // 2. Check if password matches the Hash (Secure Way)
            // We check if it's a valid hash first to avoid "This password does not use the Bcrypt algorithm" error
            elseif (password_get_info($user->password)['algoName'] !== 'unknown' && Hash::check($validated['password'], $user->password)) {
                // Password is secure and correct
            }
            // 3. Password is wrong
            else {
                return response()->json(['success' => false, 'message' => 'Invalid user credentials.'], 401);
            }

            // Create a token for the user
            $token = $user->createToken('user-token')->plainTextToken;

            return response()->json([
                'success' => true,
                'userId' => $user->id,
                'status' => $user->status,
                'token' => $token
            ]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'A server error occurred: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Handle a registration request for a new user.
     */
    public function register(Request $request)
    {
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'display_name' => $request->first_name . ' ' . $request->last_name,
            'email' => $request->email,
            'password' => Hash::make($request->password), // Always hash new passwords
            'role' => 'user',
            'status' => 'active',
        ]);

        return response()->json([
            'message' => 'User registered successfully',
            'user' => $user
        ], 201);
    }
}