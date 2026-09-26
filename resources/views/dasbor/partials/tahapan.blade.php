{{-- Porsi antrean tiap tahap, hanya tahap yang bisa diakses pengguna. --}}
@props(['stages' => [], 'perStage' => []])

<div class="card">
  <div class="card-header">
    <h4>Sebaran Antrean per Tahap</h4>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-striped mb-0">
        <thead>
          <tr>
            <th>Tahap</th>
            <th class="text-center">Menunggu</th>
            <th class="text-center">Perlu Perbaikan</th>
            <th class="text-center">Disetujui</th>
            <th class="text-center">Ditolak</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          @forelse($stages as $stage)
            @php
              $antrean = new \App\Support\VerifikasiAntrean($stage);
              $label = $antrean->label();
              $counts = $perStage[$stage] ?? [];
            @endphp
            <tr>
              <td class="text-capitalize">{{ $label }}</td>
              <td class="text-center">{{ $counts['menunggu'] ?? 0 }}</td>
              <td class="text-center">{{ $counts['revisi'] ?? 0 }}</td>
              <td class="text-center">{{ $counts['setuju'] ?? 0 }}</td>
              <td class="text-center">{{ $counts['tolak'] ?? 0 }}</td>
              <td class="text-right">
                <a href="{{ route($antrean->routeName()) }}" class="btn btn-sm btn-outline-primary">Buka</a>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="6" class="text-center text-muted">Tidak ada tahap verifikasi yang bisa diakses.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>
