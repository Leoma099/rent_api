<?php

namespace App\Http\Controllers\API\Administration;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Account;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Notifications\SystemNotifications;

class AccountController extends Controller
{
    public function index(Request $request) // Make sure to include Request $request
    {
        $query = Account::with('user');
    
        if ($request->has('search')) {
            $search = $request->search;
    
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'LIKE', "%$search%");
            });
        }
    
        $accounts = $query->paginate($request->limit ?? 10000);
    
        return response()->json($accounts);
    }
    
    public function store(Request $request)
    {
        // Validate incoming request data
        $request->validate([
            'full_name' => 'required',
            'email' => 'required|email|unique:accounts',
            'mobile_number' => 'required',
            'username' => 'required|unique:users',
            'password' => 'required',
        ]);

         // Create the user first
         $user = User::create([
            'username' => $request->username,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'status' => $request->status,
        ]);

        // Create the account and link it to the user
        $account = Account::create([
            'user_id' => $user->id,
            'full_name' => $request->full_name,
            'email' => $request->email,
            'mobile_number' => $request->mobile_number,
        ]);

        return response()->json([
            'message' => 'Account and user created successfully',
            'account' => $account,
        ]);
    }

    public function register(Request $request)
    {
        // Validate incoming request data
        $request->validate([
            'full_name' => 'required',
            'email' => 'required|email|unique:accounts',
            'mobile_number' => 'required',
            'username' => 'required|unique:users',
            'password' => 'required',
        ]);

         // Create the user first
         $user = User::create([
            'username' => $request->username,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'status' => $request->status,
        ]);

        // Create the account and link it to the user
        $account = Account::create([
            'user_id' => $user->id,
            'full_name' => $request->full_name,
            'email' => $request->email,
            'mobile_number' => $request->mobile_number,
        ]);

        return response()->json([
            'message' => 'Account and user created successfully',
            'account' => $account,
        ]);
    }

    public function show($id)
    {
        $account = Account::find($id);

        if (!$account) {
            return response()->json(['error' => 'Account not found'], 404);
        }

        return response()->json($account);
    }

    public function update(Request $request, $id)
    {
        // Find the account first
        $account = Account::findOrFail($id);

        // Get the related user record
        $user = $account->user; // or User::find($account->user_id);

        // Update the user data
        if ($user) {
            $user->username = $request->username;
            $user->role = $request->role;
            $user->status = $request->status;

            // Only update password if provided
            if ($request->filled('password')) {
                $user->password = Hash::make($request->password);
            }

            $user->save();
        }

        // Update the account data
        $account->update([
            'full_name'     => $request->full_name,
            'email'         => $request->email,
            'mobile_number' => $request->mobile_number,
            // no need to update 'user_id' unless you’re reassigning it
        ]);

        return response()->json(['message' => 'Account updated successfully']);
    }

    public function updateStatus(Request $request, $id)
    {
        $account = Account::with('user')->findOrFail($id);

        $request->validate([
            'status' => 'required|in:0,1'
        ]);

        // ✅ Update the linked user's status
        $account->user->update([
            'status' => $request->status
        ]);

        // ✅ Get the admin performing the action
        $admin = auth()->user();

        // ✅ Prepare readable data
        $userName   = $account->full_name ?? $account->user->name ?? 'Unknown User';
        $statusText = $request->status == 1 ? 'Active' : 'Inactive';

        // ✅ Notify the admin themself
        $admin->notify(new SystemNotifications(
            'User Status Updated',
            "You updated the status of {$userName} to {$statusText}."
        ));

        // ✅ Return fresh updated data
        return response()->json([
            'message' => 'Status updated successfully',
            'user'    => $userName,
            'status'  => $statusText,
            'account' => $account->load('user')
        ]);
    }

    public function destroy($id)
    {
        $account = Account::findOrFail($id);

        // ✅ Delete related user (login)
        if ($account->user) {

            // ✅ Check if the user is a landlord (role = 2)
            if ($account->user->role == 2) {

                foreach ($account->properties as $property) {

                    // ✅ These lines deleted — since photos are part of properties table
                    // $property->photos()->delete();

                    // ✅ Delete related property data only
                    $property->landmarks()->delete();
                    $property->bookings()->delete();
                    $property->schedules()->delete();
                    $property->inquiries()->delete();
                    $property->leases()->delete();

                    // ✅ Finally delete the property itself
                    $property->delete();
                }
            }

            // ✅ Delete the user record
            $account->user->delete();
        }

        // ✅ Delete the account itself
        $account->delete();

        return response()->json([
            'message' => 'Account and all related data deleted successfully.'
        ]);
    }


    public function clientDataInfo()
    {
        $client = Account::with('user')
        ->whereHas('user', function ($query) {
            $query->where('role', 2);
        })
        ->get();

        return response()->json($client);
    }

}
