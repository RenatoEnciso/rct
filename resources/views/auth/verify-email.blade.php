@extends('layouts.auth')
@section('title', 'Verificar correo')

@section('content')
  <h2>Verifique su correo</h2>
  <p class="lead-text">Antes de continuar, confirme su dirección de correo con el enlace que le enviamos. Si no lo recibió, puede solicitar uno nuevo.</p>

  @if(session('status') == 'verification-link-sent')
    <div class="notice-success"><i class="bi bi-check-circle"></i><span>Se envió un nuevo enlace de verificación a su correo.</span></div>
  @endif

  <div class="actions justify-content-between">
    <form method="POST" action="{{ route('logout') }}">
      @csrf
      <button type="submit" class="btn btn-ghost">Cerrar sesión</button>
    </form>
    <form method="POST" action="{{ route('verification.send') }}">
      @csrf
      <button type="submit" class="btn btn-primary">Reenviar enlace</button>
    </form>
  </div>
@endsection
