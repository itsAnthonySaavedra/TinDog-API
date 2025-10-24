<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

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
            $admin = DB::table('users')
                ->where('email', $validated['email'])
                ->where('role', 'admin')
                ->first();

            if (!$admin || $admin->password !== $validated['password']) {
                return response()->json(['success' => false, 'message' => 'Invalid administrator credentials.'], 401);
            }

            return response()->json([
                'success' => true,
                'adminId' => $admin->id
            ]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'A server error occurred.'], 500);
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
            $user = DB::table('users')
                ->where('email', $validated['email'])
                ->where('role', 'user')
                ->first();

            if (!$user || $user->password !== $validated['password']) {
                return response()->json(['success' => false, 'message' => 'Invalid user credentials.'], 401);
            }

            return response()->json([
                'success' => true,
                'userId' => $user->id,
                'status' => $user->status,
            ]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'A server error occurred.'], 500);
        }
    }
}