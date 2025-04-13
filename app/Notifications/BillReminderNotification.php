<?php

namespace App\Notifications;

use App\Models\BillReminder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BillReminderNotification extends Notification
{
    use Queueable;

    public $reminder;

    /**
     * Create a new notification instance.
     */
    public function __construct(BillReminder $reminder)
    {
        $this->reminder = $reminder;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail','database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Upcoming Bill Reminder')
            ->line("You have a {$this->reminder->bill_type} bill of Rs. {$this->reminder->amount}")
            ->line('Due Date: ' . $this->reminder->due_date)
            ->line('Please make sure to pay on time.')
            ->line('Paynest');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'bill_type' => $this->reminder->bill_type ?? 'Unknown Bill Type',
            'amount' => $this->reminder->amount ?? 0,
            'due_date' => $this->reminder->due_date ?? 'Not Specified',
        ];
    }
    
}
