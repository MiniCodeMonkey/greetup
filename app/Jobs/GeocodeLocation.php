<?php

namespace App\Jobs;

use App\Services\GeocodingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Queue\Queueable;

class GeocodeLocation implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /**
     * @var list<int>
     */
    public array $backoff = [10, 60, 300];

    public function __construct(
        public Model $model,
        public string $addressField,
        public string $latitudeField,
        public string $longitudeField,
    ) {}

    public function handle(GeocodingService $geocoding): void
    {
        $address = $this->model->{$this->addressField};

        if ($address === null || trim($address) === '') {
            return;
        }

        $result = $geocoding->geocode($address);

        if ($result === null) {
            return;
        }

        $this->model->updateQuietly([
            $this->latitudeField => $result['lat'],
            $this->longitudeField => $result['lng'],
        ]);
    }
}
