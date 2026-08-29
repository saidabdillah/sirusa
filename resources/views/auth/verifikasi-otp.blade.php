@extends('layouts.app')

@section('content')
<section class="section d-flex align-items-center justify-content-center" style="min-height: 100vh;">
  <div class="container">
    <div class="row">
      <div class="col-12 col-sm-10 offset-sm-1 col-md-8 offset-md-2 col-lg-6 offset-lg-3">
        <div class="login-brand">
          <img src="{{ asset('assets/img/stisla-fill.svg') }}" alt="logo" width="100" class="shadow-light rounded-circle">
        </div>

        <div class="card card-primary">
          <div class="card-header">
            <h4>Verifikasi OTP</h4>
          </div>

          <div class="card-body">
            <p class="text-muted">
              Kami telah mengirimkan kode OTP 6 digit ke email <strong>{{ $email }}</strong>.
              Kode ini berlaku selama 5 menit.
            </p>

            @if (session('success'))
              <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            <form method="POST" action="{{ route('password.otp.check', ['email' => $email]) }}">
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
                           @if ($i === 1) autofocus @endif
                           required>
                  @endfor
                </div>
                <input type="hidden" name="otp" id="otp-hidden" value="">
                @error('otp')
                  <div class="invalid-feedback d-block mt-2">{{ $message }}</div>
                @enderror
              </div>

              <div class="form-group mt-4">
                <button type="submit" class="btn btn-primary btn-lg btn-block" tabindex="4">
                  Verifikasi
                </button>
              </div>
            </form>

            <div class="text-center mt-3">
              <small class="text-muted">
                Tidak menerima email?
                <a href="{{ route('password.request') }}" class="text-primary ms-1">
                  Kirim ulang
                </a>
              </small>
            </div>
          </div>
        </div>
        <div class="simple-footer">
          Hak Cipta &copy; SIRUSA 2026
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