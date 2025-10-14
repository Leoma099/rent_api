<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SavedProperty;
use App\Models\Property;
use Illuminate\Support\Facades\Auth;
use App\Notifications\SystemNotifications;

class SavedPropertyController extends Controller
{
    public function index()
    {
        return SavedProperty::with('property')
            ->where('tenant_id', auth()->id())
            ->get();
    }

    public function store(Request $request)
    {
        $saved = SavedProperty::firstOrCreate([
            'tenant_id'   => auth()->id(),
            'property_id' => $request->property_id,
        ]);

        // ✅ Load the property title
        $property = Property::find($request->property_id);

        if ($property) {
            Auth::user()->notify(new SystemNotifications(
                'Property Saved',
                "You have saved {$property->title} to your list."
            ));
        }

        return response()->json($saved, 201);
    }

    public function destroy($id)
    {
        $saved = SavedProperty::where('tenant_id', auth()->id())
            ->where('property_id', $id)
            ->first();

        return response()->json(['message' => 'Removed from saved']);
    }
}