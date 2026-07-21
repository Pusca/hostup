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

        // Hero background: cover of the first property with a photo (real image, no stock).
        $heroImage = $properties
            ->map(fn ($p) => $p->coverPhoto ?? $p->photos->first())
            ->filter()
            ->first()?->url;

        return view('public.home', compact('properties', 'heroImage'));
    }
}
