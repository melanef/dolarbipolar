<?php

declare(strict_types=1);

namespace DolarBipolar\Enums;

enum Provider: string
{
    case CURRENCY_CONVERTER_API = 'currencyconverterapi';
    case WISE_SCRAPPER = 'wise';
}
