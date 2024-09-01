export type Options = {
    keys: {
        currencyconverterapi: string
    },
    currencies: CurrencySettings[],
    twitterStatusFormat: string,
}

export type CurrencySettings = {
    name: string,
    currencyApiName: string,
    batch: number,
    precision: number,
    twitterApiKey?: string,
    twitterApiSecret: string,
    consumerApiKey: string,
    consumerApiSecret: string,
    blueskyUser?: string,
    blueskyPassword?: string,
}