@extends('layouts.app')

@section('content')
<section class="section">
  <div class="section-header">
    <h1>Verifikasi Ganti Email</h1>
    <div class="section-header-breadcrumb">
      <div class="breadcrumb-item active"><a href="{{ route('dashboard') }}">Dasbor</a></div>
      <div class="breadcrumb-item active"><a href="{{ route('settings') }}">Pengaturan</a></div>
      <div class="breadcrumb-item">Verifikasi Email</div>
    </div>
  </div>

  <div class="section-body">
    <div class="row">
      <div class="col-12 col-sm-10 offset-sm-1 col-md-8 offset-md-2 col-lg-6 offset-lg-3">
        @if (session('success'))
          <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
          </div>
        @endif

        <div class="card">
          <div class="card-header">
            <h4>Masukkan Kode OTP</h4>
          </div>
          <div class="card-body">
            <p class="text-muted">
              Kami telah mengirimkan kode OTP 6 digit ke email baru <strong>{{ $pendingEmail }}</strong>.
              Kode ini berlaku selama 5 menit.
            </p>

            <form method="POST" action="{{ route('settings.email.verify.store') }}">
              @csrf

              <div class="form-group">
                <label>Masukkan Kode OTP</label>
                <div class="d-flex gap-2 justify-content-center" id="otp-inputs">
                  @for ($i = 1; $i <= 6; $i++)
                    <input type="text"
                           name="otp_digit_{{ $i }}"
                           class="form-control text-center otp-digit"
                           maxlength="1"
                           style="width: 48px; height: 56px; font-size: 1.5rem;"
                           autocomplete="one-time-code"
                           inputmode="numeric"
                           @if ($i === 1) autofocus @endif>
                  @endfor
                </div>
                <input type="hidden" name="otp" id="otp-hidden" value="">
                @error('otp')
                  <div class="invalid-feedback d-block mt-2">{{ $message }}</div>
                @enderror
              </div>

              <div class="form-group mt-4">
                <button type="submit" class="btn btn-primary btn-lg btn-block">
                  Verifikasi &amp; Ganti Email
                </button>
              </div>
            </form>

            <div class="text-center mt-3">
              <small class="text-muted">
                Tidak menerima email?
                <a href="{{ route('settings') }}" class="text-primary ms-1">Coba lagi</a>
              </small>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

@push('script')
<script>
  document.addEventListener('DOMContentLoaded', function() {
    const inputs = document.querySelectorAll('.otp-digit');
    const hiddenInput = document.getElementById('otp-hidden');

    inputs.forEach((input, index) => {
      input.addEventListener('input', function(e) {
        this.value = this.value.replace(/[^0-9]/g, '');

        if (this.value && index < inputs.length - 1) {
          inputs[index + 1].focus();
        }

        updateHiddenInput();
      });

      input.addEventListener('keydown', function(e) {
        if (e.key === 'Backspace' && !this.value && index > 0) {
          inputs[index - 1].focus();
        }
      });

      input.addEventListener('paste', function(e) {
        e.preventDefault();
        const pasted = (e.clipboardData || window.clipboardData).getData('text');
        const digits = pasted.replace(/[^0-9]/g, '').split('');

        digits.forEach((digit, i) => {
          if (inputs[index + i]) {
            inputs[index + i].value = digit;
          }
        });

        const lastFilled = Math.min(index + digits.length - 1, inputs.length - 1);
        inputs[lastFilled].focus();

        updateHiddenInput();
      });
    });

    function updateHiddenInput() {
      let otp = '';
      inputs.forEach(input => otp += input.value);
      hiddenInput.value = otp;
    }

    inputs[0].focus();
  });
</script>
@endpush
@endsection
