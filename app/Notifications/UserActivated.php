<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserActivated extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $username,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Akun Anda Telah Diaktifkan - SIRUSA')
            ->greeting('Halo '.$this->username.',')
            ->line('Akun Anda telah diaktifkan oleh admin.')
            ->line('Anda sekarang dapat masuk menggunakan email dan kata sandi yang terdaftar.')
            ->action('Masuk Sekarang', route('login'))
            ->salutation('Salam, Tim SIRUSA');
    }
}
