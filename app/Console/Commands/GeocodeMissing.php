<?php

namespace App\Console\Commands;

use App\Models\Event;
use App\Models\Group;
use App\Models\User;
use App\Services\GeocodingService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

#[Signature('greetup:geocode-missing')]
#[Description('Batch geocode groups, events, and users with addresses but missing coordinates')]
class GeocodeMissing extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(GeocodingService $geocoding): int
    {
        if (! config('services.geocodio.api_key')) {
            $this->warn('No Geocodio API key configured. Set GEOCODIO_API_KEY in your .env file.');

            return self::FAILURE;
        }

        $totalGeocoded = 0;

        $totalGeocoded += $this->geocodeGroups($geocoding);
        $totalGeocoded += $this->geocodeEvents($geocoding);
        $totalGeocoded += $this->geocodeUsers($geocoding);

        $this->newLine();
        $this->info("Done. Geocoded {$totalGeocoded} record(s) total.");

        return self::SUCCESS;
    }

    private function geocodeGroups(GeocodingService $geocoding): int
    {
        $groups = Group::query()
            ->whereNotNull('location')
            ->where('location', '!=', '')
            ->whereNull('latitude')
            ->get();

        return $this->geocodeBatch($geocoding, $groups, 'groups', 'location', 'latitude', 'longitude');
    }

    private function geocodeEvents(GeocodingService $geocoding): int
    {
        $events = Event::query()
            ->whereNotNull('venue_address')
            ->where('venue_address', '!=', '')
            ->whereNull('venue_latitude')
            ->get();

        return $this->geocodeBatch($geocoding, $events, 'events', 'venue_address', 'venue_latitude', 'venue_longitude');
    }

    private function geocodeUsers(GeocodingService $geocoding): int
    {
        $users = User::query()
            ->whereNotNull('location')
            ->where('location', '!=', '')
            ->whereNull('latitude')
            ->get();

        return $this->geocodeBatch($geocoding, $users, 'users', 'location', 'latitude', 'longitude');
    }

    /**
     * @param  Collection<int, Model>  $models
     */
    private function geocodeBatch(
        GeocodingService $geocoding,
        Collection $models,
        string $label,
        string $addressField,
        string $latField,
        string $lngField,
    ): int {
        if ($models->isEmpty()) {
            $this->info("No {$label} need geocoding.");

            return 0;
        }

        $this->info("Geocoding {$models->count()} {$label}...");

        $geocoded = 0;

        foreach ($models as $model) {
            $result = $geocoding->geocode($model->{$addressField});

            if ($result === null) {
                $this->warn("  Could not geocode: {$model->{$addressField}}");

                continue;
            }

            $model->updateQuietly([
                $latField => $result['lat'],
                $lngField => $result['lng'],
            ]);

            $geocoded++;
        }

        $this->info("  Geocoded {$geocoded}/{$models->count()} {$label}.");

        return $geocoded;
    }
}
