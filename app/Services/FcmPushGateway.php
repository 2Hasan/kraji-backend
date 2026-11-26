<?php

namespace App\Services;

use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Contract\Messaging;

/** الإرسال الحقيقي عبر Firebase Cloud Messaging. */
class FcmPushGateway implements PushGateway
{
    public function __construct(private Messaging $messaging)
    {
    }

    public function send(string $deviceToken, string $title, string $body): void
    {
        $this->messaging->send(CloudMessage::fromArray([
            'token' => $deviceToken,
            'notification' => ['title' => $title, 'body' => $body],
        ]));
    }
}
