<?php

declare(strict_types=1);

namespace DolarBipolar\Publishers;

use Abraham\TwitterOAuth\TwitterOAuth;
use DolarBipolar\ValueObjects\TwitterCredentials;
use RuntimeException;

class TwitterPublisher implements Publisher
{
    private TwitterOAuth $connection;

    public function __construct(TwitterCredentials $credentials)
    {
        $this->connection = new TwitterOAuth(
            $credentials->consumerApiKey,
            $credentials->consumerApiSecret,
            $credentials->twitterApiKey,
            $credentials->consumerApiSecret
        );
        $this->connection->setApiVersion('2');
    }

    public function publish(string $status): void
    {
        $this->connection->post('tweets', ['text' => $status], true);

        if ($this->connection->getLastHttpCode() !== 201) {
            throw new RuntimeException(json_encode($this->connection->getLastBody()));
        }
    }
}
