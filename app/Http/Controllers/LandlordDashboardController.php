<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Property;
use App\Models\BookingSchedule;
use App\Models\Inquiry;
use Illuminate\Support\Facades\Auth;

class LandlordDashboardController extends Controller
{
    public function totalPending()
    {
        $pendingProperties = Property::where('landlord_id', Auth::id())
            ->where('status', 0) // or ->where('status', 'pending')
            ->count();

        $pendingBooked = BookingSchedule::where('landlord_id', Auth::id())
            ->where('status', 0) // or ->where('status', 'pending')
            ->count();

        $totalPending = $pendingProperties + $pendingBooked;

        return response()->json(['total_pending' => $totalPending]);
    }

    public function totalBooked()
    {
        $totalBooked = BookingSchedule::count();

        return response()->json(['total_booked' => $totalBooked]);
    }

    public function totalInquire()
    {
        $totalInquire = Inquiry::count();

        return response()->json(['total_inquire'=> $totalInquire]);
    }
}
