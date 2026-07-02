<?php

declare(strict_types=1);

namespace DolarBipolar\Providers;

use GuzzleHttp\Psr7\Request;
use Psr\Http\Client\ClientInterface;

class WiseScrapper implements ApiProvider
{
    private const API_URL = 'https://wise.com/rates/history+live?source=%s&target=BRL&length=1&resolution=hourly&unit=day';

    public function __construct(
        private readonly ClientInterface $httpClient,
    ) {
    }

    public function getQuote(string $currency, int $batch): ?float
    {
        $request = new Request('GET', sprintf(self::API_URL, $currency, $batch));

        $response = $this->httpClient->sendRequest($request);
        $payload = $response->getBody()->getContents();
        $parsed = json_decode($payload, true, flags: JSON_THROW_ON_ERROR);
        $value = $parsed[count($parsed) - 1]['value'] ?? null;

        return $value ? $value * $batch : null;
    }
}