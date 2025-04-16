<?php

namespace App\Services;

use Twilio\Rest\Client;

class TwilioService
{
    protected $twilio;

    public function __construct()
{
    $sid = config('services.twilio.sid');
    $token = config('services.twilio.token');

    $this->twilio = new Client($sid, $token);
}

    public function sendMessage($to, $message)
    {
        $from = env('TWILIO_FROM');

        $message = $this->twilio->messages->create($to, [
            'from' => $from,
            'body' => $message
        ]);

        return $message->sid;
    }
}
