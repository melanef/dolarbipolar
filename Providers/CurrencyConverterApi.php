<?php

namespace DolarBipolar\Providers;

use GuzzleHttp\Psr7\Request;
use Psr\Http\Client\ClientInterface;

class CurrencyConverterApi implements ApiProvider
{
    private const API_URL = 'https://api.currconv.com/api/v7/convert?q=%s_BRL&compact=ultra&apiKey=%s';

    /** @var ClientInterface */
    private $httpClient;

    /** @var string */
    private $key;

    /**
     * CurrConv constructor.
     *
     * @param string          $key
     * @param ClientInterface $client
     */
    public function __construct(string $key, ClientInterface $client)
    {
        $this->key = $key;
        $this->httpClient = $client;
    }

    /**
     * @inheritDoc
     */
    public function getQuote(string $currency, int $batch): float
    {
        $request = new Request('GET', sprintf(self::API_URL, $currency, $this->key));

        $response = $this->httpClient->sendRequest($request);
        $payload = json_decode($response->getBody()->getContents(), true);

        return $payload[$currency.'_BRL'] * $batch;
    }
}