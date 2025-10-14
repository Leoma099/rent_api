<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $user = User::where('username', $request->username)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['error' => 'Invalid credentials'], 401);
        }

        // <-- Block inactive users here
        if ($user->status == 0) {
            return response()->json(['error' => 'Your account is not activated yet.'], 403);
        };


        $token = $user->createToken('eMISO-App-Token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'id' => $user->id,
            'full_name' => optional($user->account)->full_name,
            'id_number' => optional($user->account)->id_number,
            'office_name' => optional($user->account)->office_name,
            'office_address' => optional($user->account)->office_address,
            'mobile_number' => optional($user->account)->mobile_number,
            'position' => optional($user->account)->position,
            'email' => optional($user->account)->email,
            'address' => optional($user->account)->address,
            'username' => $user->username,
            'role' => $user->role,
            'account' => $user->account ? ['id' => $user->account->id] : null, // Avoid null errors
        ], 200);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out successfully'], 200);
    }
};
