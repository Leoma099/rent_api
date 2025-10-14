<?php

namespace App\Http\Controllers;

use App\Models\PropertyImage;
use App\Models\Property;
use Illuminate\Http\Request;

class PropertyImageController extends Controller
{
    public function index(Request $request)
    {
        $property = Property::with('images')->findOrFail($propertyId);
        return response()->json($property->images);
    }

    public function store($propertyId)
    {
        $request->validate([
            'photos.*' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        $property = Property::findOrFail($propertyId);

        $savedImages = [];

        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $photo) {
                $path = $photo->store('uploads/properties', 'public');

                $savedImages[] = PropertyImage::create([
                    'property_id' => $property->id,
                    'photo' => $path
                ]);
            }
        }

        return response()->json([
            'message' => 'Images uploaded successfully',
            'images' => $savedImages
        ], 201);
    }
}
