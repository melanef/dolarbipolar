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
use GuzzleHttp\Client;

const API_URL = 'https://free.currconv.com/api/v7/convert?q=%s&compact=ultra&apiKey=%s';
const FILE_OPTIONS = './options.json';
const FILE_HISTORY = './history.json';
const INCREASE = 'subiu';
const DECREASE = 'caiu';

$access_token = 'ACCESS_TOKEN';
$access_token_secret = 'ACCESS_TOKEN_SECRET';

$now = new DateTime('now', new DateTimeZone('America/Sao_Paulo'));
$options = json_decode(file_get_contents(FILE_OPTIONS), true);
$lastQuotes = json_decode(file_get_contents(FILE_HISTORY), true);

$client = new Client();
foreach ($options['currencies'] as $currencySettings) {
    $response = $client->get(
        sprintf(API_URL, $currencySettings['currencyApiName'], $options['currencyApiKey'])
    );

    $payload = json_decode($response->getBody()->getContents(), true);
    $quote = $payload[$currencySettings['currencyApiName']];

    $lastQuote = null;
    if (!empty($lastQuotes[$currencySettings['currencyApiName']])) {
        $lastQuote = $lastQuotes[$currencySettings['currencyApiName']];
    }

    if ($quote === $lastQuote) {
        continue;
    }

    $variance = ($quote > $lastQuote) ? INCREASE : DECREASE;
    $emoji = ($variance === INCREASE) ? ':(' : ':)';

    $status = $options['twitterStatusFormat'];
    $status = str_replace('{name}', $currencySettings['name'], $status);
    $status = str_replace('{subiu/caiu}', $variance, $status);
    $status = str_replace('{emoji}', $emoji, $status);
    $status = str_replace('{cotacao}', number_format($quote, 2, ',',  '.'), $status);
    $status = str_replace('{data-hora}', $now->format('H:i'), $status);

    if (!empty($currencySettings['twitterApiKey'])) {
        $connection = new TwitterOAuth('CONSUMER_KEY', 'CONSUMER_SECRET', $access_token, $access_token_secret);
        $statusUpdate = $connection->post("statuses/update", array("status" => $status));

        if ($connection->getLastHttpCode() == 200) {
            $lastQuotes[$currencySettings['currencyApiName']] = $quote;
        }
    }

    print sprintf(
        '%s - %s - %s%s',
        $now->format('Y-m-d H:i:s'),
        $currencySettings['currencyApiName'],
        $status,
        PHP_EOL
    );
}

file_put_contents(FILE_HISTORY, json_encode($lastQuotes));