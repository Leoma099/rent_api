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

class PropertyController extends Controller
{
    public function index(Request $request)
    {
        if (Auth::check())
        {
            // User is logged in
            if (Auth::user()->role === 1)
            {
                $query = Property::with('landlord.account', 'schedules', 'landmarks');

                if ($request->filled('search')) {
                    $search = $request->search;

                    $query->where(function ($q) use ($search) {
                        $q->where('title', 'like', "%{$search}%");
                    });
                }

                $properties = $query->latest()->get();
            }
            else if (Auth::user()->role === 2)
            {
                $query = Property::with('landlord.account', 'schedules', 'landmarks')
                    ->where('landlord_id', Auth::id());

                if ($request->filled('search')) {
                    $search = $request->search;

                    $query->where(function ($q) use ($search) {
                        $q->where('title', 'like', "%{$search}%");
                    });
                }

                $properties = $query->latest()->get();
            }

            else if (Auth::user()->role === 3) // Tenant
            {
                $query = Property::with('landlord.account', 'schedules', 'landmarks')
                    ->where('status', 2);

                // ✅ Auto filter by property_type
                if ($request->has('property_type') && $request->property_type != '')
                {
                    $query->where('property_type', $request->property_type);
                }

                // ✅ Filter by address OR property title
                if ($request->has('search') && $request->search != '')
                {
                    $search = $request->search;
                    $query->where(function($q) use ($search)
                    {
                        $q->where('address', 'like', '%' . $search . '%')
                        ->orWhere('title', 'like', '%' . $search . '%');
                    });
                }

                $properties = $query->latest()->get();
            }
            else
            {
                $properties = Property::with('landlord.account', 'schedules', 'landmarks')
                    ->where('status', 2)
                    ->latest()
                    ->get();
            }
        }
        else
        {
            // Guest (not logged in)
            $query = Property::with('landlord.account', 'schedules', 'landmarks')
                ->where('status', 2);

            // ✅ Add search filter for guests
            if ($request->has('search') && $request->search != '')
            {
                $search = $request->search;
                $query->where(function($q) use ($search)
                {
                    $q->where('address', 'like', '%' . $search . '%')
                    ->orWhere('title', 'like', '%' . $search . '%');
                });
            }

            // ✅ Add property_type filter for guests
            if ($request->has('property_type') && $request->property_type != '')
            {
                $query->where('property_type', $request->property_type);
            }

            $properties = $query->latest()->get();
        }

        return response()->json($properties);
    }

    public function store(Request $request)
    {
        // Only landlords can create properties
        if (Auth::user()->role != 2) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        // Decode schedules JSON string to array
        if ($request->has('schedules')) {
            $request->merge([
                'schedules' => json_decode($request->input('schedules'), true)
            ]);
        }

        // Decode landmarks JSON string to array
        if ($request->has('landmarks')) {
            $request->merge([
                'landmarks' => json_decode($request->input('landmarks'), true)
            ]);
        }

        // ✅ Validate the request including the photo file
        $validatedData = $request->validate([
            'title'             => 'required|string|max:255',
            'description'       => 'nullable|string',
            'address'           => 'nullable|string|max:255',
            'lat'               => 'nullable|numeric',
            'lng'               => 'nullable|numeric',
            'price'             => 'nullable|numeric',
            'property_type'     => 'nullable|string',
            'photo_1'           => 'nullable',
            'photo_2'           => 'nullable',
            'photo_3'           => 'nullable',
            'photo_4'           => 'nullable',
            'floor_plan'        => 'nullable',
            'size'              => 'nullable|numeric',
            'schedules'         => 'nullable|array',
            'schedules.*.available_day' => 'required_with:schedules|string',
            'schedules.*.start_time'    => 'required_with:schedules|date_format:H:i',
            'schedules.*.end_time'      => 'required_with:schedules|date_format:H:i|after:schedules.*.start_time',
            'landmarks'         => 'nullable|array',
            'landmarks.*.name' => 'required_with:landmarks|string|max:255',
            'landmarks.*.vicinity' => 'nullable|string|max:255',
            'landmarks.*.distance' => 'nullable|numeric',
            'landmarks.*.lat'   => 'nullable|numeric',
            'landmarks.*.lng'   => 'nullable|numeric',
        ]);

        // ✅ Handle photo upload
        $photoPath = null;
        $floorPlanPath = null;

        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('uploads/photos', 'public');
        }

        if ($request->hasFile('floor_plan')) {
            $floorPlanPath = $request->file('floor_plan')->store('uploads/photos', 'public');
        }

        $property = Property::create([
            'landlord_id'   => Auth::id(),
            'title'         => $validatedData['title'],
            'description'   => $validatedData['description'] ?? null,
            'address'       => $validatedData['address'] ?? null,
            'lat'           => $validatedData['lat'] ?? null,
            'lng'           => $validatedData['lng'] ?? null,
            'price'         => $validatedData['price'] ?? null,
            'property_type' => $validatedData['property_type'] ?? null,
            'photo'         => $photoPath,
            'floor_plan'    => $floorPlanPath,
            'status'        => 0, // default inactive
            'size'          => $validatedData['size'] ?? null,
            'is_featured'   => 0,
            'propertyStats' => 1,
        ]);

        // ✅ notify landlord (current user)
        $landlord = Auth::user();
        $landlord->notify(new SystemNotifications(
            'New Property Added',
            'You have added new property listing: ' . $property->title
        ));

        // ✅ notify admin (find first admin)
        $admin = \App\Models\User::where('role', 1)->first();
        if ($admin)
        {
            $admin->notify(new SystemNotifications(
                'New Property Added',
                $landlord->account->full_name . 'has added a new property listing:' . $property->title
            ));
        }

            // Create schedules if any
        if (!empty($validatedData['schedules']))
        {
            foreach ($validatedData['schedules'] as $sched)
            {
                $property->schedules()->create([
                    'landlord_id' => Auth::id(),
                    'available_day' => $sched['available_day'],
                    'start_time' => $sched['start_time'],
                    'end_time' => $sched['end_time'],
                ]);
            }
        }

        if (!empty($validatedData['landmarks'])) {
            foreach ($validatedData['landmarks'] as $landmark) {
                $property->landmarks()->create([
                    'landlord_id' => Auth::id(),
                    'name' => $landmark['name'],
                    'vicinity' => $landmark['vicinity'] ?? null,
                    'distance' => $landmark['distance'] ?? null,
                    'lat' => $landmark['lat'] ?? null,
                    'lng' => $landmark['lng'] ?? null,
                ]);
            }
        }

        
        // ✅ Send property suggestion email to all tenants
        try 
        {   
            $tenants = User::where('role', 3)->with('account')->get();
            foreach ($tenants as $tenant) {
                Mail::to($tenant->account->email)
                    ->queue(new PropertySuggestionMail($property));
            }
        }
        catch (\Exception $e) 
        {
            \Log::error('Failed to send property suggestion email: ' . $e->getMessage());
        }

        return response()->json(['message' => 'Property record successfully created', 'property' => $property->load('schedules')], 201);
    }

    public function show($id)
    {
        $property = Property::with([
            'landlord.account',
            'schedules.bookings',
            'landmarks'
        ])->findOrFail($id);

        // add `is_booked` attribute to each schedule
        $property->schedules->each(function($schedule) {
            $schedule->is_booked = $schedule->bookings()
                ->whereIn('status', [1, 2])
                ->exists();
        });

        // ✅ Add full URLs for photo & floor_plan
        if ($property->photo) {
            $property->photo = asset('storage/' . $property->photo);
        }
        if ($property->floor_plan) {
            $property->floor_plan = asset('storage/' . $property->floor_plan);
        }

        return response()->json($property);
    }

    public function update(Request $request, $id)
    {
        $property = Property::findOrFail($id);

        // Handle photo
        if ($request->hasFile('photo'))
        {
            $property->photo = $request->file('photo')->store('uploads/photos', 'public');
        }

        // Handle floor plan
        if ($request->hasFile('floor_plan'))
        {
            $property->floor_plan = $request->file('floor_plan')->store('uploads/photos', 'public');
        }

        $property->update([
            'title'         =>  $request->title,
            'description'   =>  $request->description,
            'address'       =>  $request->address,
            'lat'           =>  $request->lat,
            'lng'           =>  $request->lng,
            'price'         =>  $request->price,
            'property_type' =>  $request->property_type,
            'size'          =>  $request->size,
            'status'        =>  $request->status,
            'propertyStats'=>  $request->propertyStats,
            'is_featured'   =>  $request->is_featured,
        ]);

        // ✅ Handle schedules
        if ($request->has('schedules'))
        {
            $schedules = json_decode($request->input('schedules'), true);
            $property->schedules()->delete(); // remove old schedules

            foreach ($schedules as $sched)
            {
                $property->schedules()->create([
                    'landlord_id'   => $property->landlord_id,
                    'available_day' => $sched['available_day'] ?? null,
                    'start_time'    => $sched['start_time'] ?? null,
                    'end_time'      => $sched['end_time'] ?? null,
                ]);
            }
        }

        // ✅ Handle landmarks
        if ($request->has('landmarks'))
        {
            $landmarks = json_decode($request->input('landmarks'), true);
            $property->landmarks()->delete(); // remove old landmarks

            foreach ($landmarks as $lm)
            {
                $property->landmarks()->create([
                    'landlord_id' => $property->landlord_id,
                    'name'        => $lm['name'] ?? null,
                    'vicinity'    => $lm['vicinity'] ?? null,
                    'distance'    => $lm['distance'] ?? null,
                    'lat'         => $lm['lat'] ?? null,
                    'lng'         => $lm['lng'] ?? null,
                ]);
            }
        }

        $user = Auth::user();
        if ($user->role === 2) {
            // Notify landlord themselves
            $user->notify(new \App\Notifications\SystemNotifications(
                'Property Updated',
                'You have updated your property: ' . $property->title
            ));
        }

        return response()->json([
            'message'  => 'Property updated successfully',
            'property' => $property->load('schedules', 'landmarks')
        ]);
    }

    public function destroy($id)
    {
        $property = Property::findOrFail($id);

        // Store property title before deleting
        $propertyName = $property->title;

        // Delete related data
        $property->schedules()->each(function($schedule) {
            $schedule->bookings()->delete(); // delete bookings first
        });
        $property->schedules()->delete();
        $property->inquiries()->delete();
        $property->leases()->delete();
        $property->landmarks()->delete(); // ✅ delete all related landmarks

        // Delete the property itself
        $property->delete();

        // Notify landlord
        $landlord = $property->landlord;
        if ($landlord) {
            $landlord->notify(new SystemNotifications(
                'Property Deleted',
                'Admin deleted your property: ' . $propertyName
            ));
        }

        // Notify admin
        $admin = Auth::user();
        if ($admin && $admin->role === 1) {
            $admin->notify(new SystemNotifications(
                'Property Deleted',
                'You deleted the property: ' . $propertyName
            ));
        }

        return response()->json(['message' => 'Property and related data deleted successfully']);
    }

    public function approved($id)
    {
        $user = Auth::user();
        if ($user->role !== 1) { // Only admin
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $property = Property::findOrFail($id);
        $property->status = 2; // Active
        $property->save();

        // Notify landlord that admin approved their property
        $landlord = $property->landlord;
        if ($landlord) {
            $landlord->notify(new SystemNotifications(
                'Property Approved',
                'Your property "' . $property->title . '" has been approved by the admin.'
            ));
        }

        // Send property suggestion email to all tenants
        try
        {
            \Log::info('Starting to queue property suggestion emails...', ['property_id' => $property->id]);

            $tenants = User::where('role', 3)->with('account')->get();

            foreach ($tenants as $tenant) {
                $email = $tenant->account->email ?? $tenant->email ?? null;
                if ($email) {
                    Mail::to($email)->queue(new PropertySuggestionMail($property));
                }
            }

            \Log::info('Finished queuing property suggestion emails', ['total_tenants' => $tenants->count()]);
        }
        catch (\Exception $e)
        {
            \Log::error('Failed to send property suggestion emails', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        return response()->json(['message' => 'Property approved successfully and notifications sent.', 'property' => $property]);
    }

    public function updateFeatured(Request $request, $id)
    {
        $property = Property::findOrFail($id);

        // ✅ Update only if present (no validation errors)
        if ($request->has('is_featured'))
        {
            $property->is_featured = $request->is_featured;
            $property->save();
        }

        return response()->json([
            'message'  => 'Property featured status updated successfully',
            'property' => $property->load('landlord')
        ]);
    }

    public function updateStatus(Request $request, $id)
    {
        $property = Property::findOrFail($id);

        // ✅ Update only if present (no validation)
        if ($request->has('status'))
        {
            $property->status = $request->status;
            $property->save();
        }

        $user = Auth::user(); // currently logged in user

        // ✅ Notifications logic
        if ($user->role === 2) // Landlord
        {
            // Notify landlord themselves
            $user->notify(new SystemNotifications(
                'Status update',
                'You have updated the status of ' . $property->title
            ));

            // Notify admin
            $admin = \App\Models\User::where('role', 1)->first();
            if ($admin)
            {
                $admin->notify(new SystemNotifications(
                    'Status update',
                    $user->account->full_name . ' updated the status of ' . $property->title
                ));
            }
        }
        elseif ($user->role === 1) // Admin
        {
            // Notify admin themselves
            $user->notify(new SystemNotifications(
                'Status update',
                'You have updated the status of ' . $property->title
            ));

            // Notify landlord
            $landlord = $property->landlord;
            if ($landlord)
            {
                $landlord->notify(new SystemNotifications(
                    'Status update',
                    'Admin updated the status of ' . $property->title
                ));
            }
        }

        return response()->json([
            'message'  => 'Property status updated successfully',
            'property' => $property->load('landlord')
        ]);
    }
}
