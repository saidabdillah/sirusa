<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Applicant;
use App\Models\Scholarship;
use App\Support\DataTablesProcessor;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PendaftarController extends Controller
{
    public function index(Request $request): View
    {
        // Menunya dihapus dari sidebar (digantikan leaf rekap "Pendaftar" di
        // Administrasi), tapi halamannya tetap ada dan tetap terlindungi.
        // Sebelumnya penjaganya grant menu `admin.pendaftar`; karena baris
        // menunya sudah tidak ada, penjaganya kini peran admin secara eksplisit
        // -- tanpa ini, route tanpa baris menu tidak pernah dianggap "guarded"
        // dan bisa dibuka siapa pun yang sudah login.
        abort_unless(
            auth()->user()->hasAnyRole(['super_admin', 'catpil', 'kampus', 'kesra']),
            403,
            'Anda tidak memiliki akses ke halaman ini.',
        );

        $request->validate([
            'status' => 'nullable|in:'.implode(',', array_keys(Applicant::STATUS_LABELS)),
            'beasiswa_id' => 'nullable|integer|exists:beasiswa,id',
        ]);

        $kampusId = $this->scopedKampusId();

        $applicants = Applicant::with(['user', 'beasiswa'])
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->where('status', $request->string('status'));
            })
            ->when($request->filled('beasiswa_id'), function ($query) use ($request) {
                $query->where('beasiswa_id', $request->integer('beasiswa_id'));
            })
            ->when($kampusId, function ($query) use ($kampusId) {
                $query->whereHas('beasiswa', fn ($q) => $q->where('kampus_id', $kampusId));
            })
            ->latest('id')
            ->limit(25)
            ->get();

        $beasiswas = Scholarship::orderBy('nama')->get(['id', 'nama']);

        return view('admin.pendaftar.index', compact('applicants', 'beasiswas'));
    }

    public function data(Request $request): JsonResponse
    {
        // Penjaga yang sama dengan `index()` -- lihat catatannya di sana.
        abort_unless(
            auth()->user()->hasAnyRole(['super_admin', 'catpil', 'kampus', 'kesra']),
            403,
            'Anda tidak memiliki akses ke halaman ini.',
        );

        $kampusId = $this->scopedKampusId();

        $query = Applicant::query()->with(['user.profile', 'beasiswa'])->latest('id');

        if ($kampusId) {
            $query->whereHas('beasiswa', fn ($q) => $q->where('kampus_id', $kampusId));
        }

        $processor = new DataTablesProcessor(
            $request,
            $query,
            [
                'filter' => function (Builder $query, Request $request) {
                    if (in_array((string) $request->string('status'), array_keys(Applicant::STATUS_LABELS), true)) {
                        $query->where('status', (string) $request->string('status'));
                    }

                    if ($request->filled('beasiswa_id')) {
                        $query->where('beasiswa_id', $request->integer('beasiswa_id'));
                    }
                },
                'searchable' => [
                    'fakultas',
                    'prodi',
                    fn ($q, $term) => $q->orWhereRelation('user.profile', 'nama_lengkap', 'like', "%{$term}%"),
                    fn ($q, $term) => $q->orWhereRelation('beasiswa', 'nama', 'like', "%{$term}%"),
                ],
                'orderable' => [],
            ],
            fn (Applicant $applicant, int $no) => [
                'no' => $no,
                'nama' => e($applicant->user->profile?->nama_lengkap ?? '-'),
                'beasiswa' => e($applicant->beasiswa->nama),
                'fakultas' => e($applicant->fakultas ?? '-'),
                'prodi' => e($applicant->prodi ?? '-'),
                'ipk' => e((string) $applicant->ipk),
                'status' => $this->renderStatus($applicant),
            ]
        );

        return response()->json($processor->respond());
    }

    private function scopedKampusId(): ?int
    {
        $user = auth()->user();

        if (! $user->hasRole('kampus')) {
            return null;
        }

        return $user->kampus_id;
    }

    private function renderStatus(Applicant $applicant): string
    {
        return sprintf(
            '<span class="badge badge-%s">%s</span>',
            $applicant->statusBadge(),
            e($applicant->statusLabel()),
        );
    }
}
