<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Channel;
use App\Models\ChannelLink;
use App\Models\Property;
use App\Services\Channels\Ical\IcalImporter;
use Illuminate\Http\Request;

class ChannelLinkController extends Controller
{
    /**
     * Save the iCal import URLs for each channel of a property.
     */
    public function update(Request $request, Property $property)
    {
        $data = $request->validate([
            'links' => ['array'],
            'links.*.ical_import_url' => ['nullable', 'url', 'max:1024'],
        ]);

        foreach ($data['links'] ?? [] as $channelId => $values) {
            $channel = Channel::find($channelId);
            if (! $channel || $channel->code === 'direct') {
                continue;
            }

            $url = $values['ical_import_url'] ?? null;

            ChannelLink::updateOrCreate(
                ['property_id' => $property->id, 'channel_id' => $channel->id],
                ['ical_import_url' => $url ?: null, 'is_active' => true],
            );
        }

        return back()->with('status', 'Canali aggiornati.');
    }

    /**
     * Manually trigger an iCal import for a property right now.
     */
    public function sync(Property $property, IcalImporter $importer)
    {
        $links = $property->channelLinks()
            ->whereNotNull('ical_import_url')
            ->where('is_active', true)
            ->with('channel', 'property')
            ->get();

        $total = 0;
        foreach ($links as $link) {
            $total += $importer->sync($link);
        }

        return back()->with('status', "Sincronizzazione completata: {$total} notti occupate importate da {$links->count()} canali.");
    }
}
