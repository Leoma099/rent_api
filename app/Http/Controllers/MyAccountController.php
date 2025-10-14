<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class MyAccountController extends Controller
{
    public function update(Request $request)
    {
        $user = Auth::user();

        // Update user basic info
        $user->username = $request->username;

        // Update password if filled
        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        $user->save();

        // Update account info (assuming User hasOne Account)
        if ($user->account) {
            $user->account->update([
                'id_number' => $request->id_number,
                'full_name' => $request->full_name,
                'office_name' => $request->office_name,
                'office_address' => $request->office_address,
                'position' => $request->position,
                'address' => $request->address,
                'email' => $request->email,
                'mobile_number' => $request->mobile_number,
            ]);
        }

        return response()->json(['message' => 'Account updated successfully']);
    }
}
