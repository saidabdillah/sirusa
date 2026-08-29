{{-- Admin Konfirmasi Penerima Beasiswa --}}
      @if($confirmedRecipients > 0)
      <div class="row mt-4">
        <div class="col-12">
          <div class="card">
            <div class="card-header">
              <h5>Konfirmasi Penerima Beasiswa</h5>
            </div>
            <div class="card-body">
              <div class="table-responsive">
                <table class="table table-striped mb-0">
                  <thead>
                    <tr>
                      <th>#</th>
                      <th>Nama Lengkap</th>
                      <th>Beasiswa</th>
                      <th>Status</th>
                      <th>Tanggal Konfirmasi</th>
                      <th>Aksi</th>
                    </tr>
                  </thead>
                  <tbody>
                    @php $no = 1; @endif
                    @foreach($recentApplicants as $applicant)
                      @if($applicant->status === 'diterima')
                        <tr>
                          <td>{{ $no++ }}</td>
                          <td>{{ $applicant->user->profile->nama_lengkap ?? '-' }}</td>
                          <td>{{ $applicant->beasiswa->nama }}</td>
                          <td>
                            @if($applicant->beasiswa->tanggal_pemberitahuan)
                              <span class="badge badge-success">Sudah Konfirmasi</span>
                            @else
                              <span class="badge badge-warning">Belum Konfirmasi</span>
                            @endif
                          </td>
                          <td>
                            @if(!$applicant->beasiswa->tanggal_pemberitahuan)
                              <form method="POST" action="{{ route('dasbor') }}" style="display:inline">
                                @csrf @method('POST')
                                <button type="submit" class="btn btn-sm btn-outline-primary" onclick="return confirm('Apakah {{ $applicant->user->profile->nama_lengkap }} sudah mendapatkan beasiswa {{ $applicant->beasiswa->nama }}?')">
                                  Konfirmasi
                                </button>
                              </form>
                            @endif
                          </td>
                        </tr>
                      @endif
                    @endforeach
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>
      @endif

      <div class="row">
        <div class="col-lg-6 col-md-12 col-12">
          <div class="card">
            <div class="card-header">
              <h4>Batas Waktu Mendatang</h4>
            </div>
            <div class="card-body">
              @forelse($upcomingDeadlines as $scholarship)
                <div class="d-flex align-items-center mb-3">
                  <div class="mr-3">
                    <div class="badge badge-{{ $scholarship->batas_waktu?->diffInDays(now()) <= 7 ? 'danger' : 'primary' }}">
                      {{ $scholarship->batas_waktu?->diffForHumans() }}
                    </div>
                  </div>
                  <div class="flex-grow-1">
                    <div class="font-weight-bold">{{ $scholarship->nama }}</div>
                    <div class="text-small text-muted">{{ $scholarship->kampus }}</div>
                  </div>
                </div>
              @empty
                <div class="text-center text-muted">Tidak ada batas waktu mendatang</div>
              @endforelse
            </div>
          </div>
        </div>

        <div class="col-lg-6 col-md-12 col-12">
          <div class="card">
            <div class="card-header">
              <h4>Pengumuman Beasiswa</h4>
            </div>
            <div class="card-body">
              @if($upcomingDeadlines->isNotEmpty())
                <p>Beasiswa berikut akan segera berakhir atau telah berakhir:</p>
                <ul class="mb-0">
                  @foreach($upcomingDeadlines as $scholarship)
                    <li class="mb-2">
                      <strong>{{ $scholarship->nama }}</strong> - Batas waktu: <span class="text-danger">{{ $scholarship->batas_waktu->format('d F Y') }}</span>
                    </li>
                  @endforeach
                </ul>
              @endif
            </div>
          </div>
        </div>
      </div>

      <div class="row">
        <div class="col-lg-12">
          <div class="card">
            <div class="card-header">
              <h4>Pendaftar Terbaru</h4>
              <div class="card-header-action">
                <a href="{{ route('admin.pendaftar.index') }}" class="btn btn-primary">Lihat Semua</a>
              </div>
            </div>
            <div class="card-body p-0">
              <div class="table-responsive">
                <table class="table table-striped mb-0">
                  <thead>
                    <tr>
                      <th>Nama</th>
                      <th>Beasiswa</th>
                      <th>Fakultas</th>
                      <th>Status</th>
                      <th>Tanggal Daftar</th>
                    </tr>
                  </thead>
                  <tbody>
                    @forelse($recentApplicants as $applicant)
                      <tr>
                        <td>
                          <a href="{{ route('admin.pendaftar.lihat', $applicant) }}">{{ $applicant->user->profile->nama_lengkap ?? '-' }}</a>
                        </td>
                        <td>{{ $applicant->beasiswa->nama }}</td>
                        <td>{{ $applicant->fakultas ?? '-' }}</td>
<td>
                           @if($applicant->status === 'verifikasi')
                              <span class="badge badge-warning">Verifikasi</span>
                            @elseif($applicant->status === 'diterima')
                              <span class="badge badge-success">Diterima</span>
                            @elseif($applicant->status === 'revisi')
                              <span class="badge badge-secondary">Perlu Revisi</span>
                            @elseif($applicant->status === 'ditolak')
                              <span class="badge badge-danger">Ditolak</span>
                            @endif
                          </td>
                          <td>{{ $applicant->created_at->format('d M Y') }}</td>
                        </tr>
                      @empty
                        <tr>
                          <td colspan="5" class="text-center">Belum ada pendaftar</td>
                        </tr>
                      @endforelse
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>