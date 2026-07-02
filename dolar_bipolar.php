<?php
/**
 * Created by PhpStorm.
 * User: Mario Zuany Neto <mariozuany>
 * Date: 05/03/15
 * Time: 19:19
 *
 * Updated by:
 * User: Amauri de Melo Junior <melanef>
 * Date: 01/10/2020
 * Time: 02:10
 */

error_reporting(E_ALL);
ini_set("display_errors", 1);

require "vendor/autoload.php";

use DolarBipolar\Enums\Mode;
use DolarBipolar\Enums\Provider;
use DolarBipolar\Providers\CurrencyConverterApi;
use DolarBipolar\Providers\WiseScrapper;
use DolarBipolar\Publishers\BlueSkyPublisher;
use DolarBipolar\Publishers\TwitterPublisher;
use DolarBipolar\ValueObjects\BlueSkyCredentials;
use DolarBipolar\ValueObjects\TwitterCredentials;
use GuzzleHttp\Client;

const FILE_OPTIONS = './options.json';
const FILE_HISTORY = './history.json';
const INCREASE = 'subiu';
const DECREASE = 'caiu';

$now = new DateTime('now', new DateTimeZone('America/Sao_Paulo'));
$options = json_decode(file_get_contents(FILE_OPTIONS), true, flags: JSON_THROW_ON_ERROR);
$lastQuotes = json_decode(file_get_contents(FILE_HISTORY), true, flags: JSON_THROW_ON_ERROR);

$mode = Mode::fromOptions($options);

$providers = [
    Provider::WISE_SCRAPPER->value => new WiseScrapper(new Client()),
];
foreach ($options['keys'] as $name => $key) {
    $provider = Provider::tryFrom($name);
    $providers[$name] = match ($provider) {
        Provider::CURRENCY_CONVERTER_API => new CurrencyConverterApi($options['keys'][$name], new Client()),
        default => null,
    };
}

foreach ($options['currencies'] as $currencySettings) {
    if (!hasPublisherConfig($currencySettings)) {
        print sprintf("%s: Pulando por falta de credenciais de publicação (sem Twitter e sem Bluesky)<br>%s", $currencySettings['name'], PHP_EOL);
        continue;
    }

    if (!array_key_exists('provider', $currencySettings) || !$currencySettings['provider']) {
        print sprintf("%s: Pulando por falta de credenciais de fonte<br>%s", $currencySettings['name'], PHP_EOL);
        continue;
    }

    $quote = $providers[$currencySettings['provider']]?->getQuote($currencySettings['currencyApiName'], $currencySettings['batch']);
    if (!$quote) {
        print sprintf("%s: Valor nulo retornado da fonte<br>%s", $currencySettings['name'], PHP_EOL);
        continue;
    }

    $lastQuote = null;
    if (!empty($lastQuotes[$currencySettings['currencyApiName']])) {
        $lastQuote = $lastQuotes[$currencySettings['currencyApiName']];
    } elseif (!empty($lastQuotes[$currencySettings['currencyApiName'].'_BRL'])) {
        $lastQuote = $lastQuotes[$currencySettings['currencyApiName'].'_BRL'];
    }

    $roundedQuote = round($quote, $currencySettings['precision']);
    $roundedLastQuote = round($lastQuote, $currencySettings['precision']);
    if ($mode !== Mode::FORCE && $roundedQuote === $roundedLastQuote) {
        print sprintf(
            '%s - %s - Sem alteração - %s (%s) - %s (%s)<br>%s',
            $now->format('Y-m-d H:i:s'),
            $currencySettings['currencyApiName'],
            $lastQuote,
            $roundedLastQuote,
            $quote,
            $roundedQuote,
            PHP_EOL
        );
        continue;
    }

    $variance = ($quote > $lastQuote) ? INCREASE : DECREASE;
    $emoji = ($variance === INCREASE) ? '☹️️' : '☺️';

    $day = new DateTime();

    if (empty($lastQuotes['daily'][$currencySettings['currencyApiName']]) || $lastQuotes['daily'][$currencySettings['currencyApiName']]['date'] != $day->format('Ymd')) {
        $lastQuotes['daily'][$currencySettings['currencyApiName']] = [
            'date' => $day->format('Ymd'),
            'value' => $quote,
            'closingValue' => $lastQuote,
        ];
    } else {
        $lastQuotes['daily'][$currencySettings['currencyApiName']]['value'] = $quote;
    }

    $dailyChange = null;
    $dailyChangeAbsolute = 0;
    if (!empty($lastQuotes['daily'][$currencySettings['currencyApiName']]['closingValue'])) {
        $dailyChange = $quote / $lastQuotes['daily'][$currencySettings['currencyApiName']]['closingValue'];
        $dailyChangeAbsolute = abs($quote - $lastQuotes['daily'][$currencySettings['currencyApiName']]['closingValue']);
    }

    $status = $options['twitterStatusFormat'];
    $status = str_replace('{name}', $currencySettings['name'], $status);
    $status = str_replace('{subiu/caiu}', $variance, $status);
    $status = str_replace('{emoji}', $emoji, $status);
    $status = str_replace(
        '{cotacao}',
        sprintf(
            '%s%s',
            number_format($roundedQuote, $currencySettings['precision'], ',', '.'),
            $currencySettings['batch'] == 1 ? '' : sprintf(' (lote de %d)', $currencySettings['batch'])
        ),
        $status
    );
    $status = str_replace('{data-hora}', $now->format('H:i'), $status);

    if (empty($dailyChange)) {
        $status = str_replace('{variacao}', '', $status);
    } else {
        $status = str_replace(
            '{variacao}',
            sprintf(
                'Variação %s %s',
                ($dailyChange > 1 ? '📈' : '📉'),
                renderDailyChange($dailyChange, $dailyChangeAbsolute)
            ),
            $status
        );
    }

    $updated = false;
    if (!empty($currencySettings['twitterKeys']) && is_array($currencySettings['twitterKeys'])) {
        foreach ($currencySettings['twitterKeys'] as $keys) {
            try {
                $publisher = new TwitterPublisher(
                    new TwitterCredentials(
                        $keys['consumerKey'],
                        $keys['consumerSecret'],
                        $keys['accessToken'],
                        $keys['accessTokenSecret']
                    ),
                    $mode === Mode::DEBUG,
                );

                $publisher->publish($status);
            } catch (Exception $e) {
                print sprintf("Erro: %s<br>%s", $e->getMessage(), PHP_EOL);
            }
        }
    }

    if (!empty($currencySettings['blueskyUser']) && !empty($currencySettings['blueskyPassword'])) {
        try {
            $publisher = new BlueskyPublisher(
                new BlueskyCredentials(
                    $currencySettings['blueskyUser'],
                    $currencySettings['blueskyPassword']
                ),
                $mode === Mode::DEBUG,
            );

            $publisher->publish($status);
            $updated = true;
        } catch (Exception $e) {
            print sprintf("Erro: %s<br>%s", $e->getMessage(), PHP_EOL);
        }
    }

    if ($updated) {
        $lastQuotes[$currencySettings['currencyApiName']] = $quote;
    }

    print sprintf(
        '%s - %s - Corpo: "%s"<br>%s',
        $now->format('Y-m-d H:i:s'),
        $currencySettings['currencyApiName'],
        $status,
        PHP_EOL
    );
}

file_put_contents(FILE_HISTORY, json_encode($lastQuotes, JSON_PRETTY_PRINT));

/**
 * @param float $change
 * @param float $absoluteChange
 *
 * @return string
 */
function renderDailyChange(float $change, float $absoluteChange): string
{
    $change = ($change - 1) * 100;

    $signal = '+';
    if ($change < 0) {
        $signal = '-';
        $change = abs($change);
    }

    return sprintf(
        '%s%s%% (R$ %s)',
        $signal,
        number_format($change, 2, ',', '.'),
        number_format($absoluteChange, 2, ',', '.')
    );
}

function hasPublisherConfig(array $currencySettings): bool
{
    if (!empty($currencySettings['twitterKeys']) && is_array($currencySettings['twitterKeys'])) {
        return true;
    }

    if (
        !empty($currencySettings['blueskyUser']) && is_array($currencySettings['blueskyUser'])
        && !empty($currencySettings['blueskyPassword']) && is_array($currencySettings['blueskyPassword'])
    ) {
        return true;
    }

    return false;
}
