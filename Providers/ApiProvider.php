<?php

namespace DolarBipolar\Providers;

use Psr\Http\Client\ClientInterface;

interface ApiProvider
{
    public function getQuote(string $currency, int $batch): ?float;
}