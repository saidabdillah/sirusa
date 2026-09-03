<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Daftar Penerima Beasiswa</title>
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: Arial, sans-serif; font-size: 12px; color: #333; padding: 30px; }
    .header { text-align: center; margin-bottom: 25px; border-bottom: 2px solid #007bff; padding-bottom: 15px; }
    .header h1 { font-size: 16px; color: #007bff; margin-bottom: 5px; }
    .header h2 { font-size: 14px; font-weight: normal; color: #555; }
    .info { margin-bottom: 15px; font-size: 11px; }
    .info p { margin-bottom: 3px; }
    table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    th, td { border: 1px solid #ccc; padding: 6px 8px; text-align: left; font-size: 11px; }
    th { background-color: #007bff; color: white; }
    tr:nth-child(even) { background-color: #f8f9fa; }
    .footer { margin-top: 20px; font-size: 10px; text-align: right; color: #888; }
  </style>
</head>
<body>
  <div class="header">
    <h1>Daftar Penerima Beasiswa</h1>
    <h2>{{ $scholarship->nama }}</h2>
  </div>

  <div class="info">
    <p><strong>Kampus:</strong> {{ $scholarship->kampus }}</p>
    @if($scholarship->tanggal_pengumuman && $scholarship->tanggal_pengumuman_selesai)
      <p><strong>Periode Pengumuman:</strong> {{ $scholarship->tanggal_pengumuman->format('d/m/Y') }} s/d {{ $scholarship->tanggal_pengumuman_selesai->format('d/m/Y') }}</p>
    @endif
    <p><strong>Total Penerima:</strong> {{ $penerima->count() }}</p>
  </div>

  <table>
    <thead>
      <tr>
        <th>No</th>
        <th>Nama Lengkap</th>
        <th>Fakultas</th>
        <th>Program Studi</th>
        <th>IPK</th>
      </tr>
    </thead>
    <tbody>
      @forelse($penerima as $index => $applicant)
        <tr>
          <td>{{ $index + 1 }}</td>
          <td>{{ $applicant->user?->profile?->nama_lengkap ?? $applicant->user?->username ?? '-' }}</td>
          <td>{{ $applicant->fakultas ?? '-' }}</td>
          <td>{{ $applicant->prodi ?? '-' }}</td>
          <td>{{ $applicant->ipk ?? '-' }}</td>
        </tr>
      @empty
        <tr>
          <td colspan="5" style="text-align: center">Tidak ada penerima.</td>
        </tr>
      @endforelse
    </tbody>
  </table>

  <div class="footer">
    Dicetak pada {{ now()->translatedFormat('d F Y H:i') }} &mdash; SIRUSA
  </div>
</body>
</html>