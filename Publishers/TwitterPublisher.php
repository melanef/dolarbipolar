<?php

declare(strict_types=1);

namespace DolarBipolar\Publishers;

use Abraham\TwitterOAuth\TwitterOAuth;
use DolarBipolar\ValueObjects\TwitterCredentials;
use RuntimeException;

class TwitterPublisher implements Publisher
{
    private TwitterOAuth $connection;

    public function __construct(
        TwitterCredentials $credentials,
        private readonly bool $isDebugMode = false,
    ) {
        $this->connection = new TwitterOAuth(
            $credentials->consumerKey,
            $credentials->consumerSecret,
            $credentials->accessToken,
            $credentials->accessTokenSecret,
        );
        $this->connection->setApiVersion('2');
    }

    public function publish(string $status): void
    {
        if ($this->isDebugMode) {
            print sprintf("DEBUG MODE - TWEET: %s%s<br>", $status, PHP_EOL);
            return;
        }

        $this->connection->post('statuses/update', ['status' => $status]);

        if ($this->connection->getLastHttpCode() !== 201) {
            throw new RuntimeException(json_encode($this->connection->getLastBody()));
        }
    }
}
