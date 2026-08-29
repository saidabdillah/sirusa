<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OtpPasswordReset extends Notification
{
    public function __construct(
        public string $otp,
        public int $expiresInMinutes = 5
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Kode OTP Reset Kata Sandi - SIRUSA')
            ->greeting('Halo '.($notifiable->nama ?? 'Pengguna').',')
            ->line('Kami menerima permintaan untuk mereset kata sandi akun Anda.')
            ->line('Berikut adalah kode OTP Anda (berlaku **'.$this->expiresInMinutes.' menit**):')
            ->line('**'.$this->otp.'**')
            ->line('Jika Anda tidak meminta reset kata sandi, abaikan email ini.')
            ->salutation('Salam, Tim SIRUSA');
    }
}
