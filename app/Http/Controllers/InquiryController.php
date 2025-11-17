<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Inquiry;
use App\Models\Property;
use App\Notifications\SystemNotifications;

class InquiryController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = null;

        // 🔹 If user is a landlord (role = 2)
        if ($user->role === 2) {
            $query = $user->receivedInquiries()
                ->with('tenant.account', 'property', 'messages.sender')
                ->latest();

            if ($request->filled('search')) {
                $search = $request->search;
                $query->whereHas('tenant.account', function ($tenantQuery) use ($search) {
                    $tenantQuery->where('full_name', 'like', "%{$search}%");
                });
            }
        }

        // 🔹 If user is a tenant (role = 3)
        elseif ($user->role === 3) {
            $query = $user->sentInquiries()
                ->with('landlord.account', 'property', 'messages.sender')
                ->latest();

            if ($request->filled('search')) {
                $search = $request->search;
                $query->whereHas('landlord.account', function ($landlordQuery) use ($search) {
                    $landlordQuery->where('full_name', 'like', "%{$search}%");
                });
            }
        }

        // Handle users with no inquiries or invalid role
        if (!$query) {
            return response()->json(['message' => 'Invalid role or no inquiries found'], 403);
        }

        return response()->json($query->get());
    }

    public function store(Request $request)
    {
        $request->validate([
            'property_id' => 'required|exists:properties,id',
            'landlord_id' => 'required|exists:users,id',
            'message'     => 'required|string',
        ]);

        // Create or get existing inquiry
        $inquiry = Inquiry::firstOrCreate(
            [
                'property_id' => $request->property_id,
                'tenant_id'   => Auth::id(),
                'landlord_id' => $request->landlord_id,
            ],
            ['status' => 0] // Pending
        );

        // First message
        $message = $inquiry->messages()->create([
            'sender_id' => Auth::id(),
            'message'   => $request->message,
        ]);

        /**
         * 🔔 Notification for FIRST inquiry only
         */
        $inquiry->load(['tenant.account', 'landlord.account', 'property']);

        $tenant       = $inquiry->tenant;
        $landlord     = $inquiry->landlord;
        $propertyName = $inquiry->property->title ?? 'a property';
        $tenantName   = $tenant->account->full_name ?? $tenant->username;

        // Tenant sends first inquiry → Notify landlord
        $landlord->notify(new SystemNotifications(
            "Inquiry Property",
            "{$tenantName} is interested in {$propertyName}",
            "inquiry",
            $inquiry->id
        ));

        $tenant->notify(new SystemNotifications(
            "Inquiry Property",
            "You sent inquiry for {$propertyName}",
            "inquiry",
            $inquiry->id
        ));

        return response()->json(
            $inquiry->load(['property','tenant','landlord','messages.sender'])
        );
    }


}