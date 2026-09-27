<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class UserActivated extends Notification
{
    public function __construct(
        public string $username,
        /**
         * Credential yang boleh dipakai akun ini untuk masuk, sudah disesuaikan
         * dengan role-nya oleh caller. Dulu notifikasi ini selalu menyebut
         * "masuk menggunakan NIK dan kata sandi awal (NIM)" -- salah dua-duanya:
         * akun staf tidak punya NIK, dan kata sandi awal yang dibuat admin lewat
         * "Reset Password" adalah `12345678`, bukan NIM.
         */
        public string $loginCredential = 'username',
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'Akun Telah Diaktifkan',
            'message' => "Akun '{$this->username}' telah diaktifkan oleh admin. Silakan masuk menggunakan {$this->loginCredential} dan kata sandi yang ditetapkan admin.",
            'icon' => 'fa-user-check',
            'url' => route('login'),
        ];
    }
}
