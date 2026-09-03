<nav class="navbar navbar-expand-lg main-navbar">
  <form class="form-inline mr-auto">
    <ul class="navbar-nav mr-3">
      <li><a href="#" data-toggle="sidebar" class="nav-link nav-link-lg"><i class="fas fa-bars"></i></a></li>
    </ul>
  </form>
  <ul class="navbar-nav navbar-right">
    <li class="dropdown dropdown-list-toggle"><a href="#" data-toggle="dropdown"
        class="nav-link notification-toggle nav-link-lg {{ $unreadCount > 0 ? 'beep' : '' }}"><i class="far fa-bell"></i></a>
      <div class="dropdown-menu dropdown-list dropdown-menu-right">
        <div class="dropdown-header">Notifikasi
          @if($unreadCount > 0)
            <span class="badge badge-primary ml-1">{{ $unreadCount }}</span>
          @endif
          <div class="float-right">
            <form action="{{ route('notifications.read-all') }}" method="POST" class="d-inline">
              @csrf
              <button type="submit" class="btn btn-link p-0">Tandai Semua Sudah Dibaca</button>
            </form>
          </div>
        </div>
        <div class="dropdown-list-content dropdown-list-icons">
          @forelse($notifications as $notification)
            @php $data = $notification->data; @endphp
            <a href="{{ route('notifications.show', $notification) }}"
               class="dropdown-item {{ $notification->read() ? '' : 'dropdown-item-unread' }}">
              <div class="dropdown-item-icon bg-{{ $notification->read() ? 'secondary' : 'primary' }} text-white">
                <i class="fas {{ data_get($data, 'icon', 'fa-bell') }}"></i>
              </div>
              <div class="dropdown-item-desc">
                {{ data_get($data, 'title', 'Notifikasi') }}
                <div class="time text-muted">{{ data_get($data, 'message', '') }}</div>
              </div>
            </a>
          @empty
            <div class="text-center text-muted py-4">Tidak ada notifikasi</div>
          @endforelse
        </div>
        <div class="dropdown-footer text-center">
          <a href="{{ route('notifications.index') }}">Lihat Semua <i class="fas fa-chevron-right"></i></a>
        </div>
      </div>
    </li>
    <li class="dropdown"><a href="#" data-toggle="dropdown" class="nav-link dropdown-toggle nav-link-lg nav-link-user">
        @if(Auth::user()->profile?->foto_profil)
          <img alt="image" src="{{ route('profile.photo', Auth::user()->profile->foto_profil) }}" class="rounded-circle mr-1" width="30" height="30" style="object-fit:cover;">
        @else
          <img alt="image" src="{{ asset('assets/img/avatar/avatar-1.png') }}" class="rounded-circle mr-1">
        @endif
        <div class="d-sm-none d-lg-inline-block">Hai, {{ Auth::user()->username }}</div>
      </a>
      <div class="dropdown-menu dropdown-menu-right">
        <div class="dropdown-title">Masuk 5 menit yang lalu</div>
        <a href="{{ route('profile') }}" class="dropdown-item has-icon">
          <i class="far fa-user"></i> Profil
        </a>
        <a href="{{ route('settings') }}" class="dropdown-item has-icon">
          <i class="fas fa-cog"></i> Pengaturan
        </a>
        <div class="dropdown-divider"></div>
        <a href="#" class="dropdown-item has-icon text-danger" id="logoutBtn">
          <i class="fas fa-sign-out-alt"></i> Keluar
        </a>
        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
          @csrf
        </form>
      </div>
    </li>
  </ul>
</nav>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    var logoutBtn = document.getElementById('logoutBtn');

    if (logoutBtn) {
      logoutBtn.addEventListener('click', function (e) {
        e.preventDefault();

        fetch("{{ route('logout.info') }}", {
          headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
          },
        })
          .then(function (res) { return res.json(); })
          .then(function (data) {
            Swal.fire({
              title: data.title,
              text: data.text,
              icon: data.icon,
              showCancelButton: true,
              confirmButtonColor: data.confirmButtonColor,
              cancelButtonColor: '#6c757d',
              confirmButtonText: data.confirmButtonText,
              cancelButtonText: 'Batal',
            }).then(function (result) {
              if (result.isConfirmed) {
                document.getElementById('logout-form').submit();
              }
            });
          });
      });
    }
  });
</script>
