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
          <div class="card-header">
            <form action="{{ route('notifications.read-all') }}" method="POST" class="d-inline mr-1">
              @csrf
              <button type="submit" class="btn btn-sm btn-primary">
                <i class="fas fa-check-double"></i> Tandai Semua Sudah Dibaca
              </button>
            </form>
            <form action="{{ route('notifications.destroy-read') }}" method="POST" class="d-inline mr-1">
              @csrf
              @method('DELETE')
              <button type="submit" class="btn btn-sm btn-secondary"
                      onclick="return confirm('Hapus semua notifikasi yang sudah dibaca?')">
                <i class="fas fa-trash"></i> Hapus yang Sudah Dibaca
              </button>
            </form>
            <form action="{{ route('notifications.destroy-all') }}" method="POST" class="d-inline">
              @csrf
              @method('DELETE')
              <button type="submit" class="btn btn-sm btn-danger"
                      onclick="return confirm('Hapus SEMUA notifikasi?')">
                <i class="fas fa-trash"></i> Hapus Semua
              </button>
            </form>
          </div>
          <div class="card-body">
            <div class="list-group list-group-flush">
              @forelse($notifications as $notification)
                @php $data = $notification->data; @endphp
                <div class="list-group-item list-group-item-action d-flex justify-content-between align-items-center {{ $notification->read() ? '' : 'bg-light' }}">
                  <a href="{{ route('notifications.show', $notification) }}" class="text-decoration-none flex-grow-1 mr-2">
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
                  <form action="{{ route('notifications.destroy', $notification) }}" method="POST" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-icon btn-light text-danger"
                            title="Hapus notifikasi" onclick="return confirm('Hapus notifikasi ini?')">
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
