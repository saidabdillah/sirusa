<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <title>@yield('title', 'SIRUSA &mdash; Sistem Informasi Beasiswa')</title>

  <!-- General CSS Files -->
  <link rel="stylesheet" href="{{ asset('assets/modules/bootstrap/css/bootstrap.min.css') }}">
  <link rel="stylesheet" href="{{ asset('assets/modules/fontawesome/css/all.min.css') }}">

  <!-- Template CSS -->
  <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">
  <link rel="stylesheet" href="{{ asset('assets/css/components.css') }}">

  <style>
    html {
      scroll-behavior: smooth;
      scroll-padding-top: 76px;
    }

    .landing-navbar {
      height: auto;
      left: auto;
      right: auto;
      position: sticky;
      z-index: 1030;
      background-color: #fff !important;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    }

    .landing-navbar .navbar-brand {
      color: #191d21;
      text-transform: none;
      letter-spacing: normal;
      font-weight: 700;
    }

    .landing-navbar .navbar-nav .nav-link {
      color: #34395e;
      padding: 0.5rem 0.75rem !important;
      height: auto;
      font-weight: 500;
    }

    .landing-navbar .navbar-nav .nav-link:hover {
      color: #346CB0;
    }

    .navbar .landing-cart-btn .btn-block {
      width: auto;
    }

    @media (min-width: 768px) and (max-width: 991.98px) {
      .landing-navbar .collapse,
      .landing-navbar .navbar-nav {
        position: static !important;
      }
    }

    @media (max-width: 991.98px) {
      .landing-navbar .navbar-nav {
        flex-direction: column;
        width: 100%;
      }

      .landing-navbar .navbar-collapse {
        align-items: stretch;
      }
    }

    .landing-hero,
    .landing-cta {
      background: linear-gradient(135deg, #16335c 0%, #346CB0 60%, #5db0e6 100%);
    }

    .stat-count {
      font-size: 2rem;
      font-weight: 700;
      color: #346CB0;
    }

    .kampus-marquee {
      background: linear-gradient(135deg, #16335c 0%, #346CB0 60%, #5db0e6 100%);
      overflow: hidden;
      white-space: nowrap;
      color: #fff;
      font-weight: 500;
      font-size: 1.1rem;
    }

    .kampus-marquee__track {
      display: inline-flex;
      will-change: transform;
      animation: kampus-scroll 22s linear infinite;
    }

    .kampus-marquee:hover .kampus-marquee__track {
      animation-play-state: paused;
    }

    @keyframes kampus-scroll {
      from {
        transform: translateX(0);
      }
      to {
        transform: translateX(-50%);
      }
    }
  </style>
</head>

<body>
  @yield('content')
  <script src="{{ asset('assets/modules/jquery.min.js') }}"></script>
  <script src="{{ asset('assets/modules/bootstrap/js/bootstrap.min.js') }}"></script>
  @stack('script')
</body>

</html>
