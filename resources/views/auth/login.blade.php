@extends('layouts.auth')
@section('title', 'Iniciar sesión')

@section('content')
  <h2>Iniciar sesión</h2>
  <p class="lead-text">Ingrese con su usuario institucional.</p>

  @if(session('status'))
    <div class="notice-success"><i class="bi bi-check-circle"></i><span>{{ session('status') }}</span></div>
  @endif
  @if($errors->any())
    <div class="notice-danger"><i class="bi bi-exclamation-circle"></i><span>{{ $errors->first() }}</span></div>
  @endif

  <form method="POST" action="{{ route('login') }}" novalidate>
    @csrf
    <div class="field">
      <label for="email">Correo institucional</label>
      <div class="input-icon">
        <i class="bi bi-envelope"></i>
        <input id="email" type="email" name="email" value="{{ old('email') }}"
               placeholder="usuario@cajatrujillo.pe" required autofocus autocomplete="username"
               class="{{ $errors->has('email') ? 'is-invalid' : '' }}">
      </div>
    </div>

    <div class="field">
      <label for="password">Contraseña</label>
      <div class="input-icon">
        <i class="bi bi-lock"></i>
        <input id="password" type="password" name="password" required autocomplete="current-password"
               class="{{ $errors->has('password') ? 'is-invalid' : '' }}">
        <button type="button" class="toggle-pass" id="toggle-pass" aria-label="Mostrar contraseña">
          <i class="bi bi-eye"></i>
        </button>
      </div>
    </div>

    <div class="auth-row">
      <label class="check"><input type="checkbox" name="remember"> Mantener sesión iniciada</label>
      @if(Route::has('password.request'))
        <a href="{{ route('password.request') }}">¿Olvidó su contraseña?</a>
      @endif
    </div>

    <button type="submit" class="btn btn-primary btn-lg btn-block">
      Ingresar <i class="bi bi-arrow-right"></i>
    </button>
  </form>

  <div class="auth-foot">
    <i class="bi bi-lock"></i> Conexión segura · Las acciones quedan registradas en la bitácora del sistema.
  </div>
@endsection

@push('scripts')
<script>
document.getElementById('toggle-pass').addEventListener('click', function () {
  var i = document.getElementById('password'), ver = i.type === 'password';
  i.type = ver ? 'text' : 'password';
  this.querySelector('.bi').className = ver ? 'bi bi-eye-slash' : 'bi bi-eye';
  this.setAttribute('aria-label', ver ? 'Ocultar contraseña' : 'Mostrar contraseña');
});
</script>
@endpush
