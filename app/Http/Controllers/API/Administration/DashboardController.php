<?php

namespace App\Http\Controllers\API\Administration;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Auth;
use App\Models\Property;
use App\Notifications\SystemNotifications;
use Illuminate\Support\Facades\Mail;
use App\Mail\PropertySuggestionMail;
use App\Models\User;

class DashboardController extends Controller
{
    public function propertyOptions()
    {
        $user = Auth::user();

        if ($user && $user->role === 1) {
            $properties = Property::with('landlord.account', 'schedules', 'landmarks')
                ->latest()
                ->get();

            return response()->json(['properties' => $properties]);
        }

        return response()->json(['message' => 'Unauthorized access.'], 403);
    }

    public function totalProperties()
    {
        if (!Auth::check()) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        if (Auth::user()->role === 1)
        { // Admin
            $totalProperties = Property::count(); // all properties
        }
        elseif (Auth::user()->role === 2)
        { // Landlord
            $totalProperties = Property::where('landlord_id', Auth::id())->count(); // only their own
        }
        else
        { // Tenant or others
            $totalProperties = Property::where('status', 1)->count(); // only approved properties
        }

        return response()->json([
            'total_properties' => $totalProperties
        ]);
    }

    public function pendingProperty()
    {
        $totalPendingProperty = Property::where('status', 0)->count();

        return response()->json([
            'total_pending_properties' => $totalPendingProperty
        ]);
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
