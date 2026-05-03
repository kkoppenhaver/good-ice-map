<?php

namespace App\Http\Controllers;

use App\Models\Location;

class AdminLocationController extends Controller
{
    public function index()
    {
        $locations = Location::where('status', 'pending')
            ->with(['images', 'submittedBy'])
            ->oldest()
            ->get();

        return view('admin.locations.index', compact('locations'));
    }

    public function approve(Location $location)
    {
        $location->update(['status' => 'approved']);

        return redirect()->route('admin.locations.index')
            ->with('success', "Approved “{$location->name}”.");
    }

    public function reject(Location $location)
    {
        $location->update(['status' => 'rejected']);

        return redirect()->route('admin.locations.index')
            ->with('success', "Rejected “{$location->name}”.");
    }
}
