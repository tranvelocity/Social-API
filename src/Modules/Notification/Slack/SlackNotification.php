<?php

namespace Modules\Notification\Slack;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\SlackMessage;
use Illuminate\Support\Facades\Config;

class SlackNotification extends Notification
{
    use Queueable;

    private string $message;
    private mixed $attachmentContent;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($message, $attachmentContent = null)
    {
        $this->message = $message;
        $this->attachmentContent = $attachmentContent;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(): array
    {
        return ['slack'];
    }

    public function toSlack(): SlackMessage
    {
        $slackMessage = (new SlackMessage())
            ->from(Config::get('notification.slack.app_name'), Config::get('notification.slack.icons.exclamation'))
            ->to(Config::get('notification.slack.channel.' . env('APP_ENV', 'local')) ?: Config::get('notification.slack.channel.default'))
            ->content($this->message);

        if ($this->attachmentContent !== null) {
            $slackMessage->attachment(function ($attachment) {
                $attachment
                    ->title('Description:')
                    ->content($this->attachmentContent)
                    ->markdown(['text']);
            });
        }

        return $slackMessage;
    }
}
