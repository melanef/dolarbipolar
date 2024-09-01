import axios from "axios";

export async function getQuote(currencyApiName: string, batch: number, apiKey: string) {
    try {
        const response = await axios.get(`https://api.currencyconverterapi.com/${currencyApiName}?batch=${batch}&key=${apiKey}`);
        return response.data.quote;
    } catch (error) {
        console.error('Error fetching currency data:', error);
        return null;
    }
}

export function renderDailyChange(change: number, absoluteChange: number) {
    change = (change - 1) * 100;
    const signal = change < 0 ? '-' : '+';
    change = Math.abs(change);
    return `${signal}${change.toFixed(2).replace('.', ',')}% (R$ ${absoluteChange.toFixed(2).replace('.', ',')})`;
}