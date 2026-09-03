<?php

namespace App\Console\Commands;

use App\Models\Scholarship;
use App\Notifications\PengumumanBeasiswa;
use Illuminate\Console\Command;

class SendScholarshipAnnouncement extends Command
{
    protected $signature = 'announcements:send';

    protected $description = 'Kirim notifikasi pengumuman penerima ke semua pendaftar beasiswa yang window pengumumannya baru dimulai';

    public function handle(): int
    {
        $scholarships = Scholarship::whereNull('pengumuman_notified_at')->get();

        $sent = 0;

        foreach ($scholarships as $scholarship) {
            if (! $scholarship->isPengumumanAktif()) {
                continue;
            }

            $users = $scholarship->pendaftar()
                ->with('user')
                ->get()
                ->pluck('user')
                ->filter()
                ->unique('id');

            $users->each->notify(new PengumumanBeasiswa($scholarship));

            $scholarship->update(['pengumuman_notified_at' => now()]);

            $sent += $users->count();
        }

        $this->info("Notifikasi pengumuman dikirim ke {$sent} pengguna.");

        return self::SUCCESS;
    }
}
