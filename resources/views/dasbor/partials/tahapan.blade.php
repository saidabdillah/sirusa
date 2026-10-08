{{-- Porsi antrean tiap tahap, hanya tahap yang bisa diakses pengguna. --}}
@props(['stages' => [], 'perStage' => []])

@php
  use App\Support\VerifikasiAntrean;

  // Satu baris merangkai beberapa tahap sekaligus, padahal tiap tahap punya
  // kumpulan status sendiri (lihat `VerifikasiAntrean::ringkasanKolom()`).
  // Karena itu header dibuat sekali dari daftar lintas tahap, dan setiap sel
  // mencari kunci status apa yang berlaku untuk tahap baris itu. Status yang
  // tidak berlaku ditampilkan 0 -- permintaan pengguna (dulu tanda hubung).
  $kolom = VerifikasiAntrean::ringkasanKolom();
@endphp

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
            @foreach($kolom as $column)
              <th class="text-center">{{ $column['label'] }}</th>
            @endforeach
            <th></th>
          </tr>
        </thead>
        <tbody>
          @forelse($stages as $stage)
            @php
              $antrean = new VerifikasiAntrean($stage);
              $kunci = $antrean->ringkasanKeys();
              $counts = $perStage[$stage] ?? [];
            @endphp
            <tr>
              <td class="text-capitalize">{{ $antrean->label() }}</td>
              @foreach($kolom as $column)
                @php
                  $key = current(array_intersect($column['keys'], $kunci));
                @endphp
                <td class="text-center">{{ $key === false ? 0 : ($counts[$key] ?? 0) }}</td>
              @endforeach
              <td class="text-right">
                <a href="{{ route($antrean->routeName()) }}" class="btn btn-sm btn-outline-primary">Buka</a>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="{{ 2 + count($kolom) }}" class="text-center text-muted">Tidak ada tahap verifikasi yang bisa diakses.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>
