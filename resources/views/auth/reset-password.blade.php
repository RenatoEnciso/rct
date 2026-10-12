@extends('layouts.auth')
@section('title', 'Restablecer contraseña')

@section('content')
  <h2>Restablecer contraseña</h2>
  <p class="lead-text">Defina una nueva contraseña para su cuenta.</p>

  <form method="POST" action="{{ route('password.store') }}">
    @csrf
    <input type="hidden" name="token" value="{{ $request->route('token') }}">

    <div class="field">
      <label for="email">Correo institucional</label>
      <input id="email" type="email" name="email" value="{{ old('email', $request->email) }}" required autofocus autocomplete="username"
             class="{{ $errors->has('email') ? 'is-invalid' : '' }}">
      @error('email')<span class="field-error">{{ $message }}</span>@enderror
    </div>
    <div class="field">
      <label for="password">Nueva contraseña</label>
      <input id="password" type="password" name="password" required autocomplete="new-password"
             class="{{ $errors->has('password') ? 'is-invalid' : '' }}">
      @error('password')<span class="field-error">{{ $message }}</span>@enderror
    </div>
    <div class="field">
      <label for="password_confirmation">Confirmar contraseña</label>
      <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password">
      @error('password_confirmation')<span class="field-error">{{ $message }}</span>@enderror
    </div>
    <div class="actions">
      <button type="submit" class="btn btn-primary">Restablecer contraseña</button>
    </div>
  </form>
@endsection
