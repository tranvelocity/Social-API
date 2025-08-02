<?php

namespace Modules\Notification\Traits;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Notification;

trait NotificationTrait
{
    public function sendSlackNotification($message, $attachmentContent = null): void
    {
        $slackNotification = new \Modules\Notification\Slack\SlackNotification($message, $attachmentContent);
        Notification::route('slack', Config::get('notification.slack.webhook_url'))->notify($slackNotification);
    }
}
