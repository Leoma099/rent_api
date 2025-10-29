<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Mail\ResetPasswordMail;
use App\Models\User;
use App\Models\Account;

class ForgotPasswordController extends Controller
{
    // ✅ Send reset link
    public function sendResetLink(Request $request)
    {
        $request->validate([
            'email' => 'required|email'
        ]);

        $account = Account::where('email', $request->email)->first();

        if (!$account) {
            return response()->json(['message' => 'Email not found.'], 404);
        }

        $user = User::find($account->user_id);

        if (!$user) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        // ✅ Generate a plain token
        $token = Str::random(60);

        // ✅ Store token (plain) so we can match it later
        DB::table('password_resets')->updateOrInsert(
            ['email' => $account->email],
            [
                'token' => $token,
                'created_at' => now(),
            ]
        );

        // ✅ Send reset email
        Mail::to($account->email)->send(new ResetPasswordMail($token, $account->email));

        return response()->json(['message' => 'Reset link sent. Check your Mailtrap inbox.'], 200);
    }

    // ✅ Reset password
    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'token' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed', // at least 8 characters
                'regex:/[A-Z]/', // must contain uppercase
                'regex:/[a-z]/', // must contain lowercase
                'regex:/[0-9]/', // must contain a number
                'regex:/[@$!%*?&]/', // must contain special character
            ],
        ]);

        $account = Account::where('email', $request->email)->first();

        if (!$account) {
            return response()->json(['message' => 'Email not found.'], 404);
        }

        $reset = DB::table('password_resets')
            ->where('email', $request->email)
            ->first();

        // ✅ Simple token check
        if (!$reset || $reset->token !== $request->token) {
            return response()->json(['message' => 'Invalid or expired token.'], 400);
        }

        // ✅ Update password
        $user = User::find($account->user_id);
        $user->password = Hash::make($request->password);
        $user->save();

        // ✅ Delete token
        DB::table('password_resets')->where('email', $request->email)->delete();

        return response()->json(['message' => 'Password reset successfully.'], 200);
    }
}
