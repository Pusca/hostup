<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Services\Channels\Ical\IcalExporter;

class IcalController extends Controller
{
    public function export(string $token, IcalExporter $exporter)
    {
        $property = Property::where('ical_token', $token)->firstOrFail();

        return response($exporter->forProperty($property), 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'inline; filename="' . $property->slug . '.ics"',
        ]);
    }
}
