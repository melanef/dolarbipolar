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

require "vendor/autoload.php";

use Abraham\TwitterOAuth\TwitterOAuth;
use DolarBipolar\Providers\CurrencyConverterApi;
use GuzzleHttp\Client;

const FILE_OPTIONS = './options.json';
const FILE_HISTORY = './history.json';
const INCREASE = 'subiu';
const DECREASE = 'caiu';

$now = new DateTime('now', new DateTimeZone('America/Sao_Paulo'));
$options = json_decode(file_get_contents(FILE_OPTIONS), true);
$lastQuotes = json_decode(file_get_contents(FILE_HISTORY), true);
$provider = new CurrencyConverterApi($options['keys']['currencyconverterapi'], new Client());

foreach ($options['currencies'] as $currencySettings) {
    $quote = $provider->getQuote($currencySettings['currencyApiName'], $currencySettings['batch']);

    $lastQuote = null;
    if (!empty($lastQuotes[$currencySettings['currencyApiName']])) {
        $lastQuote = $lastQuotes[$currencySettings['currencyApiName']];
    } elseif (!empty($lastQuotes[$currencySettings['currencyApiName'].'_BRL'])) {
        $lastQuote = $lastQuotes[$currencySettings['currencyApiName'].'_BRL'];
    }

    $roundedQuote = round($quote, $currencySettings['precision']);
    $roundedLastQuote = round($lastQuote, $currencySettings['precision']);
    if ($roundedQuote === $roundedLastQuote) {
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
    $emoji = ($variance === INCREASE) ? ':(' : ':)';

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
    if (!empty($lastQuotes['daily'][$currencySettings['currencyApiName']]['closingValue'])) {
        $dailyChange = $quote / $lastQuotes['daily'][$currencySettings['currencyApiName']]['closingValue'];
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
        $status = str_replace('{data-hora}', '', $status);
    } else {
        $status = str_replace(
            '{variacao}',
            sprintf(
                'Variação %s %s%s%%',
                ($dailyChange > 1 ? '📈' : '📉'),
                ($dailyChange > 1 ? '+' : ''),
                number_format(($dailyChange - 1) * 100, 2, ',', '.')
            ),
            $status
        );
    }

    if (!empty($currencySettings['twitterApiKey'])) {
        $connection = new TwitterOAuth(
            $currencySettings['consumerApiKey'],
            $currencySettings['consumerApiSecret'],
            $currencySettings['twitterApiKey'],
            $currencySettings['twitterApiSecret']
        );
        $statusUpdate = $connection->post("statuses/update", array("status" => $status));

        if ($connection->getLastHttpCode() == 200) {
            $lastQuotes[$currencySettings['currencyApiName']] = $quote;
        } else {
            print sprintf("Erro: %s<br>%s", json_encode($connection->getLastBody()), PHP_EOL);
        }
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
