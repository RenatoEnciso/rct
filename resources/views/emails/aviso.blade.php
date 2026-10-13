<!DOCTYPE html>
<html lang="es">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>{{ $titulo }}</title></head>
<body style="margin:0;padding:0;background:#f4f5f7;font-family:Segoe UI,Arial,sans-serif;color:#1c2430">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f5f7;padding:24px 0">
    <tr><td align="center">
      <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border:1px solid #e3e6ea;border-radius:8px;overflow:hidden">
        <tr><td style="background:#a3182c;padding:18px 28px;color:#ffffff">
          <div style="font-size:16px;font-weight:700;letter-spacing:.02em">CAJA TRUJILLO</div>
          <div style="font-size:12px;opacity:.85">RCT · Sistema de Recuperaciones · Programas de Gobierno</div>
        </td></tr>
        <tr><td style="padding:26px 28px 8px">
          @if($nombre)<p style="margin:0 0 12px;font-size:14px">Estimado(a) {{ $nombre }}:</p>@endif
          <h1 style="margin:0 0 10px;font-size:19px;color:#1c2430">{{ $titulo }}</h1>
          <p style="margin:0 0 18px;font-size:14px;line-height:1.55;color:#3d4756">{{ $mensaje }}</p>
          @if(!empty($detalle))
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e3e6ea;border-radius:6px;margin-bottom:18px;font-size:13px">
              @foreach($detalle as [$etiqueta, $valor])
                <tr>
                  <td style="padding:8px 12px;background:#f8f9fa;color:#5f6b7a;border-bottom:1px solid #eef0f2;width:45%">{{ $etiqueta }}</td>
                  <td style="padding:8px 12px;border-bottom:1px solid #eef0f2;font-weight:600">{{ $valor }}</td>
                </tr>
              @endforeach
            </table>
          @endif
          @if($url)
            <p style="margin:0 0 22px"><a href="{{ $url }}" style="display:inline-block;background:#a3182c;color:#ffffff;text-decoration:none;padding:10px 18px;border-radius:6px;font-size:14px;font-weight:600">Ver en RCT</a></p>
          @endif
        </td></tr>
        <tr><td style="padding:14px 28px;border-top:1px solid #eef0f2;font-size:11.5px;color:#8a94a3">
          Mensaje automático de RCT. No responda este correo. Información de uso interno de Caja Trujillo.
        </td></tr>
      </table>
    </td></tr>
  </table>
</body>
</html>
