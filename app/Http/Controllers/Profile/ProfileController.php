<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Concerns\RespondsToAjax;
use App\Http\Controllers\Controller;
use App\Http\Requests\Profil\UpdateProfilRequest;
use App\Models\DokumenAkademik;
use App\Models\Kampus;
use App\Models\UserProfile;
use App\Models\VerifikasiSemester;
use App\Services\WilayahService;
use App\Support\JenjangPendidikan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProfileController extends Controller
{
    use RespondsToAjax;

    private const PROVINSI = 'Kalimantan Selatan';

    private const KABUPATEN = 'Balangan';

    private const KABUPATEN_CODE = '6311';

    /**
     * Dokumen yang SATU berkas per mahasiswa.
     *
     * SKTM dan Transkrip sudah tidak ada di daftar ini (permintaan pengguna):
     * SKTM tidak lagi disyaratkan, sedangkan Transkrip digantikan PDDIKTI.
     * Kolom `dokumen_sktm` dan `dokumen_transkrip` sengaja DIBIARKAN di tabel
     * supaya berkas mahasiswa lama tidak hilang -- hanya tidak lagi diunggah
     * atau diminta.
     */
    private array $documentFields = [
        'dokumen_ktp',
        'dokumen_kk',
        'dokumen_desil',
        'dokumen_surat_aktif',
        'dokumen_surat_pernyataan',
        'dokumen_pddikti',
        'dokumen_bukti_ukt',
        'ktp_ayah',
        'ktp_ibu',
        'ktp_wali',
        'kk_wali',
    ];

    public function __construct(
        private WilayahService $wilayah
    ) {}

    public function index(): View
    {
        $user = auth()->user();
        $profile = $user->profile;
        $isMahasiswa = $user->isMahasiswa();

        // Akun staf tidak punya baris profil, jadi form isian + dokumen dan
        // pilihan wilayah/kampus tidak perlu dihitung -- `getDistricts()` sampai
        // ke API luar, dan itu sia-sia untuk halaman yang hanya menampilkan
        // informasi akun.
        $profileComplete = $isMahasiswa && $user->isProfileComplete();

        $districts = $isMahasiswa
            ? $this->wilayah->getDistricts(self::KABUPATEN_CODE)
            : [];

        $kampusList = $isMahasiswa
            ? Kampus::with('fakultas.prodi')
                ->orderBy('nama_kampus')
                ->get()
            : collect();

        $kampusJson = $kampusList->map(function (Kampus $kampus) {
            return [
                'nama' => $kampus->nama_kampus,
                'fakultas' => $kampus->fakultas->map(function ($fakultas) {
                    return [
                        'nama' => $fakultas->nama,
                        'prodi' => $fakultas->prodi->map(fn ($prodi) => [
                            'id' => $prodi->id,
                            'nama' => $prodi->nama,
                        ])->values(),
                    ];
                })->values(),
            ];
        });

        // Dokumen KRS/KHS yang sudah tersimpan, dikelompokkan per jenis lalu
        // per semester supaya view tinggal mencarinya. Baris yang ada tetap
        // ditampilkan walau semester aktifnya sudah berubah.
        $dokumenAkademik = $isMahasiswa && $profile
            ? $profile->dokumenAkademik()->get()->groupBy('jenis')
            : collect();

        return view('profil.index', compact(
            'profile',
            'profileComplete',
            'isMahasiswa',
            'districts',
            'kampusList',
            'kampusJson',
            'dokumenAkademik',
        ) + [
            'jenjangList' => JenjangPendidikan::JENJANG,
            'semesterMap' => JenjangPendidikan::JUMLAH_SEMESTER,
        ]);
    }

    public function update(UpdateProfilRequest $request): RedirectResponse|JsonResponse
    {
        $data = $request->validated();
        $data['provinsi'] = self::PROVINSI;
        $data['kabupaten_kota'] = self::KABUPATEN;

        // Email ada di `users`, bukan di `profil_pengguna`. Ditarik dari payload
        // supaya tidak ikut tersimpan ke tabel profil (tidak fillable, jadi
        // `fill()` akan membuangnya diam-diam kalau tidak dilepas di sini).
        $email = $data['email'] ?? null;
        unset($data['email']);

        $ikutKk = $request->input('ikut_kk', 'ayah');

        $data['ikut_kk'] = $ikutKk;
        $data['kk_ikut_famili_lain'] = $ikutKk === 'famili_lain';

        if (! $data['kk_ikut_famili_lain']) {
            $data['nama_wali'] = null;
            $data['nik_wali'] = null;
            $data['pekerjaan_wali'] = null;
            $data['hubungan_wali'] = null;
            $data['ktp_wali'] = null;
            $data['kk_wali'] = null;
        }

        $profile = auth()->user()->profile;
        $uploadDisk = Storage::disk('public');

        try {
            foreach (['foto_profil'] as $field) {
                if ($request->hasFile($field)) {
                    $file = $request->file($field);
                    $old = $profile?->{$field};
                    $data[$field] = $uploadDisk->putFile('profil/'.auth()->id(), $file, 'public');
                    $this->deleteIfExists($uploadDisk, $old);
                }
            }

            foreach ($this->documentFields as $field) {
                if ($request->hasFile($field)) {
                    $file = $request->file($field);
                    $old = $profile?->{$field};
                    $data[$field] = $uploadDisk->putFile('profil/'.auth()->id(), $file, 'public');
                    $this->deleteIfExists($uploadDisk, $old);
                }
            }

            if ($request->hasFile('dokumen_prestasi')) {
                $prestasi = $profile?->dokumen_prestasi ?? [];
                foreach ($request->file('dokumen_prestasi') as $file) {
                    $path = $uploadDisk->putFile('profil/'.auth()->id(), $file, 'public');
                    $prestasi[] = $path;
                }
                $data['dokumen_prestasi'] = $prestasi;
            }

            $profile = UserProfile::updateOrCreate(
                ['user_id' => auth()->id()],
                $data
            );

            // KRS/KHS disimpan SETELAH profil, dan per semester: satu baris
            // `dokumen_akademik` per (mahasiswa, jenis, semester). Semester
            // yang tidak dikirim di request ini tidak disentuh sama sekali,
            // jadi mengunggah KHS semester 4 tidak pernah menimpa KHS
            // semester 3.
            $this->simpanDokumenAkademik($request, $uploadDisk);

            $profile->resetVerification();
            $profile->save();

            if ($email !== null && $email !== auth()->user()->email) {
                auth()->user()->forceFill(['email' => $email])->save();
            }

            // Setelah berkas akademik disimpan, paket semester yang dokumennya
            // kini LENGKAP otomatis masuk antrean Kampus. Pemicunya Simpan,
            // bukan tombol tambahan, supaya tidak ada paket yang tertinggal
            // hanya karena mahasiswa lupa menekan tombol kedua.
            VerifikasiSemester::sinkron(auth()->user());

            return $this->ajaxOk($request, 'Profil berhasil diperbarui', route('profile'));
        } catch (\Throwable $e) {
            // Detail teknisnya masuk log, TIDAK ke layar. Pesan exception
            // database memuat seluruh perintah INSERT beserta nilai-nilainya,
            // jadi menampilkannya sama dengan membocorkan NIK, No. KK, nomor
            // HP, dan data pribadi lain milik mahasiswa yang sedang mengisi.
            Log::error('Gagal update profil: '.$e->getMessage(), [
                'user_id' => auth()->id(),
                'exception' => $e,
            ]);

            return $this->ajaxFail(
                $request,
                'Profil gagal disimpan karena terjadi kesalahan pada sistem. Silakan coba lagi; bila masih gagal, hubungi admin.',
                null,
                [],
                500
            );
        }
    }

    private function deleteIfExists($disk, ?string $path): void
    {
        if ($path && $disk->exists($path)) {
            $disk->delete($path);
        }
    }

    /**
     * Simpan berkas (+ nilai untuk KHS) per semester.
     *
     * Satu baris per (mahasiswa, jenis, semester) -- lihat unique index di
     * migrasinya. Yang penting di sini: baris yang TIDAK dikirim (tidak
     * diunggah ulang dan nilainya kosong) sama sekali tidak disentuh, jadi
     * dokumen semester sebelumnya tidak pernah tertimpa oleh unggahan semester
     * lain. Berkas lama juga tidak dihapus kalau pengguna hanya memperbarui
     * nilainya.
     *
     * KRS TIDAK punya nilai: rencana studi hanya berisi daftar mata kuliah,
     * jadi `nilai` hanya dibaca untuk KHS. Angka nilai yang tetap dikirim
     * pada baris KRS diabaikan, bukan disimpan.
     *
     * @param  array<string, array<int|string, array<string, mixed>>>  $baris  `[jenis => [semester => [file, nilai]]]`
     */
    private function simpanDokumenAkademik(UpdateProfilRequest $request, $uploadDisk): void
    {
        $semua = (array) $request->input('dokumen_akademik', []);

        foreach ($semua as $jenis => $perSemester) {
            if (! array_key_exists($jenis, DokumenAkademik::JENIS_LABELS)) {
                continue;
            }

            // Nilai hanya milik KHS.
            $punyaNilai = $jenis === DokumenAkademik::JENIS_KHS;

            foreach ((array) $perSemester as $semester => $isi) {
                $semester = (int) $semester;

                if ($semester < 1) {
                    continue;
                }

                $file = $request->file("dokumen_akademik.{$jenis}.{$semester}.file");
                $nilai = $punyaNilai ? ($isi['nilai'] ?? null) : null;

                // Baris yang tidak diisi apa-apa dilewati, bukan dikosongkan:
                // form mengirim semua baris semester, dan memperlakukan yang
                // kosong sebagai "hapus" akan menghapus data yang sudah ada.
                if ($file === null && ($nilai === null || $nilai === '')) {
                    continue;
                }

                $dokumen = DokumenAkademik::firstOrNew([
                    'user_id' => auth()->id(),
                    'jenis' => $jenis,
                    'semester' => $semester,
                ]);

                if ($file !== null) {
                    $lama = $dokumen->file;
                    $dokumen->file = $uploadDisk->putFile(
                        'profil/'.auth()->id().'/akademik',
                        $file,
                        'public'
                    );
                    $this->deleteIfExists($uploadDisk, $lama);
                }

                if ($nilai !== null && $nilai !== '') {
                    $dokumen->nilai = $nilai;
                }

                $dokumen->save();
            }
        }
    }
}
