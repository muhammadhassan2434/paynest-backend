<?php

namespace App\Services;

use Twilio\Rest\Client;

class TwilioService
{
    protected $twilio;

    public function __construct()
{
    $sid = env('TWILIO_SID');
    $token = env('TWILIO_AUTH_TOKEN');

    // dd($sid, $token); // Debugging

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
