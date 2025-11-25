<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Lease;
use Illuminate\Support\Facades\Auth;
use App\Notifications\SystemNotifications;

class LeaseController extends Controller
{
    // List all leases
    public function index(Request $request)
    {
        $query = Lease::with(['property', 'tenant.account'])
            ->where('landlord_id', Auth::id());

        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                // Search tenant full name
                $q->whereHas('tenant.account', function ($tenantQuery) use ($search) {
                    $tenantQuery->where('full_name', 'like', "%{$search}%");
                })
                // Or search property title
                ->orWhereHas('property', function ($propertyQuery) use ($search) {
                    $propertyQuery->where('title', 'like', "%{$search}%");
                });
            });
        }

        $leases = $query->latest()->get();

        // Auto-update status for all leases before returning
        // $leases->each->autoUpdateStatus();

        return response()->json($leases);
    }

    // Create a new lease (Pending)
    public function store(Request $request)
    {
        $request->validate([
            'property_id' => 'required|exists:properties,id',
            'tenant_id' => 'required|exists:users,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
        ]);

        $lease = Lease::create([
            'property_id' => $request->property_id,
            'landlord_id' => Auth::id(),
            'tenant_id' => $request->tenant_id,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'status' => 0, // Pending
        ]);

        // Notify landlord immediately
        Auth::user()->notify(new SystemNotifications(
            'New Lease Created',
            "You added a new lease for {$lease->tenant->name} in {$lease->property->title}."
        ));

        return response()->json($lease);
    }

    // Manually update status (Pending → Active)
    public function updateStatus(Request $request, $id)
    {
        $lease = Lease::where('landlord_id', Auth::id())
            ->with(['tenant', 'property'])
            ->findOrFail($id);

        // Only allow updating Pending to Active manually
        if ($lease->status === 0) {
            $lease->status = 1; // Active
            $lease->save();

            // Send notification to tenant
            $lease->sendStatusNotification();

            // Automatically update property status to Rented (2)
            if ($lease->property) {
                $lease->property->propertyStats = 2; // Rented
                $lease->property->save();
            }
        }

        return response()->json(['message' => 'Lease status updated successfully', 'lease' => $lease]);
    }

    public function destroy($id)
    {
        $lease = Lease::where('landlord_id', Auth::id())->findOrFail($id);

        // Update property status to AVAILABLE again (2)
        $property = $lease->property;
        if ($property) {
            $property->propertyStats = 1;
            $property->save();
        }

        // Delete the lease
        $lease->delete();

        return response()->json(['message' => 'Lease deleted successfully']);
    }


    // Total leases
    public function totalLease()
    {
        $totalLease = Lease::count();
        return response()->json(['total_leases' => $totalLease]);
    }
}
