<?php

namespace App\Services\Channels\Ical;

use Illuminate\Support\Carbon;

class IcalParser
{
    /**
     * Parse iCalendar text and return occupied night date strings (Y-m-d).
     * DTEND is treated as exclusive (the checkout day is NOT occupied).
     *
     * @return array<int, string> sorted unique Y-m-d strings
     */
    public function occupiedDates(string $ics): array
    {
        $text = $this->unfold($ics);
        $lines = preg_split('/\r\n|\n|\r/', $text);

        $dates = [];
        $start = null;
        $end = null;
        $inEvent = false;

        foreach ($lines as $line) {
            $upper = strtoupper($line);

            if ($upper === 'BEGIN:VEVENT') {
                $inEvent = true;
                $start = $end = null;
                continue;
            }
            if ($upper === 'END:VEVENT') {
                if ($start) {
                    $endDate = $end ?: Carbon::parse($start)->addDay();
                    $cursor = Carbon::parse($start);
                    // guard against malformed huge ranges
                    $limit = 0;
                    while ($cursor->lt($endDate) && $limit < 1000) {
                        $dates[$cursor->toDateString()] = true;
                        $cursor->addDay();
                        $limit++;
                    }
                }
                $inEvent = false;
                continue;
            }

            if (! $inEvent) {
                continue;
            }

            if (str_starts_with($upper, 'DTSTART')) {
                $start = $this->parseDate($line);
            } elseif (str_starts_with($upper, 'DTEND')) {
                $end = $this->parseDate($line);
            }
        }

        $out = array_keys($dates);
        sort($out);

        return $out;
    }

    private function parseDate(string $line): ?Carbon
    {
        // e.g. "DTSTART;VALUE=DATE:20260801" or "DTEND:20260804T100000Z"
        $value = substr($line, strpos($line, ':') + 1);
        $value = trim($value);
        // take leading 8 digits (YYYYMMDD)
        if (preg_match('/(\d{8})/', $value, $m)) {
            try {
                return Carbon::createFromFormat('Ymd', $m[1])->startOfDay();
            } catch (\Throwable) {
                return null;
            }
        }

        return null;
    }

    /**
     * Unfold RFC5545 folded lines (continuation lines start with space or tab).
     */
    private function unfold(string $text): string
    {
        return preg_replace('/\r?\n[ \t]/', '', $text);
    }
}
