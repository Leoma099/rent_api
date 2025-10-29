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
            ->where('status', 1)
            ->latest()
            ->take(9)
            ->get();

        return response()->json($properties);
    }

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
                    ->where('status', 1);

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
                    ->where('status', 1)
                    ->latest()
                    ->get();
            }
        }
        else
        {
            // Guest (not logged in)
            $query = Property::with('landlord.account', 'schedules', 'landmarks')
                ->where('status', 1);

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
            'title'             => 'required|string|max:255', // changed from title
            'description'       => 'nullable|string',
            'address'           => 'nullable|string|max:255',
            'lat'               => 'nullable|numeric', // fixed name + rule
            'lng'               => 'nullable|numeric', // fixed name + rule
            'price'             => 'nullable|numeric', // decimal -> numeric
            'property_type'     => 'nullable|string', // use ID (1–15)
            'photo_1'             => 'nullable|image|max:51200',
            'photo_2'             => 'nullable|image|max:51200',
            'photo_3'             => 'nullable|image|max:51200',
            'photo_4'             => 'nullable|image|max:51200',
            'floor_plan'        => 'nullable|image|max:51200',
            'size'              => 'nullable|numeric',
            'schedules' => 'nullable|array',
            'schedules.*.available_day' => 'required_with:schedules|string',
            'schedules.*.start_time' => 'required_with:schedules|date_format:H:i',
            'schedules.*.end_time' => 'required_with:schedules|date_format:H:i|after:schedules.*.start_time',
            'landmarks' => 'nullable|array',
            'landmarks.*.name' => 'required_with:landmarks|string|max:255',
            'landmarks.*.vicinity' => 'nullable|string|max:255',
            'landmarks.*.distance' => 'nullable|numeric',
            'landmarks.*.lat' => 'nullable|numeric',
            'landmarks.*.lng' => 'nullable|numeric',

        ]);

        // ✅ Handle photo upload
        $photoPath1 = null;
        $photoPath2 = null;
        $photoPath3 = null;
        $photoPath4 = null;
        $floorPlanPath = null;

        if ($request->hasFile('photo_1')) {
            $photoPath1 = $request->file('photo_1')->store('uploads/photos', 'public');
        }
        if ($request->hasFile('photo_2')) {
            $photoPath2 = $request->file('photo_2')->store('uploads/photos', 'public');
        }
        if ($request->hasFile('photo_3')) {
            $photoPath3 = $request->file('photo_3')->store('uploads/photos', 'public');
        }
        if ($request->hasFile('photo_4')) {
            $photoPath4 = $request->file('photo_4')->store('uploads/photos', 'public');
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
            'photo_1' => $photoPath1,
            'photo_2' => $photoPath2,
            'photo_3' => $photoPath3,
            'photo_4' => $photoPath4,
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
        try {   
            \Log::info('Starting to queue property suggestion emails...');

            $tenants = User::where('role', 3)->with('account')->get();
            \Log::info('Tenants found: ' . $tenants->count());

            foreach ($tenants as $tenant) {
                $email = $tenant->account->email ?? null;

                if ($email) {
                    \Log::info('Queueing email for: ' . $email);

                    Mail::to($email)->queue(new PropertySuggestionMail($property));

                    \Log::info('Queued email for: ' . $email);
                } else {
                    \Log::warning('Skipped tenant — no email address found.');
                }
            }

            \Log::info('Finished queuing all property suggestion emails.');
        } catch (\Exception $e) {
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
            'title'             => 'required|string|max:255',
            'description'       => 'nullable|string',
            'address'           => 'nullable|string|max:255',
            'lat'               => 'nullable|numeric',
            'lng'               => 'nullable|numeric',
            'price'             => 'nullable|numeric',
            'property_type'     => 'nullable|string',
            'photo_1'           => 'nullable|image|max:51200',
            'photo_2'           => 'nullable|image|max:51200',
            'photo_3'           => 'nullable|image|max:51200',
            'photo_4'           => 'nullable|image|max:51200',
            'floor_plan'        => 'nullable|image|max:51200',
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
        foreach (['photo_1', 'photo_2', 'photo_3', 'photo_4', 'floor_plan'] as $photoField) {
            if ($request->hasFile($photoField)) {
                $property->$photoField = $request->file($photoField)->store('uploads/photos', 'public');
            }
        }

        // Update property basic fields
        $property->update([
            'title'         => $validatedData['title'],
            'description'   => $validatedData['description'] ?? $property->description,
            'address'       => $validatedData['address'] ?? $property->address,
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
        $property->status = 1; // Active
        $property->save();

        return response()->json(['message' => 'Property approved successfully', 'property' => $property]);
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

        // Store property title before deleting
        $propertyName = $property->title;

        // Delete related data
        $property->schedules()->each(function($schedule) {
            $schedule->bookings()->delete(); // delete bookings first
        });
        $property->schedules()->delete();
        $property->inquiries()->delete();
        $property->leases()->delete();

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

        // Fetch recommended properties based on property_type (and active status)
        $recommended = Property::with('landlord.account', 'landmarks')
            ->where('status', 1)
            ->where('property_type', $currentProperty->property_type)
            ->where('id', '!=', $id) // exclude current property
            ->latest()
            ->take(3)
            ->get();

        return response()->json($recommended);
    }


}
