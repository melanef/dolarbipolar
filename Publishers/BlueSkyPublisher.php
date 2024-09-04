<?php

declare(strict_types = 1);

namespace DolarBipolar\Publishers;

use DateTime;
use DolarBipolar\ValueObjects\BlueSkyCredentials;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

class BlueSkyPublisher implements Publisher
{
    private const BLUESKY_API_URL = 'https://bsky.social/xrpc';

    private Client $client;

    private array $token;

    public function __construct(BlueSkyCredentials $credentials)
    {
        $this->client = new Client();
        $this->init($credentials);
    }


    public function publish(string $status): void
    {
        $payload = [
            'repo' => $this->token['did'],
            'collection' => 'app.bsky.feed.post',
            'record' => [
                'text' => $status,
                'createdAt' => (new DateTime())->format(DateTime::ATOM),
            ],
        ];

        $request = new Request(
            'POST',
            self::BLUESKY_API_URL . '/com.atproto.repo.createRecord',
            [
                'Content-Type' => 'application/json',
                'Authorization' => "Bearer ". $this->token['accessJwt'],
            ],
            json_encode($payload)
        );

        $this->client->send($request);
    }

    private function init(BlueSkyCredentials $credentials): void
    {
        $payload = [
            'identifier' => $credentials->user,
            'password' => $credentials->password,
        ];

        $request = new Request(
            'POST',
            self::BLUESKY_API_URL . '/com.atproto.server.createSession',
            [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
            json_encode($payload)
        );

        $response = $this->client->sendRequest($request);
        $this->token = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
    }
}
