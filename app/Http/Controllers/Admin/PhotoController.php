<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\PropertyPhoto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PhotoController extends Controller
{
    public function store(Request $request, Property $property)
    {
        $request->validate([
            'photos' => ['required', 'array'],
            'photos.*' => ['image', 'mimes:jpeg,jpg,png,webp', 'max:12288'], // 12MB
        ]);

        $maxOrder = (int) $property->photos()->max('sort_order');
        $hasCover = $property->photos()->where('is_cover', true)->exists();

        foreach ($request->file('photos') as $file) {
            $path = $file->store('properties/' . $property->slug, 'public');
            $maxOrder++;

            $property->photos()->create([
                'path' => $path,
                'sort_order' => $maxOrder,
                'is_cover' => ! $hasCover,
            ]);
            $hasCover = true;
        }

        return back()->with('status', 'Foto caricate.');
    }

    public function destroy(PropertyPhoto $photo)
    {
        $wasCover = $photo->is_cover;
        $property = $photo->property;

        if (! str_starts_with($photo->path, 'http')) {
            Storage::disk('public')->delete($photo->path);
        }
        $photo->delete();

        // Promote another photo to cover if needed
        if ($wasCover) {
            $next = $property->photos()->orderBy('sort_order')->first();
            $next?->update(['is_cover' => true]);
        }

        return back()->with('status', 'Foto eliminata.');
    }

    public function cover(PropertyPhoto $photo)
    {
        $photo->property->photos()->update(['is_cover' => false]);
        $photo->update(['is_cover' => true]);

        return back()->with('status', 'Copertina aggiornata.');
    }

    public function reorder(Request $request, Property $property)
    {
        $data = $request->validate([
            'order' => ['required', 'array'],
            'order.*' => ['integer'],
        ]);

        foreach ($data['order'] as $position => $photoId) {
            $property->photos()->where('id', $photoId)->update(['sort_order' => $position]);
        }

        return response()->json(['ok' => true]);
    }
}
