<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class UserActivated extends Notification
{
    public function __construct(
        public string $username,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'Akun Telah Diaktifkan',
            'message' => 'Akun Anda telah diaktifkan oleh admin. Silakan masuk menggunakan NIK dan kata sandi awal (NIM) Anda.',
            'icon' => 'fa-user-check',
            'url' => route('login'),
        ];
    }
}
