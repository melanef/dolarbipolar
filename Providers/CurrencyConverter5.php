<?php

namespace DolarBipolar\Providers;

use GuzzleHttp\Psr7\Request;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;

class CurrencyConverter5 implements ApiProvider
{
    private const API_URL = 'https://currency-converter5.p.rapidapi.com/currency/convert?format=json&from=%s&to=BRL&amount=%d';

    private const HEADER_HOST = 'X-Rapidapi-Host';
    private const HEADER_KEY = 'X-Rapidapi-Key';

    private const HOST = 'currency-converter5.p.rapidapi.com';

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
     * @throws ClientExceptionInterface
     */
    public function getQuote(string $currency, int $batch): float
    {
        $request = new Request(
            'GET',
            sprintf(self::API_URL, $currency, $batch),
            [
                self::HEADER_HOST => self::HOST,
                self::HEADER_KEY => $this->key,
            ]
        );

        sleep(1);
        $response = $this->httpClient->sendRequest($request);

        $payload = json_decode($response->getBody()->getContents(), true);

        return $payload['rates']['BRL']['rate_for_amount'];
    }
}