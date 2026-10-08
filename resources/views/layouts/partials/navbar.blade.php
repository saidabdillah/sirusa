<nav class="navbar navbar-expand-lg main-navbar">
  <form class="form-inline mr-auto">
    <ul class="navbar-nav mr-3">
      <li><a href="#" data-toggle="sidebar" class="nav-link nav-link-lg"><i class="fas fa-bars"></i></a></li>
    </ul>
  </form>
  <ul class="navbar-nav navbar-right">
    <li class="dropdown"><a href="#" data-toggle="dropdown" class="nav-link dropdown-toggle nav-link-lg nav-link-user">
        @if(Auth::user()->profile?->foto_profil)
          <img alt="image" src="{{ route('dokumen.show', Auth::user()->profile->foto_profil) }}" class="rounded-circle mr-1" width="30" height="30" style="object-fit:cover;">
        @else
          <img alt="image" src="{{ asset('assets/img/avatar/avatar-1.png') }}" class="rounded-circle mr-1">
        @endif
        <div class="d-sm-none d-lg-inline-block">Hai, {{ Auth::user()->username }}</div>
      </a>
      <div class="dropdown-menu dropdown-menu-right">
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
          .then(function (res) {
            // 5xx balannya HTML halaman error, bukan JSON, jadi `res.json()`
            // akan melempar. Cek dulu supaya masuk ke `.catch()` dengan pesan
            // yang benar, bukan "Unexpected token <".
            if (!res.ok) {
              throw new Error('HTTP ' + res.status);
            }

            return res.json();
          })
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
          })
          // Tanpa cabang ini, request yang gagal (koneksi putus, 403, 500)
          // ditelan promise begitu saja: tidak ada dialog konfirmasi yang
          // muncul dan tidak ada pesan apa pun, sehingga klik Logout seolah
          // tidak berefek. Tombolnya sendiri tidak pernah di-disable, jadi
          // tidak ada keadaan "nyangkut" -- masalahnya murni tidak ada
          // umpan balik sama sekali.
          .catch(function () {
            Swal.fire({
              icon: 'error',
              title: 'Gagal',
              text: 'Konfirmasi logout tidak bisa dimuat. Silakan coba lagi.',
            });
          });
      });
    }
  });
</script>
