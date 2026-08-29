@extends('layouts.app')

@section('content')
<section class="section" style="min-height: calc(100vh - 140px);">
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
          <div class="card-header">
            <form action="{{ route('notifications.read-all') }}" method="POST" class="d-inline mr-1">
              @csrf
              <button type="submit" class="btn btn-sm btn-primary">
                <i class="fas fa-check-double"></i> Tandai Semua Sudah Dibaca
              </button>
            </form>
            <form action="{{ route('notifications.destroy-read') }}" method="POST" class="d-inline mr-1"
              data-confirm="true" data-confirm-title="Hapus yang Sudah Dibaca?"
              data-confirm-text="Semua notifikasi yang sudah dibaca akan dihapus permanen!">
              @csrf
              @method('DELETE')
              <button type="submit" class="btn btn-sm btn-secondary">
                <i class="fas fa-trash"></i> Hapus yang Sudah Dibaca
              </button>
            </form>
            <form action="{{ route('notifications.destroy-all') }}" method="POST" class="d-inline" data-confirm="true"
              data-confirm-title="Hapus SEMUA Notifikasi?"
              data-confirm-text="SEMUA notifikasi akan dihapus permanen! Tindakan ini tidak dapat dibatalkan.">
              @csrf
              @method('DELETE')
              <button type="submit" class="btn btn-sm btn-danger">
                <i class="fas fa-trash"></i> Hapus Semua
              </button>
            </form>
          </div>
          <div class="card-body">
            <div class="list-group list-group-flush">
              @forelse($notifications as $notification)
              @php $data = $notification->data; @endphp
              <div
                class="list-group-item list-group-item-action d-flex justify-content-between align-items-center {{ $notification->read() ? '' : 'bg-light' }}">
                <a href="{{ route('notifications.show', $notification) }}"
                  class="text-decoration-none flex-grow-1 mr-2">
                  <div class="d-flex justify-content-between align-items-center">
                    <div>
                      <div class="font-weight-bold">
                        <i class="fas {{ data_get($data, 'icon', 'fa-bell') }} mr-1 text-primary"></i>
                        {{ data_get($data, 'title', 'Notifikasi') }}
                        @if($notification->unread())
                        <span class="badge badge-primary ml-1">Baru</span>
                        @endif
                      </div>
                      <small class="text-muted">{{ data_get($data, 'message', '') }}</small>
                    </div>
                    <small class="text-muted">{{ $notification->created_at->diffForHumans() }}</small>
                  </div>
                </a>
                <form action="{{ route('notifications.destroy', $notification) }}" method="POST" class="d-inline"
                  data-confirm="true" data-confirm-title="Hapus Notifikasi?"
                  data-confirm-text="Notifikasi ini akan dihapus permanen!">
                  @csrf
                  @method('DELETE')
                  <button type="submit" class="btn btn-sm btn-icon btn-light text-danger" title="Hapus notifikasi">
                    <i class="fas fa-times"></i>
                  </button>
                </form>
              </div>
              @empty
              <div class="text-center text-muted py-4">Tidak ada notifikasi</div>
              @endforelse
            </div>
            <div class="mt-3">
              {{ $notifications->links() }}
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
    // SweetAlert2 confirmation handler untuk elemen dengan data-confirm="true"
    document.addEventListener('click', function(e) {
        const element = e.target.closest('[data-confirm="true"]');
        if (!element) return;

        e.preventDefault();

        const title = element.dataset.confirmTitle || 'Konfirmasi';
        const text = element.dataset.confirmText || 'Apakah Anda yakin?';
        const icon = element.dataset.confirmIcon || 'warning';
        const confirmButtonText = element.dataset.confirmButton || 'Ya';
        const cancelButtonText = element.dataset.confirmCancel || 'Batal';
        const confirmButtonColor = element.dataset.confirmColor || '#d33';
        const cancelButtonColor = element.dataset.confirmCancelColor || '#6c757d';

        Swal.fire({
            title: title,
            text: text,
            icon: icon,
            showCancelButton: true,
            confirmButtonColor: confirmButtonColor,
            cancelButtonColor: cancelButtonColor,
            confirmButtonText: confirmButtonText,
            cancelButtonText: cancelButtonText
        }).then((result) => {
            if (result.isConfirmed) {
                // Jika element adalah form, submit form
                if (element.tagName === 'FORM') {
                    element.submit();
                }
                // Jika element adalah button di dalam form, submit form parent
                else if (element.tagName === 'BUTTON') {
                    const form = element.closest('form');
                    if (form) {
                        form.submit();
                    }
                }
            }
        });
    });
});
</script>
@endpush