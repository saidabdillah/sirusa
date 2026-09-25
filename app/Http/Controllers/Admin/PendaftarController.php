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
        $request->validate([
            'status' => 'nullable|in:verifikasi,diterima,ditolak',
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
                    if (in_array((string) $request->string('status'), ['verifikasi', 'diterima', 'ditolak'], true)) {
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
        return match ($applicant->status) {
            'verifikasi' => '<span class="badge badge-warning">Verifikasi</span>',
            'diterima' => '<span class="badge badge-success">Diterima</span>',
            'revisi' => '<span class="badge badge-warning">Revisi</span>',
            'ditolak' => '<span class="badge badge-danger">Ditolak</span>',
            default => '',
        };
    }
}
