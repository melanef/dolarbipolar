<?php

declare(strict_types=1);

namespace DolarBipolar\Providers;

use GuzzleHttp\Psr7\Request;
use Psr\Http\Client\ClientInterface;

class CurrencyConverterApi implements ApiProvider
{
    private const API_URL = 'https://api.currconv.com/api/v7/convert?q=%s_BRL&compact=ultra&apiKey=%s';

    public function __construct(
        private readonly string $key,
        private readonly ClientInterface $httpClient,
    ) {
    }

    public function getQuote(string $currency, int $batch): ?float
    {
        $request = new Request('GET', sprintf(self::API_URL, $currency, $this->key));

        $response = $this->httpClient->sendRequest($request);
        $payload = json_decode($response->getBody()->getContents(), true, flags: JSON_THROW_ON_ERROR);

        return $payload[$currency.'_BRL'] * $batch;
    }
}