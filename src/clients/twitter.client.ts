import { TwitterApi, TwitterApiTokens } from "twitter-api-v2";
import { SocialMediaClient } from "./client.interface";

export class TwitterClient implements SocialMediaClient {
    private client: TwitterApi;
    constructor(settings: TwitterApiTokens) {
        this.client = new TwitterApi(settings);
    }

    async sendMessage(message: string): Promise<string> {
        try {
            const status = await this.client.v2.tweet(message);
            return status.data.text;
        } catch (error) {
            console.error('Error posting to Twitter:', error);
        }
    }
}