<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\RespondsToAjax;
use App\Http\Controllers\Controller;
use App\Http\Requests\Scholarship\StoreScholarshipRequest;
use App\Http\Requests\Scholarship\UpdateScholarshipRequest;
use App\Models\Kampus;
use App\Models\Prodi;
use App\Models\Scholarship;
use App\Support\DataTablesProcessor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class BeasiswaController extends Controller
{
    use RespondsToAjax;

    public function index(): View
    {
        $scholarships = Scholarship::latest()->paginate(10);

        return view('admin.beasiswa.index', compact('scholarships'));
    }

    public function data(Request $request): JsonResponse
    {
        $canManage = auth()->user()->hasMenuAccess('admin.beasiswa.index');

        $processor = new DataTablesProcessor(
            $request,
            Scholarship::latest(),
            [
                'searchable' => ['nama', 'kampus'],
                'orderable' => [1 => 'nama', 2 => 'kampus', 3 => 'kuota'],
            ],
            fn (Scholarship $scholarship, int $no) => [
                'no' => $no,
                'nama' => e($scholarship->nama),
                'kampus' => e($scholarship->kampus),
                'kuota' => $scholarship->kuota,
                'gelar' => e((string) $scholarship->tingkat_gelar),
                'batas' => $this->renderBatasWaktu($scholarship),
                'ipk' => number_format($scholarship->ipk_minimal, 2),
                'semester' => $scholarship->semester_minimal,
                'status' => $scholarship->status === 'aktif'
                    ? '<span class="badge badge-success">Aktif</span>'
                    : '<span class="badge badge-secondary">Non-aktif</span>',
                'aksi' => $canManage ? view('admin.beasiswa._aksi', ['data' => $scholarship])->render() : '',
            ]
        );

        return response()->json($processor->respond());
    }

    private function renderBatasWaktu(Scholarship $scholarship): string
    {
        $mulai = $scholarship->tanggal_mulai?->translatedFormat('d M Y');
        $selesai = $scholarship->tanggal_selesai?->translatedFormat('d M Y');

        $periode = ($mulai && $selesai) ? $mulai.' - '.$selesai : '-';

        if ($scholarship->isExpired()) {
            return '<span class="text-danger">'.e($periode).'</span>';
        }

        return e($periode);
    }

    public function create(): View
    {
        return view('admin.beasiswa.buat', [
            'kampusList' => $this->kampusTree(),
            // Prodi yang dicentang, per kampus. Saat validasi gagal `old()`
            // yang dipakai supaya centangnya tidak hilang.
            'kampusTerpilih' => $this->kampusTerpilih(collect(old('prodi_ids', []))),
        ]);
    }

    public function store(StoreScholarshipRequest $request): RedirectResponse|JsonResponse
    {
        $data = $request->validated();
        $prodiIds = $data['prodi_ids'];
        unset($data['prodi_ids']);

        $data['kampus'] = $this->ringkasanKampus($prodiIds);

        $scholarship = Scholarship::create($data);

        $this->simpanCakupan($scholarship, $prodiIds);

        return $this->ajaxOk($request, 'Beasiswa berhasil ditambahkan', route('admin.beasiswa.index'));
    }

    public function show(Scholarship $scholarship): View
    {
        // Cakupan dibaca dari id prodi, lalu kampus/fakultasnya diturunkan dari
        // prodi tersebut -- tidak ada nama salinan yang bisa melenceng.
        $scholarship->load('cakupan.prodi.fakultas.kampus');
        $applicants = $scholarship->pendaftar()->with(['user.profile', 'beasiswa'])->latest()->paginate(10);

        return view('admin.beasiswa.lihat', compact('scholarship', 'applicants'));
    }

    public function edit(Scholarship $scholarship): View
    {
        $scholarship->load('cakupan');

        return view('admin.beasiswa.ubah', [
            'scholarship' => $scholarship,
            'kampusList' => $this->kampusTree(),
            // Centang dari database + centang dari `old()` digabung: saat
            // validasi gagal, pilihan terakhir user yang harus tampil.
            'kampusTerpilih' => $this->kampusTerpilih(
                $scholarship->cakupan->pluck('prodi_id')->merge(old('prodi_ids', [])),
            ),
        ]);
    }

    public function update(UpdateScholarshipRequest $request, Scholarship $scholarship): RedirectResponse|JsonResponse
    {
        $data = $request->validated();
        $prodiIds = $data['prodi_ids'];
        unset($data['prodi_ids']);

        $data['kampus'] = $this->ringkasanKampus($prodiIds);

        $scholarship->update($data);
        $this->simpanCakupan($scholarship, $prodiIds);

        return $this->ajaxOk($request, 'Beasiswa berhasil diperbarui', route('admin.beasiswa.index'));
    }

    /**
     * Pohon kampus -> fakultas -> prodi untuk form centang.
     *
     * Kampus dan fakultas di sini hanya PENYARING tampilan (tombol "pilih
     * semua" mencentang anaknya). Yang disimpan sebagai cakupan hanyalah prodi,
     * sehingga tidak ada dua sumber kebenaran yang bisa bertentangan.
     *
     * @return Collection<int, Kampus>
     */
    private function kampusTree(): Collection
    {
        return Kampus::with('fakultas.prodi')->orderBy('nama_kampus')->get();
    }

    /**
     * Kelompokkan id prodi per kampusnya, supaya view bisa menyalakan centang
     * kampus dan fakultas yang punya prodi terpilih.
     *
     * @param  Collection<int, mixed>  $prodiIds
     * @return array<int, array<int, int>>
     */
    private function kampusTerpilih(Collection $prodiIds): array
    {
        $ids = $prodiIds->map(fn ($id) => (int) $id)->unique();

        if ($ids->isEmpty()) {
            return [];
        }

        return Prodi::query()
            ->join('fakultas', 'fakultas.id', '=', 'prodi.fakultas_id')
            ->whereIn('prodi.id', $ids)
            ->get(['prodi.id', 'fakultas.kampus_id'])
            ->groupBy('kampus_id')
            ->map(fn (Collection $baris) => $baris->pluck('id')->all())
            ->all();
    }

    /**
     * Ringkasan nama kampus untuk kolom teks `beasiswa.kampus`.
     *
     * Kolom itu dipertahankan karena daftar dan pencarian memakainya. Isinya
     * diturunkan dari prodi terpilih, jadi tidak ada daftar kampus kedua yang
     * bisa berbeda dengan cakupan sesungguhnya.
     *
     * @param  array<int, mixed>  $prodiIds
     */
    private function ringkasanKampus(array $prodiIds): string
    {
        $nama = Kampus::query()
            ->whereIn('kampus.id', Prodi::query()
                ->whereIn('prodi.id', $prodiIds)
                ->join('fakultas', 'fakultas.id', '=', 'prodi.fakultas_id')
                ->pluck('fakultas.kampus_id'))
            ->orderBy('nama_kampus')
            ->pluck('nama_kampus');

        if ($nama->isEmpty()) {
            return 'Semua Kampus';
        }

        // Kolom ini ringkasan untuk daftar dan pencarian; cakupan lengkapnya
        // dibaca di halaman detail beasiswa. Daftar panjang dipotong supaya
        // baris tabelnya tetap satu baris.
        return $nama->take(3)->implode(', ')
            .($nama->count() > 3 ? ' +'.($nama->count() - 3).' lainnya' : '');
    }

    /**
     * Ganti seluruh cakupan beasiswa dengan daftar prodi yang dicentang.
     *
     * @param  array<int, mixed>  $prodiIds
     */
    private function simpanCakupan(Scholarship $scholarship, array $prodiIds): void
    {
        $scholarship->cakupan()->delete();

        $baris = collect($prodiIds)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->map(fn (int $id) => ['prodi_id' => $id])
            ->all();

        if ($baris !== []) {
            $scholarship->cakupan()->createMany($baris);
        }
    }

    public function destroy(Request $request, Scholarship $scholarship): RedirectResponse|JsonResponse
    {
        $scholarship->delete();

        return $this->ajaxOk($request, 'Beasiswa berhasil dihapus', route('admin.beasiswa.index'));
    }
}
