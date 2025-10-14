<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NearbyController extends Controller
{
    public function index(Request $request)
    {
        $lat = $request->query('lat');
        $lng = $request->query('lng');
        $type = $request->query('type'); // school, restaurant, etc.

        // Example: query your own DB of POIs
        $places = \DB::table('points_of_interest')
            ->where('type', $type)
            ->get();

        // Compute distance for each place
        $places = $places->map(function ($place) use ($lat, $lng) {
            $distance = $this->haversine($lat, $lng, $place->lat, $place->lng);
            return [
                'id' => $place->id,
                'name' => $place->name,
                'lat' => $place->lat,
                'lng' => $place->lng,
                'distance' => $distance
            ];
        })->sortBy('distance')->values();

        return response()->json($places);
    }

    // Haversine formula in km
    private function haversine($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat/2) * sin($dLat/2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon/2) * sin($dLon/2);
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        return $earthRadius * $c;
    }
}
