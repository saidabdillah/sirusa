<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserProfile extends Model
{
    use HasFactory;

    protected $table = 'profil_pengguna';

    /**
     * Default saat baris profil baru dibuat di memori (mis. `updateOrCreate`
     * yang belum menyentuh kolom status). Kolomnya sendiri sudah punya
     * `default('menunggu')` di migrasi, jadi ini cuma menutup jalur pembuatan
     * baris yang tidak lewat DEFAULT database.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'verif_catpil' => 'menunggu',
        'verif_kampus' => 'menunggu',
        'verif_kesra' => 'menunggu',
    ];

    protected $fillable = [
        'user_id',
        'nama_lengkap',
        'nik',
        'nim',
        'no_kk',
        'tempat_lahir',
        'tanggal_lahir',
        'jenis_kelamin',
        'agama',
        'telepon',
        'alamat',
        'provinsi',
        'kabupaten_kota',
        'kecamatan',
        'desa_kelurahan',
        'foto_profil',
        'ikut_kk',
        'kk_ikut_famili_lain',
        'nama_ayah',
        'pekerjaan_ayah',
        'nik_ayah',
        'nama_ibu',
        'pekerjaan_ibu',
        'nik_ibu',
        'nama_wali',
        'pekerjaan_wali',
        'hubungan_wali',
        'nik_wali',
        'prodi_id',
        'jenjang',
        'tahun_masuk',
        'kepala_keluarga',
        'ipk',
        'semester',
        'ukt',
        'desil',
        'dokumen_ktp',
        'dokumen_kk',
        'dokumen_desil',
        'dokumen_sktm',
        'dokumen_prestasi',
        'dokumen_transkrip',
        'dokumen_surat_aktif',
        'dokumen_surat_pernyataan',
        'dokumen_pddikti',
        'dokumen_bukti_ukt',
        'ktp_ayah',
        'ktp_ibu',
        'ktp_wali',
        'kk_wali',
        'verif_catpil',
        'verif_kampus',
        'verif_kesra',
        'catatan_catpil',
        'catatan_kampus',
        'catatan_kesra',
        'berdomisili_sejak',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_lahir' => 'date',
            'kk_ikut_famili_lain' => 'boolean',
            'kepala_keluarga' => 'boolean',
            'tahun_masuk' => 'integer',
            'dokumen_prestasi' => 'array',
            // Diisi petugas Catpil saat memutuskan `setuju`, bukan oleh
            // mahasiswa. Dipakai menghitung lama berdomisili di antrean Kesra.
            'berdomisili_sejak' => 'date',
            // Tanpa cast, `decimal(10,2)` dibaca sebagai string "1500000.00".
            // Input UKT dirender ke Cleave yang membuang titik tetapi
            // mempertahankan "00", jadi 1500000 -> 150000000 (x100) setiap
            // kali form dibuka lalu disimpan ulang. `decimal:0` memangkas nilai
            // ke bilangan bulat rupiah sebelum masuk ke Blade.
            'ukt' => 'decimal:0',
        ];
    }

    public static function verifStages(): array
    {
        return [
            'catpil' => ['verif_catpil', 'catatan_catpil'],
            'kampus' => ['verif_kampus', 'catatan_kampus'],
            'kesra' => ['verif_kesra', 'catatan_kesra'],
        ];
    }

    /**
     * Prefix route tiap tahap verifikasi. Tidak seragam — Verifikasi Kampus
     * memakai `admin.kampusverif`, bukan `admin.kampus` — jadi pemetaannya
     * harus satu sumber kebenaran, bukan di-hardcode di tiap controller.
     */
    public static function verifRoutePrefixes(): array
    {
        return [
            'catpil' => 'catpil',
            'kampus' => 'kampusverif',
            'kesra' => 'kesra',
        ];
    }

    public static function verifStageOrder(): array
    {
        return array_keys(self::verifStages());
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function prodi(): BelongsTo
    {
        return $this->belongsTo(Prodi::class);
    }

    public function getAlamatLengkapAttribute(): string
    {
        $parts = array_filter([
            $this->alamat,
            $this->desa_kelurahan,
            $this->kecamatan ? 'Kec. '.$this->kecamatan : null,
            $this->kabupaten_kota,
            $this->provinsi,
        ]);

        return implode(', ', $parts);
    }

    /**
     * Data orang tua/famili lain yang dipilih mahasiswa lewat field "Kartu
     * Keluarga".
     *
     * Satu sumber untuk nama, NIK, dan pekerjaan sekaligus, supaya tidak
     * mungkin ada tampilan yang menampilkan nama ayah tapi NIK ibu.
     * Pemanggil yang hanya butuh satu bagian tetap bisa ambil dari array ini
     * alih-alih menulis `match ($this->ikut_kk)` sendiri-sendiri.
     *
     * `default` memakai ayah supaya profil lama yang `ikut_kk`-nya kosong tetap
     * punya orang tua yang bisa ditampilkan, sama seperti bawaan form.
     *
     * @return array{label: string, nama: ?string, nik: ?string, pekerjaan: ?string}
     */
    public function orangTuaDipilih(): array
    {
        return match ($this->ikut_kk) {
            'famili_lain' => [
                'label' => 'Famili Lain',
                'nama' => $this->nama_wali,
                'nik' => $this->nik_wali,
                'pekerjaan' => $this->pekerjaan_wali,
            ],
            'ibu' => [
                'label' => 'Ibu',
                'nama' => $this->nama_ibu,
                'nik' => $this->nik_ibu,
                'pekerjaan' => $this->pekerjaan_ibu,
            ],
            default => [
                'label' => 'Ayah',
                'nama' => $this->nama_ayah,
                'nik' => $this->nik_ayah,
                'pekerjaan' => $this->pekerjaan_ayah,
            ],
        };
    }

    /**
     * Dokumen KRS/KHS per semester milik mahasiswa ini.
     *
     * @return HasMany<DokumenAkademik>
     */
    public function dokumenAkademik(): HasMany
    {
        return $this->hasMany(DokumenAkademik::class, 'user_id', 'user_id')
            ->orderBy('jenis')
            ->orderBy('semester');
    }

    /**
     * Lama berdomisili dalam bentuk "2 tahun 3 bulan", dihitung dari
     * `berdomisili_sejak` sampai hari ini.
     *
     * SELALU dihitung ulang saat dipanggil, tidak pernah disimpan sebagai
     * kolom: nilai statis akan salah begitu bulan berganti. `null` berarti
     * tanggalnya belum diisi -- pemanggil menampilkan "Belum ditentukan".
     *
     * Perhitungannya kalender, bukan "selisih hari dibagi 30": bulan yang
     * panjangnya beda-beda membuat cara itu meleset beberapa hari setiap bulan.
     */
    public function lamaBerdomisili(): ?string
    {
        if (! $this->berdomisili_sejak) {
            return null;
        }

        // Selalu pakai awal hari supaya jam pada data tidak membuat
        // perhitungannya bergeser satu hari.
        $mulai = $this->berdomisili_sejak->copy()->startOfDay();
        $kini = now()->startOfDay();

        // Tanggal di masa depan tidak dihitung sebagai durasi negatif.
        if ($mulai->greaterThan($kini)) {
            return null;
        }

        $selisih = $mulai->diff($kini);

        $bagian = [];

        if ($selisih->y > 0) {
            $bagian[] = $selisih->y.' tahun';
        }

        if ($selisih->m > 0) {
            $bagian[] = $selisih->m.' bulan';
        }

        // Kurang dari sebulan tetap punya jawaban yang lebih berguna daripada
        // kalimat kosong.
        if ($bagian === []) {
            return $selisih->d > 0 ? $selisih->d.' hari' : 'Baru saja';
        }

        return implode(' ', $bagian);
    }

    /**
     * Lama berdomisili siap tampil, termasuk teks pengganti saat tanggalnya
     * belum ada.
     */
    public function lamaBerdomisiliLabel(): string
    {
        return $this->lamaBerdomisili() ?? 'Belum ditentukan';
    }

    /**
     * Semua tahap sudah dituntaskan, termasuk tahap Kesra.
     *
     * Perlu diingat bahwa Kesra tidak memverifikasi profil: yang disimpulkan
     *Kesra adalah keputusan terhadap satu pendaftaran, dan keputusan itu bisa
     * berupa penolakan. Jadi nilai `true` berarti "prosesnya selesai", bukan
     * "mahasiswa ini layak diberi beasiswa". Jangan dipakai sebagai syarat
     * kelayakan.
     */
    public function isVerified(): bool
    {
        return $this->verif_catpil === 'setuju'
            && $this->verif_kampus === 'setuju'
            && $this->verif_kesra === 'setuju';
    }

    public function verifStatus(): string
    {
        if ($this->isVerified()) {
            return 'terverifikasi';
        }

        foreach (self::verifStages() as $stage) {
            if ($this->{$stage[0]} === 'revisi') {
                return 'revisi';
            }
        }

        foreach (self::verifStages() as $stage) {
            if ($this->{$stage[0]} === 'tolak') {
                return 'tolak';
            }
        }

        return 'proses';
    }

    public function verifStageLabels(): array
    {
        return [
            'catpil' => 'Verifikasi Catpil',
            'kampus' => 'Verifikasi Kampus',
            'kesra' => 'Verifikasi Kesra',
        ];
    }

    /**
     * Keputusan pada satu tahap, beserta label dan warna badge siap tampil.
     *
     * @return array{status: string, label: string, badge: string, decided: bool}
     */
    public function verifStageDecision(string $stage): array
    {
        $statusField = self::verifStages()[$stage][0] ?? null;
        $status = $statusField ? $this->{$statusField} : 'menunggu';

        return self::decisionForStatus($status);
    }

    /**
     * Pemetaan status verifikasi mentah -> label/warna tampilan.
     *
     * Satu sumber untuk `verifStageDecision()` (tahap profil) dan
     * `User::kesraDecision()` (keputusan Kesra yang diturunkan dari baris
     * `pendaftar`), supaya istilah dan warna tidak pernah berbeda antar tampilan.
     *
     * @return array{status: string, label: string, badge: string, decided: bool}
     */
    public static function decisionForStatus(string $status): array
    {
        return [
            'status' => $status,
            'label' => match ($status) {
                'setuju' => 'Setuju',
                'revisi' => 'Revisi',
                'tolak' => 'Tolak',
                default => 'Menunggu',
            },
            'badge' => match ($status) {
                'setuju' => 'success',
                'revisi' => 'info',
                'tolak' => 'danger',
                default => 'warning',
            },
            'decided' => $status !== 'menunggu',
        ];
    }

    /**
     * Urutan antrean: yang paling butuh tindakan mahasiswa dulu, lalu yang
     * sudah diputuskan dipinda ke bawah agar mudah dibedakan dari yang baru.
     */
    public function verifQueueRank(string $stage): int
    {
        return match ($this->{self::verifStages()[$stage][0]}) {
            'revisi' => 0,
            'menunggu' => 1,
            'tolak' => 2,
            default => 3,
        };
    }

    /**
     * Apakah tahap ini masih boleh dikerjakan (ditulis) oleh admin tahap tersebut.
     *
     * Dua pengecualian yang berlaku:
     *
     * - `kampus` butuh pendaftaran, karena memang tidak ada yang bisa diperiksa
     *   sebelum ada pendaftar. Barisnya juga DIKUNCI begitu Kesra memutuskan
     *   (`verif_kesra = 'setuju'`), supaya keputusan yang sudah ada tidak lagi
     *   bisa diubah lewat jalur profil. Baris yang terkunci tetap tampil di
     *   antrean (lihat `inVerifQueue()`) sebagai read-only, bukan disembunyikan.
     * - `kesra` mensyaratkan verifikasi Catpil DAN Kampus sama-sama `setuju`
     *   (permintaan pengguna). Sebelumnya hanya Kampus yang disyaratkan; aturan
     *   itu diperketat supaya permohonan tidak bisa diputuskan Kesra sebelum
     *   data kependudukan (Catpil) dan data akademik (Kampus) dua-duanya
     *   terverifikasi.
     *
     * Keputusan tahap ini sendiri tidak dikunci oleh tahap lain: admin masih
     * bebas mengubah keputusan yang sudah dibuat selama aturan di atas terpenuhi.
     */
    public function canVerifStage(string $stage): bool
    {
        return match ($stage) {
            'catpil' => true,
            'kampus' => $this->verif_kesra !== 'setuju'
                && (bool) $this->user?->applicants()->exists(),
            // Catpil DAN Kampus harus `setuju`. `tolak` maupun `revisi` di salah
            // satunya berarti belum memenuhi syarat masuk antrean Kesra.
            'kesra' => $this->lolosGerbangKesra(),
            default => false,
        };
    }

    /**
     * Syarat masuk antrean/putusan Kesra: Catpil dan Kampus sama-sama `setuju`.
     *
     * Satu sumber kebenaran untuk `canVerifStage()` dan
     * `VerifikasiAntrean::pendaftarQuery()`, supaya halaman, angka dasbor, dan
     * unduhan tidak pernah punya syarat yang berbeda.
     */
    public function lolosGerbangKesra(): bool
    {
        return $this->verif_catpil === 'setuju' && $this->verif_kampus === 'setuju';
    }

    /**
     * Apakah baris ini tampil di antrean tahap tersebut (bisa dibuka read-only).
     *
     * Sama dengan `canVerifStage()` kecuali untuk `kampus`: begitu Kesra
     * memutuskan, keputusannya dikunci (`canVerifStage()` false) tapi barisnya
     * TETAP tampil supaya Kampus masih bisa membuka dan meninjau datanya. Karena
     * itu `VerifikasiAntrean::antrean()` memakai metode ini -- angka dasbor,
     * tabel antrean, dan export tidak boleh punya cakupan yang berbeda.
     */
    public function inVerifQueue(string $stage): bool
    {
        return match ($stage) {
            'kampus' => (bool) $this->user?->applicants()->exists(),
            default => $this->canVerifStage($stage),
        };
    }

    public function resetVerification(): void
    {
        $stages = self::verifStages();

        foreach ($stages as $stage) {
            $this->{$stage[0]} = 'menunggu';
            $this->{$stage[1]} = null;
        }
    }
}
