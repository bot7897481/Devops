<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ApplicationSubmitted extends Notification
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
            ->subject('Application Submitted Successfully - Local 39 Apprenticeship')
            ->greeting('Hello ' . $this->applicant->first_name . '!')
            ->line('Your application has been submitted successfully!')
            ->line('**Confirmation Number:** ' . $this->applicant->confirmation_number)
            ->line('**Submission Time:** ' . $this->applicant->application_timestamp->format('F j, Y g:i A'))
            ->line('')
            ->line('**Next Steps:**')
            ->line('1. Bring your physical documents to our office for in-person validation')
            ->line('2. You will need to bring:')
            ->line('   - Government-issued photo ID (Driver\'s License, State ID, or Passport)')
            ->line('   - High school diploma or GED certificate')
            ->line('   - Any other documents you uploaded')
            ->line('')
            ->line('**Validation Location:** [Address will be provided]')
            ->line('**Validation Dates:** [Dates will be provided]')
            ->line('')
            ->line('Please keep this confirmation number for your records.')
            ->salutation('Best regards, Local 39 Stationary Engineers');
    }
}
