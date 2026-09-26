{{--
  Corong dua tahap: profil terverifikasi → pendaftaran → hasil keputusan.
  Setiap tahap punya penyebut sendiri, jadi angka di bawahnya adalah bagian dari
  nilai di atasnya, bukan jumlah yang dijumlahkan.
--}}
@props(['funnel', 'stages' => []])

<div class="card">
  <div class="card-header">
    <h4>Alur Penerimaan</h4>
  </div>
  <div class="card-body">
    @php
      $tahapan = [
          ['label' => 'Profil terverifikasi', 'nilai' => $funnel['terverifikasi'], 'warna' => 'primary'],
          ['label' => 'Mendaftar beasiswa', 'nilai' => $funnel['pendaftar'], 'warna' => 'info'],
          ['label' => 'Diterima', 'nilai' => $funnel['diterima'], 'warna' => 'success'],
          ['label' => 'Ditolak', 'nilai' => $funnel['ditolak'], 'warna' => 'danger'],
      ];

      if ($funnel['dibatalkan'] > 0) {
          $tahapan[] = ['label' => 'Dibatalkan mahasiswa', 'nilai' => $funnel['dibatalkan'], 'warna' => 'secondary'];
      }
    @endphp

    @foreach($tahapan as $tahap)
      @php $persen = $funnel['pendaftar'] > 0 ? round($tahap['nilai'] / $funnel['pendaftar'] * 100) : 0; @endphp
      <div class="mb-3">
        <div class="d-flex justify-content-between flex-wrap mb-1">
          <span>{{ $tahap['label'] }}</span>
          <span>{{ $tahap['nilai'] }}</span>
        </div>
        <div class="progress" data-height="6">
          <div class="progress-bar bg-{{ $tahap['warna'] }}" data-width="{{ min($persen, 100) }}%"></div>
        </div>
      </div>
    @endforeach

    <small class="text-muted d-block">
      Persentase dihitung terhadap total pendaftaran, bukan terhadap tahap sebelumnya.
    </small>
  </div>
</div>
