<?php

namespace App\Services;

use Geocodio\Geocodio;
use Throwable;

class GeocodingService
{
    private ?Geocodio $client = null;

    public function __construct(private readonly ?string $apiKey = null) {}

    /**
     * Forward geocode an address.
     *
     * @return array{lat: float, lng: float, formatted_address: string}|null
     */
    public function geocode(string $address): ?array
    {
        if (! $this->hasApiKey()) {
            return null;
        }

        try {
            $response = $this->client()->geocode($address);

            if (empty($response['results'])) {
                return null;
            }

            $result = $response['results'][0];

            return [
                'lat' => (float) $result['location']['lat'],
                'lng' => (float) $result['location']['lng'],
                'formatted_address' => $result['formatted_address'],
            ];
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Reverse geocode coordinates to a formatted address.
     */
    public function reverse(float $lat, float $lng): ?string
    {
        if (! $this->hasApiKey()) {
            return null;
        }

        try {
            $response = $this->client()->reverse([$lat, $lng]);

            if (empty($response['results'])) {
                return null;
            }

            return $response['results'][0]['formatted_address'];
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Batch geocode multiple addresses.
     *
     * @param  list<string>  $addresses
     * @return array<string, array{lat: float, lng: float, formatted_address: string}|null>
     */
    public function batch(array $addresses): array
    {
        if (! $this->hasApiKey() || $addresses === []) {
            return [];
        }

        try {
            $response = $this->client()->geocode($addresses);

            $results = [];

            foreach ($response['results'] as $index => $item) {
                $address = $addresses[$index];

                if (empty($item['response']['results'])) {
                    $results[$address] = null;

                    continue;
                }

                $result = $item['response']['results'][0];

                $results[$address] = [
                    'lat' => (float) $result['location']['lat'],
                    'lng' => (float) $result['location']['lng'],
                    'formatted_address' => $result['formatted_address'],
                ];
            }

            return $results;
        } catch (Throwable) {
            return [];
        }
    }

    private function hasApiKey(): bool
    {
        return $this->apiKey !== null && $this->apiKey !== '';
    }

    private function client(): Geocodio
    {
        if ($this->client === null) {
            $this->client = new Geocodio;
            $this->client->setApiKey($this->apiKey);
        }

        return $this->client;
    }
}
