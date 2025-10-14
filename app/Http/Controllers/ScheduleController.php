<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Schedule;
use Illuminate\Support\Facades\Auth;

class ScheduleController extends Controller
{
    public function index(Request $request)
    {
        if (Auth::check()) {
            $user = Auth::user();

            if ($user->role === 1) {
                // Admin sees all schedules
                $schedules = Schedule::with('property', 'landlord')->get();
            } elseif ($user->role === 2) {
                // Landlord sees only their schedules
                $schedules = Schedule::with('property', 'landlord')
                    ->where('landlord_id', $user->id)
                    ->get();
            } else {
                // Other logged-in users
                $schedules = Schedule::with('property')->get();
            }
        } else {
            // Guests
            $schedules = Schedule::with('property')->get();
        }

        return response()->json($schedules);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'property_id'   => 'required|exists:properties,id',
            'available_day' => 'required|string',
            'start_time'    => 'required|date_format:H:i',
            'end_time'      => 'required|date_format:H:i|after:start_time',
        ]);

        $property = Property::findOrFail($validatedData['property_id']);

         // Ensure the logged-in landlord owns this property
        if (Auth::user()->role !== 2 || $property->landlord_id != Auth::id())
        {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $schedule = Schedule::create([
            'property_id'   => $property->id,
            'landlord_id'   => Auth::id(),
            'available_day' => $validatedData['available_day'],
            'start_time'    => $validatedData['start_time'],
            'end_time'      => $validatedData['end_time'],
        ]);

        return response()->json([
            'message' => 'Schedule created successfully',
            'schedule' => $schedule->load('property', 'landlord')
        ], 201);
    }
}
