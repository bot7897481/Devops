<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PreRegistrationConfirmation extends Notification
{
    use Queueable;

    protected $preRegistration;

    public function __construct($preRegistration)
    {
        $this->preRegistration = $preRegistration;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Pre-Registration Confirmation - Local 39 Apprenticeship')
            ->greeting('Hello ' . $this->preRegistration->first_name . '!')
            ->line('Thank you for pre-registering for the Local 39 Stationary Engineers Apprenticeship Program.')
            ->line('Your reference number is: **' . $this->preRegistration->reference_number . '**')
            ->line('You will receive an email notification 48 hours before applications open, and another email when they officially open.')
            ->line('Please keep this reference number for your records.')
            ->line('If you have any questions, please contact us.')
            ->salutation('Best regards, Local 39 Stationary Engineers');
    }
}
