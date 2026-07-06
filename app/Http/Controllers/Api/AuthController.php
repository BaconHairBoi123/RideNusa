<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'phone_number' => 'required|string|max:20',
            'address' => 'required|string',
            'verification_type' => 'required|in:sim,course',
            'license_photo' => 'required_if:verification_type,sim|image|max:4096',
            'face_photo' => 'required_if:verification_type,sim|image|max:4096',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'username' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone_number' => $request->phone_number,
            'address' => $request->address,
            'verification_status' => 'unverified',
        ]);

        // Handle Verification Data
        $verificationData = [
            'user_id' => $user->id,
            'verification_type' => $request->verification_type,
            'verification_date' => now(),
        ];

        if ($request->verification_type === 'sim') {
            if ($request->hasFile('face_photo')) {
                $path = $request->file('face_photo')->store('verifications/faces', 'public');
                $verificationData['face_photo_path'] = 'storage/' . $path;
            }
            if ($request->hasFile('license_photo')) {
                $path = $request->file('license_photo')->store('verifications/licenses', 'public');
                $verificationData['license_photo_path'] = 'storage/' . $path;
            }
            $verificationData['status'] = 'pending';
        } else {
            $verificationData['status'] = 'class_required';
        }

        \Illuminate\Support\Facades\DB::table('user_verifications')->insert($verificationData);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => 'User registered successfully',
            'data' => [
                'user' => $user,
                'access_token' => $token,
                'token_type' => 'Bearer',
            ]
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'login' => 'required|string', // Bisa berupa email atau username
            'password' => 'required|string',
        ]);

        $loginType = filter_var($request->login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        $user = User::where($loginType, $request->login)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid credentials'
            ], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => 'Login successful',
            'data' => [
                'user' => $user,
                'access_token' => $token,
                'token_type' => 'Bearer',
            ]
        ]);
    }

    public function forgotPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|exists:users,email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Email is not registered.',
                'errors' => $validator->errors()
            ], 422);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Password reset link has been sent to your email.'
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Logged out successfully'
        ]);
    }

    public function profile(Request $request)
    {
        return response()->json([
            'status' => 'success',
            'data' => $request->user()
        ]);
    }

    public function updateVerification(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'license_photo' => 'required|image|max:4096',
            'face_photo' => 'required|image|max:4096',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();

        // Handle Verification Data
        $verificationData = [
            'user_id' => $user->id,
            'verification_type' => 'sim',
            'verification_date' => now(),
            'status' => 'pending',
        ];

        if ($request->hasFile('face_photo')) {
            $path = $request->file('face_photo')->store('verifications/faces', 'public');
            $verificationData['face_photo_path'] = 'storage/' . $path;
        }
        if ($request->hasFile('license_photo')) {
            $path = $request->file('license_photo')->store('verifications/licenses', 'public');
            $verificationData['license_photo_path'] = 'storage/' . $path;
        }

        // Check if verification record already exists
        $existing = \Illuminate\Support\Facades\DB::table('user_verifications')
            ->where('user_id', $user->id)
            ->first();

        if ($existing) {
            \Illuminate\Support\Facades\DB::table('user_verifications')
                ->where('user_id', $user->id)
                ->update($verificationData);
        } else {
            \Illuminate\Support\Facades\DB::table('user_verifications')->insert($verificationData);
        }

        // Set user verification status back to unverified
        $user->update(['verification_status' => 'unverified']);

        return response()->json([
            'status' => 'success',
            'message' => 'Verification data updated successfully. Please wait for admin approval.',
        ]);
    }
}
