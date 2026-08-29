<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VerifyEmailChange extends Notification
{
    public function __construct(
        public string $otp,
        public int $expiresInMinutes = 5,
        public ?string $recipientEmail = null
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Kode Verifikasi Ganti Email - SIRUSA')
            ->greeting('Halo '.($this->recipientEmail ?? 'Pengguna').',')
            ->line('Anda meminta untuk mengganti alamat email akun Anda.')
            ->line('Berikut adalah kode OTP Anda (berlaku **'.$this->expiresInMinutes.' menit**):')
            ->line('**'.$this->otp.'**')
            ->line('Jika Anda tidak meminta penggantian email, abaikan email ini.')
            ->salutation('Salam, Tim SIRUSA');
    }
}
