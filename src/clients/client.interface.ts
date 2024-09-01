export interface SocialMediaClient {
    sendMessage(message: string): Promise<string>
}