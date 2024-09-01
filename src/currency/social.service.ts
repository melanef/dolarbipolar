import { BlueskyClient } from "../clients/bluesky.client";
import { TwitterClient } from "../clients/twitter.client";
import { CurrencySettings } from "../common/types";

export async function sendUpdates(currencySettings: CurrencySettings, status: string) {
    if (currencySettings.twitterApiKey) {
        const twitterClient = new TwitterClient({
            appKey: currencySettings.consumerApiKey,
            appSecret: currencySettings.consumerApiSecret,
            accessToken: currencySettings.twitterApiKey,
            accessSecret: currencySettings.twitterApiSecret
        });

        await twitterClient.sendMessage(status);
    }

    if (currencySettings.blueskyUser && currencySettings.blueskyPassword) {
        const blueskyClient = new BlueskyClient({
            identifier: currencySettings.blueskyUser,
            password: currencySettings.blueskyPassword,
        });
        await blueskyClient.sendMessage(status);
    }
}