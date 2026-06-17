<?php

namespace App\Services\Channels\Ical;

use App\Models\Availability;
use App\Models\ChannelLink;
use App\Models\SyncLog;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class IcalImporter
{
    public function __construct(private IcalParser $parser)
    {
    }

    /**
     * Pull an OTA iCal feed for a channel link and reconcile the master calendar.
     * Only nights owned by THIS channel (source = channel code) are managed:
     * new ones get blocked, vanished ones get freed. Direct bookings and other
     * channels' blocks are never touched.
     *
     * @return int number of occupied nights applied
     */
    public function sync(ChannelLink $link): int
    {
        $channelCode = $link->channel->code;
        $property = $link->property;

        if (empty($link->ical_import_url)) {
            return 0;
        }

        try {
            $response = Http::timeout(25)->retry(2, 500)->get($link->ical_import_url);
            if (! $response->successful()) {
                throw new \RuntimeException('HTTP ' . $response->status());
            }

            $occupied = collect($this->parser->occupiedDates($response->body()))
                ->filter(fn ($d) => $d >= Carbon::today()->toDateString())
                ->values()
                ->all();

            $applied = $this->reconcile($property->id, $channelCode, $occupied);

            $link->update(['last_synced_at' => now()]);

            SyncLog::create([
                'property_id' => $property->id,
                'channel_link_id' => $link->id,
                'direction' => 'import',
                'status' => 'success',
                'events_count' => $applied,
                'message' => "Sincronizzate {$applied} notti occupate da {$channelCode}.",
            ]);

            return $applied;
        } catch (\Throwable $e) {
            SyncLog::create([
                'property_id' => $property->id,
                'channel_link_id' => $link->id,
                'direction' => 'import',
                'status' => 'error',
                'events_count' => 0,
                'message' => 'Errore import: ' . $e->getMessage(),
            ]);

            return 0;
        }
    }

    /**
     * @param  array<int, string>  $occupied  Y-m-d strings
     */
    private function reconcile(int $propertyId, string $channelCode, array $occupied): int
    {
        return DB::transaction(function () use ($propertyId, $channelCode, $occupied) {
            $desired = array_fill_keys($occupied, true);

            // Nights currently owned by this channel
            $owned = Availability::where('property_id', $propertyId)
                ->where('source', $channelCode)
                ->where('status', 'blocked')
                ->whereDate('date', '>=', Carbon::today())
                ->get();

            // Free nights no longer present in the feed
            foreach ($owned as $row) {
                $key = Carbon::parse($row->date)->toDateString();
                if (! isset($desired[$key])) {
                    $row->update(['status' => 'available', 'source' => 'manual', 'booking_id' => null]);
                }
            }

            // Block the nights from the feed
            $applied = 0;
            foreach ($occupied as $date) {
                $row = Availability::firstOrNew([
                    'property_id' => $propertyId,
                    'date' => $date,
                ]);

                // Don't override a direct booking or another channel's block
                if ($row->exists && in_array($row->status, ['booked', 'blocked'], true) && $row->source !== $channelCode) {
                    continue;
                }

                $row->status = 'blocked';
                $row->source = $channelCode;
                $row->save();
                $applied++;
            }

            return $applied;
        });
    }
}
