@extends('layouts.app')

@section('content')
<section class="section">
  <div class="section-header">
    <h1>Notifikasi</h1>
    <div class="section-header-breadcrumb">
      <div class="breadcrumb-item active"><a href="{{ route('dashboard') }}">Dashboard</a></div>
      <div class="breadcrumb-item">Notifikasi</div>
    </div>
  </div>

  @if(session('success'))
  <div class="alert alert-success alert-dismissible fade show" role="alert">
    {{ session('success') }}
    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
  </div>
  @endif

  <div class="section-body">
    <div class="row">
      <div class="col-12">
        <div class="card">
          <div class="card-header flex-wrap">
            <form action="{{ route('notifications.read-all') }}" method="POST" class="d-inline mr-1 mb-1">
              @csrf
              <button type="submit" class="btn btn-sm btn-primary">
                <i class="fas fa-check-double"></i> Tandai Semua Sudah Dibaca
              </button>
            </form>
            <form action="{{ route('notifications.destroy-read') }}" method="POST" class="d-inline mr-1 mb-1">
              @csrf
              @method('DELETE')
              <button type="button" class="btn btn-sm btn-delete" data-confirm-title="Hapus yang Sudah Dibaca?"
                data-confirm-text="Semua notifikasi yang sudah dibaca akan dihapus permanen!">
                <i class="fas fa-trash"></i> Hapus yang Sudah Dibaca
              </button>
            </form>
            <form action="{{ route('notifications.destroy-all') }}" method="POST" class="d-inline mb-1">
              @csrf
              @method('DELETE')
              <button type="button" class="btn btn-sm btn-delete" data-confirm-title="Hapus SEMUA Notifikasi?"
                data-confirm-text="SEMUA notifikasi akan dihapus permanen! Tindakan ini tidak dapat dibatalkan.">
                <i class="fas fa-trash"></i> Hapus Semua
              </button>
            </form>
          </div>
          <div class="card-body">
            <div class="table-responsive">
              <table class="table table-striped" id="notifikasiTable">
                <thead>
                  <tr>
                    <th>No</th>
                    <th>Judul</th>
                    <th>Pesan</th>
                    <th>Waktu</th>
                    <th>Status</th>
                    <th>Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($notifications as $notification)
                  @php $data = $notification->data; @endphp
                  <tr class="{{ $notification->read() ? '' : 'table-active' }}">
                    <td>{{ $loop->iteration }}</td>
                    <td>
                      <a href="{{ route('notifications.show', $notification) }}" class="text-decoration-none">
                        <i class="fas {{ data_get($data, 'icon', 'fa-bell') }} mr-1 text-primary"></i>
                        {{ data_get($data, 'title', 'Notifikasi') }}
                      </a>
                    </td>
                    <td>{{ Str::limit((string) data_get($data, 'message', ''), 80) }}</td>
                    <td data-order="{{ $notification->created_at->timestamp }}">
                      {{ $notification->created_at->format('d M Y H:i') }}
                    </td>
                    <td>
                      @if($notification->unread())
                      <span class="badge badge-primary">Baru</span>
                      @else
                      <span class="badge badge-secondary">Dibaca</span>
                      @endif
                    </td>
                    <td>
                      <form action="{{ route('notifications.destroy', $notification) }}" method="POST" class="d-inline-block align-middle mr-1 mb-1 btn-delete-form">
                        @csrf
                        @method('DELETE')
                        <button type="button" class="btn btn-sm btn-delete" title="Hapus notifikasi"
                          data-confirm-title="Hapus Notifikasi?"
                          data-confirm-text="Notifikasi ini akan dihapus permanen!">
                          <i class="fas fa-trash"></i>
                        </button>
                      </form>
                    </td>
                  </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
@endsection

@push('script')
<script>
  document.addEventListener('DOMContentLoaded', function() {
    $('#notifikasiTable').DataTable({
      order: [[3, 'desc']],
      pageLength: 10,
      language: {
        search: "Cari:",
        lengthMenu: "Tampilkan _MENU_ data",
        info: "Menampilkan _START_ - _END_ dari _TOTAL_ data",
        infoEmpty: "Tidak ada data",
        infoFiltered: "(disaring dari _MAX_ total data)",
        zeroRecords: "Tidak ada data yang cocok",
        paginate: {
          first: "Pertama",
          last: "Terakhir",
          next: "Selanjutnya",
          previous: "Sebelumnya"
        }
      }
    });
});
</script>
@endpush