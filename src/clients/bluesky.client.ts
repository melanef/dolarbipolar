import { SocialMediaClient } from "./client.interface";
import { AtpAgentLoginOpts, BskyAgent, RichText } from "@atproto/api";

export class BlueskyClient implements SocialMediaClient {
    private client: BskyAgent;
    constructor(private readonly settings: AtpAgentLoginOpts) {
        this.client = new BskyAgent({
            service: 'https://bsky.social',
        })
    }

    private async authenticate() {
        await this.client.login(this.settings).then(() => {
            console.log("[CLIENT] :: Bluesky connected")
        })
    }
    async sendMessage(message: string): Promise<string> {
        await this.authenticate();
        const rt = new RichText({ text: message })
        await rt.detectFacets(this.client);
        const postRecord = {
            $type: 'app.bsky.feed.post',
            text: rt.text,
            facets: rt.facets,
            createdAt: new Date().toISOString(),
        }
        try {
            await this.client.post(postRecord);
        } catch (error) {
            console.error('Error posting to Bluesky:', error);
        } finally {
            return rt.text;
        }
    }
}