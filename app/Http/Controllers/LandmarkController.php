<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Landmark;
use App\Models\Property;
use Illuminate\Support\Facades\Auth;

class LandmarkController extends Controller
{
    public function index(Request $request)
    {
        if (Auth::check()) {
            $user = Auth::user();

            if ($user->role === 1) {
                // Admin sees all landmarks
                $landmarks = Landmark::with('property')->get();
            } elseif ($user->role === 2) {
                // Landlord sees only their landmarks
                $landmarks = Landmark::with('property')
                    ->where('landlord_id', $user->id)
                    ->get();
            } else {
                // Other logged-in users
                $landmarks = Landmark::with('property')->get();
            }
        } else {
            // Guests
            $landmarks = Landmark::with('property')->get();
        }

        return response()->json($landmarks);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'property_id' => 'required|exists:properties,id',
            'name'        => 'required|string|max:255',
            'vicinity'    => 'nullable|string|max:255',
            'distance'    => 'nullable|numeric',
            'lat'         => 'required|numeric',
            'lng'         => 'required|numeric',
            'type'        => 'required|string|max:255', 
        ]);

        $property = Property::findOrFail($validatedData['property_id']);

        // Ensure the logged-in landlord owns this property
        if (Auth::user()->role === 2 && $property->landlord_id != Auth::id()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $landmark = Landmark::create([
            'property_id' => $property->id,
            'landlord_id' => Auth::id(),
            'name'        => $validatedData['name'],
            'vicinity'    => $validatedData['vicinity'],
            'distance'    => $validatedData['distance'],
            'lat'         => $validatedData['lat'],
            'lng'         => $validatedData['lng'],
            'type'        => $validatedData['type']
        ]);

        return response()->json([
            'message'  => 'Landmark created successfully',
            'landmark' => $landmark->load('property')
        ], 201);
    }
}