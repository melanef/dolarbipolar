import { format, fromZonedTime } from 'date-fns-tz';
import { Changes } from './common/enums';
import { Options } from './common/types';
import { formatStatus, loadJson, saveJson } from './common/utils';
import { getQuote } from './currency/currency.service';
import { sendUpdates } from './currency/social.service';

const now = fromZonedTime(new Date(), 'America/Sao_Paulo');
const FILE_OPTIONS = 'options.json';
const FILE_HISTORY = 'history.json';
const options = loadJson<Options>(FILE_OPTIONS);
const quoteHistory = loadJson(FILE_HISTORY);

async function sendCurrencyToSocialNetwork(){
    for (const currencySettings of options.currencies) {
        const quote = await getQuote(
            currencySettings.currencyApiName, 
            currencySettings.batch, 
            options.keys.currencyconverterapi,
        );

        const lastQuoteFromHistory: number | null = quoteHistory[currencySettings.currencyApiName] || quoteHistory[`${currencySettings.currencyApiName}_BRL`] || null;
        const roundedQuote = parseFloat(quote.toFixed(currencySettings.precision));
        const roundedLastQuote = lastQuoteFromHistory ? parseFloat(lastQuoteFromHistory.toFixed(currencySettings.precision)) : null;

        if (roundedQuote === roundedLastQuote) {
            console.log(`${format(now, 'yyyy-MM-dd HH:mm:ss')} - ${currencySettings.currencyApiName} - Sem alteração - ${lastQuoteFromHistory} (${roundedLastQuote}) - ${quote} (${roundedQuote})`);
            continue;
        }

        const variance = quote > lastQuoteFromHistory ? Changes.INCREASE : Changes.DECREASE;
        const day = format(new Date(), 'yyyyMMdd');
        if (!quoteHistory.daily || quoteHistory.daily[currencySettings.currencyApiName]?.date !== day) {
            quoteHistory.daily = quoteHistory.daily || {};
            quoteHistory.daily[currencySettings.currencyApiName] = {
                date: day,
                value: quote,
                closingValue: lastQuoteFromHistory
            };
        } else {
            quoteHistory.daily[currencySettings.currencyApiName].value = quote;
        }

        let dailyChange = null;
        let dailyChangeAbsolute = 0;
        if (quoteHistory.daily[currencySettings.currencyApiName]?.closingValue) {
            dailyChange = quote / quoteHistory.daily[currencySettings.currencyApiName].closingValue;
            dailyChangeAbsolute = Math.abs(quote - quoteHistory.daily[currencySettings.currencyApiName].closingValue);
        }

        const status = formatStatus(options.twitterStatusFormat, {
            currencySettings, 
            roundedQuote, 
            dailyChange, 
            dailyChangeAbsolute, 
            now, 
            variance
        });
        await sendUpdates(currencySettings, status);

        quoteHistory[currencySettings.currencyApiName] = quote;
        console.log(`${format(now, 'yyyy-MM-dd HH:mm:ss')} - ${currencySettings.currencyApiName} - Corpo: "${status}"`);
    }

    saveJson(FILE_HISTORY, quoteHistory);
}

(async () => await sendCurrencyToSocialNetwork() )();