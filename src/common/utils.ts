import fs from 'fs';
import path from 'path';
import { CurrencySettings } from './types';
import { Changes } from './enums';
import { format } from 'date-fns';
import { renderDailyChange } from '../currency/currency.service';

function resolveParentDirectoryFile(file: string) {
    return path.resolve(__dirname, '../..', file);
}

export function loadJson<T = any>(file: string): T {
    const parentDirectoryFile = resolveParentDirectoryFile(file);
    return JSON.parse(fs.readFileSync(parentDirectoryFile, 'utf8'));
}

export function saveJson<T = string>(file: string, data: Record<string, T>) {
    const parentDirectoryFile = resolveParentDirectoryFile(file);
    fs.writeFileSync(parentDirectoryFile, JSON.stringify(data, null, 4));
}

export function formatStatus(template: string, options: {
    currencySettings: CurrencySettings, 
    roundedQuote: number, 
    dailyChange: number, 
    dailyChangeAbsolute: number, 
    now: Date, 
    variance: Changes
}) {
    const varianceText = options.dailyChange ? `Variação ${options.dailyChange > 1 ? '📈' : '📉'} ${renderDailyChange(options.dailyChange, options.dailyChangeAbsolute)}` : '';
    return template
        .replace('{name}', options.currencySettings.name)
        .replace('{subiu/caiu}', options.variance)
        .replace('{emoji}', options.variance === Changes.INCREASE ? '☹️️' : '☺️')
        .replace('{cotacao}', `${options.roundedQuote.toFixed(options.currencySettings.precision).replace('.', ',')}${options.currencySettings.batch == 1 ? '' : ` (lote de ${options.currencySettings.batch})`}`)
        .replace('{data-hora}', format(options.now, 'HH:mm'))
        .replace('{variacao}', varianceText);
}