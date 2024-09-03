<?php

declare(strict_types=1);

namespace DolarBipolar\Publishers;

use Abraham\TwitterOAuth\TwitterOAuth;
use DolarBipolar\ValueObjects\TwitterCredentials;
use RuntimeException;

class TwitterPublisher implements Publisher
{
    private string $connectionName;
    private TwitterOAuth $connection;

    public function __construct(TwitterCredentials $credentials)
    {
        $this->connectionName = $credentials->name;
        $this->connection = new TwitterOAuth(
            $credentials->consumerApiKey,
            $credentials->consumerApiSecret,
            $credentials->twitterApiKey,
            $credentials->consumerApiSecret
        );
    }

    public function publish(string $status): void
    {
        $this->connection->post("statuses/update", ['status' => $status]);

        if ($this->connection->getLastHttpCode() !== 200) {
            throw new RuntimeException(json_encode($this->connection->getLastBody()));
        }
    }
}
