<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Property;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'properties' => Property::count(),
            'published' => Property::where('status', 'published')->count(),
            'bookings' => Booking::count(),
            'upcoming' => Booking::active()->whereDate('check_in', '>=', today())->count(),
        ];

        $properties = Property::withCount('photos')->orderBy('sort_order')->get();
        $recentBookings = Booking::with('property')->latest()->take(8)->get();

        return view('admin.dashboard', compact('stats', 'properties', 'recentBookings'));
    }
}
