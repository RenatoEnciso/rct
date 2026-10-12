@extends('layouts.auth')
@section('title', 'Recuperar contraseña')

@section('content')
  <h2>Recuperar contraseña</h2>
  <p class="lead-text">Ingrese su correo institucional y le enviaremos un enlace para restablecer su contraseña.</p>

  @if(session('status'))
    <div class="notice-success"><i class="bi bi-check-circle"></i><span>{{ session('status') }}</span></div>
  @endif

  <form method="POST" action="{{ route('password.email') }}">
    @csrf
    <div class="field">
      <label for="email">Correo institucional</label>
      <div class="input-icon">
        <i class="bi bi-envelope"></i>
        <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
               class="{{ $errors->has('email') ? 'is-invalid' : '' }}">
      </div>
      @error('email')<span class="field-error">{{ $message }}</span>@enderror
    </div>
    <div class="actions">
      <a href="{{ route('login') }}" class="btn btn-ghost"><i class="bi bi-arrow-left"></i> Volver</a>
      <button type="submit" class="btn btn-primary">Enviar enlace</button>
    </div>
  </form>
@endsection
