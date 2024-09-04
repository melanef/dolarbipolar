<?php

declare(strict_types=1);

namespace DolarBipolar\ValueObjects;

class BlueSkyCredentials
{
    public function __construct(
        public readonly string $user,
        public readonly string $password
    ) {}
}