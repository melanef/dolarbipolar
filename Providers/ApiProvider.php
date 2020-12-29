<?php

namespace DolarBipolar\Providers;

use Psr\Http\Client\ClientInterface;

interface ApiProvider
{
    /**
     * @param string $currency
     * @param int    $batch
     *
     * @return float
     */
    public function getQuote(string $currency, int $batch): float;
}