<?php

declare(strict_types=1);

namespace DolarBipolar\ValueObjects;

class TwitterCredentials
{
    public function __construct(
        public readonly string $consumerKey,
        public readonly string $consumerSecret,
        public readonly string $accessToken,
        public readonly string $accessTokenSecret,
    ) {}
}