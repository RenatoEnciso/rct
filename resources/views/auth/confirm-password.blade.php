@extends('layouts.auth')
@section('title', 'Confirmar contraseña')

@section('content')
  <h2>Confirmar contraseña</h2>
  <p class="lead-text">Esta es una sección protegida. Confirme su contraseña para continuar.</p>

  <form method="POST" action="{{ route('password.confirm') }}">
    @csrf
    <div class="field">
      <label for="password">Contraseña</label>
      <input id="password" type="password" name="password" required autocomplete="current-password"
             class="{{ $errors->has('password') ? 'is-invalid' : '' }}">
      @error('password')<span class="field-error">{{ $message }}</span>@enderror
    </div>
    <div class="actions">
      <button type="submit" class="btn btn-primary">Confirmar</button>
    </div>
  </form>
@endsection
