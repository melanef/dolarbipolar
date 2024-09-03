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
use GuzzleHttp\Psr7\Request;

const FILE_OPTIONS = './options.json';
const FILE_HISTORY = './history.json';
const INCREASE = 'subiu';
const DECREASE = 'caiu';
const BLUESKY_API_URL = 'https://bsky.social/xrpc';

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

    if (!empty($currencySettings['twitterApiKey'])) {
        $connection = new TwitterOAuth(
            $currencySettings['consumerApiKey'],
            $currencySettings['consumerApiSecret'],
            $currencySettings['twitterApiKey'],
            $currencySettings['twitterApiSecret']
        );
        $statusUpdate = $connection->post("statuses/update", ['status' => $status]);

        if ($connection->getLastHttpCode() == 200) {
            $lastQuotes[$currencySettings['currencyApiName']] = $quote;
        } else {
            print sprintf("Erro: %s<br>%s", json_encode($connection->getLastBody()), PHP_EOL);
        }
    }

    if (!empty($currencySettings['blueskyUser']) && !empty($currencySettings['blueskyPassword'])) {
        $httpClient = new Client();
        $authData = json_encode([
            "identifier" => $currencySettings['blueskyUser'],
            "password" => $currencySettings['blueskyPassword']
        ]);
        $requestToken = new Request(
            'POST', 
            BLUESKY_API_URL . '/com.atproto.server.createSession',
            ['Accept' => 'application/json'],
            $authData
        );

        $response = $httpClient->sendRequest($requestToken);
        $tokenPayload = json_decode($response->getBody()->getContents(), true);

        $postData = [
            'repo' => $tokenPayload['did'],
            'collection' => "app.bsky.feed.post",
            'record' => [
                '$type' => 'app.bsky.feed.post',
                'text' => $status,
                'createdAt' => (new DateTime())->format(DateTime::ATOM),
            ]
        ];
        $requestPost = new Request(
            'POST', 
            BLUESKY_API_URL . '/com.atproto.server.createRecord',
            ['Authorization' => "Bearer ". $tokenPayload['token']],
            $postData
        );
       $httpClient->sendRequest($requestToken);
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
