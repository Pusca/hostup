<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Property;

class HomeController extends Controller
{
    public function index()
    {
        $properties = Property::published()
            ->with(['coverPhoto', 'photos'])
            ->orderBy('sort_order')
            ->get();

        return view('public.home', compact('properties'));
    }
}
