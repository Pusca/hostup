<?php

namespace App\Console\Commands;

use App\Models\ChannelLink;
use App\Services\Channels\Ical\IcalImporter;
use Illuminate\Console\Command;

class SyncIcalCommand extends Command
{
    protected $signature = 'hostup:sync-ical {--property= : Limita a un property_id}';

    protected $description = 'Importa i calendari iCal delle OTA (Airbnb, Booking, ...) nel calendario master';

    public function handle(IcalImporter $importer): int
    {
        $query = ChannelLink::query()
            ->where('is_active', true)
            ->whereNotNull('ical_import_url')
            ->with('channel', 'property');

        if ($pid = $this->option('property')) {
            $query->where('property_id', $pid);
        }

        $links = $query->get();

        if ($links->isEmpty()) {
            $this->info('Nessun canale iCal da sincronizzare.');

            return self::SUCCESS;
        }

        foreach ($links as $link) {
            $count = $importer->sync($link);
            $this->line(sprintf(
                '[%s] %s → %d notti',
                $link->property->title,
                $link->channel->code,
                $count,
            ));
        }

        $this->info('Sincronizzazione iCal completata: ' . $links->count() . ' canali.');

        return self::SUCCESS;
    }
}
