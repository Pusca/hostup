<?php

namespace App\Services\Channels\Ical;

use App\Models\Property;
use Illuminate\Support\Carbon;

class IcalExporter
{
    /**
     * Build an iCalendar feed of all OCCUPIED nights for a property
     * (direct bookings + manual blocks + nights imported from other OTAs).
     * This is the feed you paste into Airbnb / Booking so they block these dates.
     */
    public function forProperty(Property $property): string
    {
        $dates = $property->availability()
            ->whereIn('status', ['booked', 'blocked'])
            ->orderBy('date')
            ->pluck('date')
            ->map(fn ($d) => Carbon::parse($d)->toDateString())
            ->all();

        $ranges = $this->collapse($dates);

        $now = Carbon::now('UTC')->format('Ymd\THis\Z');
        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//HostUp//Channel Manager//IT',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
            'X-WR-CALNAME:' . $this->escape($property->title),
        ];

        foreach ($ranges as $i => $range) {
            // DTEND is exclusive in iCal -> checkout = last occupied night + 1 day
            $start = Carbon::parse($range[0]);
            $endExclusive = Carbon::parse($range[1])->addDay();
            $uid = sprintf('%s-%d@hostup', $property->ical_token, $i);

            $lines[] = 'BEGIN:VEVENT';
            $lines[] = 'UID:' . $uid;
            $lines[] = 'DTSTAMP:' . $now;
            $lines[] = 'DTSTART;VALUE=DATE:' . $start->format('Ymd');
            $lines[] = 'DTEND;VALUE=DATE:' . $endExclusive->format('Ymd');
            $lines[] = 'SUMMARY:Non disponibile';
            $lines[] = 'END:VEVENT';
        }

        $lines[] = 'END:VCALENDAR';

        return implode("\r\n", $lines) . "\r\n";
    }

    /**
     * Collapse a sorted list of Y-m-d strings into [start, endInclusive] ranges.
     *
     * @param  array<int, string>  $dates
     * @return array<int, array{0:string,1:string}>
     */
    private function collapse(array $dates): array
    {
        if (empty($dates)) {
            return [];
        }

        $ranges = [];
        $start = $prev = $dates[0];

        foreach (array_slice($dates, 1) as $date) {
            if (Carbon::parse($prev)->addDay()->toDateString() === $date) {
                $prev = $date;
                continue;
            }
            $ranges[] = [$start, $prev];
            $start = $prev = $date;
        }
        $ranges[] = [$start, $prev];

        return $ranges;
    }

    private function escape(string $value): string
    {
        return addcslashes($value, ",;\\");
    }
}
