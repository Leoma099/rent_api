<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\BookingSchedule;
use App\Notifications\SystemNotifications;

class BookingScheduleController extends Controller
{
    /**
     * List bookings depending on role
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        if ($user->role == 2)
        {
            $query = BookingSchedule::with(['tenant.account', 'property', 'schedule'])
                ->where('landlord_id', $user->id);

            if ($request->filled('search')) {
                $search = $request->search;

                $query->where(function ($q) use ($search) {
                    $q->whereHas('tenant.account', function ($tenantQuery) use ($search) {
                        $tenantQuery->where('full_name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('property', function ($propertyQuery) use ($search) {
                        $propertyQuery->where('title', 'like', "%{$search}%")
                                    ->orWhere('property_type', 'like', "%{$search}%");
                    });
                });
            }

            $bookings = $query->latest()->get();
        }
        else
        {
            $bookings = BookingSchedule::with(['landlord', 'property'])
                ->where('tenant_id', $user->id)
                ->latest()
                ->get();
        }

        return response()->json($bookings);
    }

    /**
     * Tenant books a schedule
     */
    public function store(Request $request)
    {
        $request->validate([
            'property_id' => 'required|exists:properties,id',
            'landlord_id' => 'required|exists:users,id',
            'schedule_id' => 'required|exists:schedules,id',
        ]);

        $tenantId = auth()->id();

        // ✅ Check if this schedule is already booked (pending or approved)
        $alreadyBooked = BookingSchedule::where('schedule_id', $request->schedule_id)
            ->whereIn('status', [0, 1]) // pending or approved
            ->exists();

        if ($alreadyBooked) {
            return response()->json([
                'message' => 'This schedule has already been booked.'
            ], 409); // Conflict
        }

        // ✅ Create booking
        $booking = BookingSchedule::create([
            'property_id' => $request->property_id,
            'landlord_id' => $request->landlord_id,
            'tenant_id'   => $tenantId,
            'schedule_id' => $request->schedule_id,
            'status'      => 0, // pending
        ]);

        $booking->load(['tenant.account', 'landlord.account', 'property']);

        $tenantName   = $booking->tenant->account->full_name ?? $booking->tenant->username;
        $propertyName = $booking->property->title ?? 'a property';

        /**
         * 📩 Notifications
         */
        // Notify landlord
        $booking->landlord->notify(new SystemNotifications(
            "Booking Schedule",
            "{$tenantName} has booked a schedule to visit your property <strong>{$propertyName}</strong>"
        ));

        // Notify tenant
        $booking->tenant->notify(new SystemNotifications(
            "Booking Schedule",
            "You have successfully booked a visit in <strong>{$propertyName}</strong>"
        ));

        return response()->json([
            'message' => 'Booking created successfully',
            'data'    => $booking->load(['tenant', 'landlord', 'schedule'])
        ]);
    }

    /**
     * Landlord updates status of booking
     */
        /**
     * Landlord updates status of booking
     */
    public function updateStatus(Request $request, $id)
    {
        $booking = BookingSchedule::findOrFail($id);

        $request->validate([
            'status' => 'required|in:0,1,2,3'
        ]);

        $booking->update([
            'status' => $request->status
        ]);

        $booking->load(['tenant.account', 'landlord.account', 'property']);

        $tenantName   = $booking->tenant->account->full_name ?? $booking->tenant->username;
        $propertyName = $booking->property->title ?? 'a property';

        /**
         * 📩 Notifications
         */

        // Notify landlord (confirmation)
        $booking->landlord->notify(new SystemNotifications(
            "Booking Status",
            "You have updated the status of <strong>{$tenantName}</strong>"
        ));

        // Notify tenant depending on the new status
        if ($request->status == 1) {
            // ✅ Approved
            $booking->tenant->notify(new SystemNotifications(
                "Booking Status",
                "Your booking status has been approved to visit <strong>{$propertyName}</strong>"
            ));
        } elseif ($request->status == 0 || $request->status == 2 || $request->status == 3) {
            // ❌ Pending, rejected, or canceled
            $booking->tenant->notify(new SystemNotifications(
                "Booking Status",
                "Your booking status hasn't been approved or deleted to visit <strong>{$propertyName}</strong>"
            ));
        }

        return response()->json($booking->load(['tenant', 'landlord', 'property']));
    }

    /**
     * Landlord deletes a booking
     */
    public function destroy($id)
    {
        $booking = BookingSchedule::where('landlord_id', Auth::id())->findOrFail($id);
        $booking->load(['tenant.account', 'landlord.account', 'property']);

        $propertyName = $booking->property->title ?? 'a property';

        /**
         * 📩 Notify tenant only if booking was NOT approved
         */
        if ($booking->status != 1) {
            $booking->tenant->notify(new SystemNotifications(
                "Booking Status",
                "Your booking status hasn't been approved or deleted to visit <strong>{$propertyName}</strong>"
            ));
        }

        $booking->delete();

        return response()->json([
            'message' => 'Booking Schedule deleted successfully'
        ]);
    }

}