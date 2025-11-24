<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ApplicationValidated extends Notification
{
    use Queueable;

    protected $applicant;

    public function __construct($applicant)
    {
        $this->applicant = $applicant;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Application Validated - Local 39 Apprenticeship')
            ->greeting('Hello ' . $this->applicant->first_name . '!')
            ->line('Great news! Your application has been validated.')
            ->line('**Confirmation Number:** ' . $this->applicant->confirmation_number)
            ->line('**Validation Date:** ' . $this->applicant->validation_timestamp->format('F j, Y g:i A'))
            ->line('')
            ->line('You are now eligible to take the apprenticeship exam.')
            ->line('Exam scheduling information will be sent to you soon.')
            ->line('')
            ->line('If you have any questions, please contact us.')
            ->salutation('Best regards, Local 39 Stationary Engineers');
    }
}
