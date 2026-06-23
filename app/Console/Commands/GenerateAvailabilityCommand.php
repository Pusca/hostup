<?php

namespace App\Console\Commands;

use App\Models\Property;
use App\Services\Availability\AvailabilityService;
use Illuminate\Console\Command;

class GenerateAvailabilityCommand extends Command
{
    protected $signature = 'hostup:generate-availability {--days=540 : Giorni di finestra} {--property= : Limita a un property_id}';

    protected $description = 'Genera/estende il calendario di disponibilità degli immobili (finestra mobile)';

    public function handle(AvailabilityService $availability): int
    {
        $query = Property::query();
        if ($pid = $this->option('property')) {
            $query->where('id', $pid);
        }

        $days = (int) $this->option('days');
        $total = 0;

        foreach ($query->get() as $property) {
            $created = $availability->ensureCalendar($property, $days);
            $total += $created;
            $this->line(sprintf('%s → +%d giorni', $property->title, $created));
        }

        $this->info("Calendario aggiornato: {$total} giorni creati.");

        return self::SUCCESS;
    }
}
