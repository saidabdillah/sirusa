<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class NewUserRegistered extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $email,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'Pengguna Baru Mendaftar',
            'message' => 'Akun baru terdaftar ('.$this->email.'). Akun menunggu aktivasi Anda.',
            'icon' => 'fa-user-plus',
            'url' => route('admin.pengguna.index'),
        ];
    }
}
