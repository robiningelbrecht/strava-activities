<?php

declare(strict_types=1);

namespace App\Domain\Nominatim;

use App\Infrastructure\Serialization\Json;
use App\Infrastructure\ValueObject\Geography\Coordinate;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;

final readonly class LiveNominatim implements Nominatim
{
    public function __construct(
        private Client $client,
    ) {
    }

    public function reverseGeocode(Coordinate $coordinate): Location
    {
        $response = $this->client->request(
            'GET',
            'https://nominatim.openstreetmap.org/reverse',
            [
                RequestOptions::HEADERS => [
                    'User-Agent' => 'Strava Activities App',
                ],
                RequestOptions::QUERY => [
                    'lat' => $coordinate->getLatitude()->toFloat(),
                    'lon' => $coordinate->getLongitude()->toFloat(),
                    'format' => 'json',
                ],
            ]
        );

        $response = Json::decode($response->getBody()->getContents());

        return Location::fromState($response['address']);
    }
}
