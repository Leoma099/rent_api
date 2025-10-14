<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index()
    {
        return response()->json(User::with('account')->get());
    }

    public function store(Request $request)
    {
        $request->validate([
            'username' => 'required|unique:users',
            'password' => 'required|min:6',
        ]);

        $user = User::create([
            'username' => $request->username,
            'password' => Hash::make($request->password),
        ]);

        return response()->json($user);
    }

    public function show($id)
    {
        $user = User::with('account')->find($id);
        return response()->json($user);
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $user->update([
            'username' => $request->username,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'status' => $request->status
        ]);

        return response()->json(['message' => 'User updated successfully']);
    }

    public function destroy(User $user)
    {
        $user->delete();
        return response()->json(['message' => 'User deleted successfully']);
    }

    public function landlordCount()
    {
        $totalLandlord = User::where('role', 2)->count();

        return response()->json([
            'total_landlords' => $totalLandlord
        ]);
    }

    public function tenantCount()
    {
        $totalTenant = User::where('role', 3)->count();

        return response()->json([
            'total_tenants' => $totalTenant
        ]);
    }

    public function pendingCount()
    {
        $totalPending = User::where('status', 0)->count();

        return response()->json([
            'total_user_pending' => $totalPending
        ]);
    }

}