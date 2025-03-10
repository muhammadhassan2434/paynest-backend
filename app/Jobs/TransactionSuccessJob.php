<?php

namespace App\Jobs;

use App\Mail\TransactionSuccessmail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class TransactionSuccessJob implements ShouldQueue
{
    use Queueable;
    use Dispatchable, InteractsWithQueue, SerializesModels;

    public $sender_first_name;
    public $sender_email;
    public  $sender_phone;
    public  $receiver_name;
    public  $transaction_amount;
    /**
     * Create a new job instance.
     */
    public function __construct($sender_first_name,$sender_email,$sender_phone,$receiver_name,$transaction_amount)
    {
        $this->sender_first_name = $sender_first_name;
        $this->sender_email = $sender_email;
        $this->sender_phone = $sender_phone;
        $this->receiver_name = $receiver_name;
        $this->transaction_amount = $transaction_amount;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Mail::to($this->sender_email)->send(new TransactionSuccessmail($this->sender_first_name,$this->sender_email,$this->sender_phone,$this->receiver_name,$this->transaction_amount));
    }
}
