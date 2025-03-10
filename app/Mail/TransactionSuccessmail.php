<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TransactionSuccessmail extends Mailable
{
    use Queueable, SerializesModels;

    public $sender_first_name;
    public $sender_email;
    public $sender_phone;
    public $receiver_name;
    public $transaction_amount;
    /**
     * Create a new message instance.
     */
    public function __construct($sender_first_name,$sender_email, $sender_phone,$receiver_name, $transaction_amount)
    {
        $this->sender_first_name = $sender_first_name;
        $this->sender_email = $sender_email;
        $this->sender_phone = $sender_phone;
        $this->receiver_name = $receiver_name;
        $this->transaction_amount = $transaction_amount;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Transaction Successmail',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'Mail.transaction_success',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
