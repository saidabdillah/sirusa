<?php

namespace App\Notifications;

use App\Models\UserProfile;
use Illuminate\Notifications\Notification;

class DataVerificationChanged extends Notification
{
    public function __construct(
        public UserProfile $profile,
        public string $status,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'Status Verifikasi Profil',
            'message' => 'Verifikasi data profil Anda kini berstatus '.$this->translatedStatus().'.',
            'icon' => 'fa-clipboard-check',
            'url' => route('profile'),
        ];
    }

    private function translatedStatus(): string
    {
        return match ($this->status) {
            'setuju' => 'disetujui',
            'revisi' => 'perlu perbaikan',
            'tolak' => 'ditolak',
            default => 'menunggu',
        };
    }
}
