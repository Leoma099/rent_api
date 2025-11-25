<?php

namespace App\Http\Controllers;

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

                    $query->where(function($q) use ($search)
                    {
                        $q->where('address', 'like', '%' . $search . '%')
                        ->orWhere('title', 'like', '%' . $search . '%')
                        ->orWhere('property_type', 'like', '%' . $search . '%')
                        ->orWhere('barangay', 'like', '%' . $search . '%');
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
                    $query->where('property_type', 'like', '%' . $request->property_type . '%');
                }

                // ✅ Auto filter by barangay
                if ($request->has('barangay') && $request->barangay != '')
                {
                    $query->where('barangay', 'like', '%' . $request->barangay . '%');
                }

                // ✅ Filter by address OR property title
                if ($request->has('search') && $request->search != '')
                {
                    $search = $request->search;
                    $query->where(function($q) use ($search)
                    {
                        $q->where('address', 'like', '%' . $search . '%')
                        ->orWhere('title', 'like', '%' . $search . '%')
                        ->orWhere('property_type', 'like', '%' . $search . '%')
                        ->orWhere('barangay', 'like', '%' . $search . '%');
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
                    ->orWhere('title', 'like', '%' . $search . '%')
                    ->orWhere('property_type', 'like', '%' . $search . '%')
                    ->orWhere('barangay', 'like', '%' . $search . '%');
                });
            }

            // ✅ Add property_type filter for guests
            if ($request->has('property_type') && $request->property_type != '')
            {
                $query->where('property_type', 'like', '%' . $request->property_type . '%');
            }

            // ✅ Auto filter by barangay
            if ($request->has('barangay') && $request->barangay != '')
            {
                $query->where('barangay', 'like', '%' . $request->barangay . '%');
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

        // Validate request
        $validatedData = $request->validate([
            'title'             => 'required|string|max:255',
            'description'       => 'required|string',
            'address'           => 'required|string|max:255',
            'barangay'          => 'required|string',
            'lat'               => 'required|numeric',
            'lng'               => 'required|numeric',
            'price'             => 'required|numeric',
            'property_type'     => 'required|string',
            'photo_1'           => 'nullable|image',
            'photo_2'           => 'nullable|image',
            'photo_3'           => 'nullable|image',
            'photo_4'           => 'nullable|image',
            'size'              => 'required|numeric',
            'schedules' => 'nullable|array',
            'schedules.*.available_day' => 'required_with:schedules|string',
            'schedules.*.start_time' => 'required_with:schedules|date_format:H:i',
            'schedules.*.end_time' => 'required_with:schedules|date_format:H:i|after:start_time',
            'landmarks' => 'nullable|array',
            'landmarks.*.name' => 'required_with:landmarks|string|max:255',
            'landmarks.*.vicinity' => 'nullable|string|max:255',
            'landmarks.*.distance' => 'nullable|numeric',
            'landmarks.*.lat' => 'nullable|numeric',
            'landmarks.*.lng' => 'nullable|numeric',
            'landmarks.*.type' => 'nullable|string|max:255',
        ]);

        // Handle photo upload
        $photos = [];
        foreach (['photo_1', 'photo_2', 'photo_3', 'photo_4'] as $photoField) {
            $photos[$photoField] = $request->hasFile($photoField) ? $request->file($photoField)->store('uploads/photos', 'public') : null;
        }

        $property = Property::create([
            'landlord_id'   => Auth::id(),
            'title'         => $validatedData['title'],
            'description'   => $validatedData['description'] ?? null,
            'address'       => $validatedData['address'] ?? null,
            'barangay'       => $validatedData['barangay'] ?? null,
            'lat'           => $validatedData['lat'] ?? null,
            'lng'           => $validatedData['lng'] ?? null,
            'price'         => $validatedData['price'] ?? null,
            'property_type' => $validatedData['property_type'] ?? null,
            'photo_1'       => $photos['photo_1'],
            'photo_2'       => $photos['photo_2'],
            'photo_3'       => $photos['photo_3'],
            'photo_4'       => $photos['photo_4'],
            'status'        => 0, // inactive, needs admin approval
            'size'          => $validatedData['size'] ?? null,
            'is_featured'   => 0,
            'propertyStats' => 1,
        ]);

        // Notify admin only
        $admin = User::where('role', 1)->first();
        if ($admin) {
            $admin->notify(new SystemNotifications(
                'New Property Added',
                Auth::user()->account->full_name . ' has added a new property listing: ' . $property->title
            ));
        }

        // Create schedules if any
        if (!empty($validatedData['schedules'])) {
            foreach ($validatedData['schedules'] as $sched) {
                $property->schedules()->create([
                    'landlord_id' => Auth::id(),
                    'available_day' => $sched['available_day'],
                    'start_time' => $sched['start_time'],
                    'end_time' => $sched['end_time'],
                ]);
            }
        }

        // Create landmarks if any
        if (!empty($validatedData['landmarks'])) {
            foreach ($validatedData['landmarks'] as $landmark) {
                $property->landmarks()->create([
                    'landlord_id' => Auth::id(),
                    'name' => $landmark['name'],
                    'vicinity' => $landmark['vicinity'] ?? null,
                    'distance' => $landmark['distance'] ?? null,
                    'lat' => $landmark['lat'] ?? null,
                    'lng' => $landmark['lng'] ?? null,
                    'type' => $landmark['type'] ?? null,
                ]);
            }
        }

        return response()->json(['message' => 'Property record successfully created. Waiting for admin approval.', 'property' => $property->load('schedules')], 201);
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
                ->whereIn('status', [0, 1])
                ->exists();
        });

        // ✅ Add full URLs for photo & floor_plan
        if ($property->photo) {
            $property->photo = asset('storage/' . $property->photo);
        }
        if ($property->floor_plan) {
            $property->floor_plan = asset('storage/' . $property->floor_plan);
        }

        // ✅ Update last_viewed_at ONLY if propertyStats is 1, 3, or 4
        if (in_array($property->propertyStats, [1, 3, 4])) {
            $property->last_viewed_at = now();
            $property->save();
        }

        return response()->json($property);
    }

    public function update(Request $request, $id)
    {
        $property = Property::findOrFail($id);

        // Only landlords can update their own property
        if (Auth::user()->role != 2 || $property->landlord_id != Auth::id()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        // Decode schedules JSON string to array
        if ($request->has('schedules')) {
            $schedules = json_decode($request->input('schedules'), true);

            // Convert times to H:i format
            foreach ($schedules as &$sched) {
                $sched['start_time'] = \Carbon\Carbon::parse($sched['start_time'])->format('H:i');
                $sched['end_time']   = \Carbon\Carbon::parse($sched['end_time'])->format('H:i');
            }

            $request->merge(['schedules' => $schedules]);
        }

        // Decode landmarks JSON string to array
        if ($request->has('landmarks')) {
            $request->merge([
                'landmarks' => json_decode($request->input('landmarks'), true)
            ]);
        }

        // Validate request
        $validatedData = $request->validate([
            'title'             => 'nullable|string|max:255',
            'description'       => 'nullable|string',
            'address'           => 'nullable|string|max:255',
            'barangay'          => 'nullable|string',
            'lat'               => 'nullable|numeric',
            'lng'               => 'nullable|numeric',
            'price'             => 'nullable|numeric',
            'property_type'     => 'nullable|string',
            'photo_1'           => 'nullable|image|max:51200',
            'photo_2'           => 'nullable|image|max:51200',
            'photo_3'           => 'nullable|image|max:51200',
            'photo_4'           => 'nullable|image|max:51200',
            'size'              => 'nullable|numeric',
            'status'            => 'nullable|integer',
            'propertyStats'     => 'nullable|integer',
            'is_featured'       => 'nullable|integer',
            'schedules'         => 'nullable|array',
            'schedules.*.available_day' => 'required_with:schedules|string',
            'schedules.*.start_time' => 'required_with:schedules|date_format:H:i',
            'schedules.*.end_time'   => 'required_with:schedules|date_format:H:i|after:schedules.*.start_time',
            'landmarks'         => 'nullable|array',
            'landmarks.*.name'  => 'required_with:landmarks|string|max:255',
            'landmarks.*.vicinity' => 'nullable|string|max:255',
            'landmarks.*.distance' => 'nullable|numeric',
            'landmarks.*.lat'   => 'nullable|numeric',
            'landmarks.*.lng'   => 'nullable|numeric',
        ]);

        // Handle photo uploads individually
        foreach (['photo_1', 'photo_2', 'photo_3', 'photo_4'] as $photoField) {
            if ($request->hasFile($photoField)) {
                $property->$photoField = $request->file($photoField)->store('uploads/photos', 'public');
            }
        }

        // Update property basic fields
        $property->update([
            'title'         => $validatedData['title'] ?? $property->title,
            'description'   => $validatedData['description'] ?? $property->description,
            'address'       => $validatedData['address'] ?? $property->address,
            'barangay'       => $validatedData['barangay'] ?? $property->barangay,
            'lat'           => $validatedData['lat'] ?? $property->lat,
            'lng'           => $validatedData['lng'] ?? $property->lng,
            'price'         => $validatedData['price'] ?? $property->price,
            'property_type' => $validatedData['property_type'] ?? $property->property_type,
            'size'          => $validatedData['size'] ?? $property->size,
            'status'        => $validatedData['status'] ?? $property->status,
            'propertyStats' => $validatedData['propertyStats'] ?? $property->propertyStats,
            'is_featured'   => $validatedData['is_featured'] ?? $property->is_featured,
        ]);

        // Update schedules
        if (!empty($validatedData['schedules'])) {
            $property->schedules()->delete();
            foreach ($validatedData['schedules'] as $sched) {
                $property->schedules()->create([
                    'landlord_id'   => $property->landlord_id,
                    'available_day' => $sched['available_day'],
                    'start_time'    => $sched['start_time'],
                    'end_time'      => $sched['end_time'],
                ]);
            }
        }

        // Update landmarks
        if (!empty($validatedData['landmarks'])) {
            $property->landmarks()->delete();
            foreach ($validatedData['landmarks'] as $lm) {
                $property->landmarks()->create([
                    'landlord_id' => $property->landlord_id,
                    'name'        => $lm['name'],
                    'vicinity'    => $lm['vicinity'] ?? null,
                    'distance'    => $lm['distance'] ?? null,
                    'lat'         => $lm['lat'] ?? null,
                    'lng'         => $lm['lng'] ?? null,
                    'type'        => $lm['type'] ?? null,
                ]);
            }
        }

        // Notify landlord
        $user = Auth::user();
        if ($user->role === 2) {
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

    public function totalProperties()
    {
        if (!Auth::check()) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        if (Auth::user()->role === 1)
        { // Admin
            $totalProperties = Property::count(); // all properties
        }
        elseif (Auth::user()->role === 2)
        { // Landlord
            $totalProperties = Property::where('landlord_id', Auth::id())->count(); // only their own
        }
        else
        { // Tenant or others
            $totalProperties = Property::where('status', 1)->count(); // only approved properties
        }

        return response()->json([
            'total_properties' => $totalProperties
        ]);
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
                'Your new property "' . $property->title . '" has been approved by the admin.'
            ));
        }

        // Send property suggestion email to all tenants
        try
        {
            \Log::info('Starting to queue property suggestion emails...', ['property_id' => $property->id]);

            $tenants = User::where('role', 3)->with('account')->get();
            foreach ($tenants as $tenant) {
                $email = $tenant->account->email ?? null;
                if ($email) {
                    Mail::to($email)->send(new PropertySuggestionMail($property));
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

    public function destroy($id)
    {
        $property = Property::findOrFail($id);
        $propertyName = $property->title;

        // Delete bookings first via schedules
        foreach ($property->schedules as $schedule) {
            $schedule->bookings()->delete();
        }

        // Delete related data
        $property->schedules()->delete();
        $property->inquiries()->delete();
        $property->leases()->delete();
        $property->landmarks()->delete();
        $property->bookings()->delete();

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


    public function pendingProperty()
    {
        $totalPendingProperty = Property::where('status', 0)->count();

        return response()->json([
            'total_pending_properties' => $totalPendingProperty
        ]);
    }

    public function recommended($id)
    {
        $currentProperty = Property::findOrFail($id);

        // Split the property types into an array and trim spaces
        $types = explode(',', $currentProperty->property_type);
        $types = array_map('trim', $types);

        // Fetch recommended properties matching any type
        $recommended = Property::with('landlord.account', 'landmarks')
            ->where('status', 2)
            ->where('id', '!=', $id)
            ->where(function($query) use ($types) {
                foreach ($types as $type) {
                    $query->orWhere('property_type', 'like', "%{$type}%");
                }
            })
            ->latest()
            ->take(3)
            ->get();

        return response()->json($recommended);
    }

    public function featured()
    {
        $properties = Property::with('landlord.account', 'landmarks')
            ->where('status', 1)
            ->where('is_featured', true)
            ->latest()
            ->take(3)
            ->get();

        return response()->json($properties);
    }

    public function recentProperty()
    {
        $properties = Property::with('landlord.account', 'schedules', 'landmarks')
            ->where('status', 2)
            ->latest()
            ->take(12)
            ->get();

        return response()->json($properties);
    }


}
