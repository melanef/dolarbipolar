<?php

declare(strict_types=1);

namespace DolarBipolar\Publishers;

interface Publisher
{
    public function publish(string $status): void;
}