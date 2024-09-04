<?php

declare(strict_types=1);

namespace DolarBipolar\ValueObjects;

class TwitterCredentials
{
    public function __construct(
        public readonly string $consumerApiKey,
        public readonly string $consumerApiSecret,
        public readonly string $twitterApiKey,
        public readonly string $twitterApiSecret
    ) {}
}