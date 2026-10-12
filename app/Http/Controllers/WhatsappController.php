<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\{ClienteCartera, PropuestaFud, User};
use App\Services\{AsignadorAprobador, RegistroFud};

/**
 * Canal de mensajería (réplica de WhatsApp Business) para el ejecutivo:
 * consulta de clientes por DNI/RUC, registro de solicitudes FUD y estado.
 * Solo usa lo que WhatsApp Business permite: texto con formato, botones de
 * respuesta (máx. 3, título ≤ 20), mensajes de lista (máx. 10 filas) y documentos.
 * Opera como el usuario configurado en rct.whatsapp.usuario (sin login).
 */
class WhatsappController extends Controller
{
    private const SESION = 'wsp';

    public function __construct(private AsignadorAprobador $asignador, private RegistroFud $registro)
    {
        abort_unless(config('rct.whatsapp.habilitado'), 404);
    }

    public function index()
    {
        return view('whatsapp.index', ['numero' => config('rct.whatsapp.numero')]);
    }

    public function historial(Request $request)
    {
        return response()->json(['mensajes' => $request->session()->get(self::SESION.'.historial', [])]);
    }

    public function reiniciar(Request $request)
    {
        $request->session()->forget(self::SESION);
        return response()->json(['ok' => true]);
    }

    public function mensaje(Request $request)
    {
        $request->validate([
            'texto'    => 'nullable|string|max:1000',
            'accion'   => 'nullable|string|max:60',
            'etiqueta' => 'nullable|string|max:80',
            'archivo'  => 'nullable|file|max:10240',
        ]);

        $usuario = $this->usuario();
        $texto   = trim((string) $request->texto);
        $accion  = (string) $request->accion;

        // Mensaje del ejecutivo tal como se ve en el chat
        if ($request->hasFile('archivo')) {
            $f = $request->file('archivo');
            $this->guardar($request, ['lado' => 'yo', 'tipo' => 'documento', 'texto' => $f->getClientOriginalName(),
                'meta' => strtoupper($f->getClientOriginalExtension()).' · '.max(1, round($f->getSize() / 1024)).' KB']);
        } elseif ($accion !== 'inicio' && ($accion !== '' || $texto !== '')) {
            $this->guardar($request, ['lado' => 'yo', 'tipo' => 'texto', 'texto' => $request->etiqueta ?: $texto]);
        }

        $respuestas = $this->procesar($request, $usuario, $texto, $accion);
        foreach ($respuestas as $r) $this->guardar($request, $r + ['lado' => 'bot']);

        return response()->json(['mensajes' => array_map(fn($r) => $r + ['lado' => 'bot', 'hora' => now()->format('H:i')], $respuestas)]);
    }

    // ── Conversación ─────────────────────────────────────────

    private function procesar(Request $request, User $usuario, string $texto, string $accion): array
    {
        $s = $request->session();
        $paso = $s->get(self::SESION.'.paso', 'menu');
        $t = Str::lower(Str::ascii($texto));

        if ($accion === 'inicio' || $accion === 'menu' || in_array($t, ['menu', 'inicio', 'hola', 'buenas', 'buenos dias', 'buenas tardes', 'opciones'])) {
            $this->limpiar($request);
            return $accion === 'inicio' || in_array($t, ['hola', 'buenas', 'buenos dias', 'buenas tardes'])
                ? [$this->saludo($usuario)] : [$this->menu('¿Qué desea hacer?')];
        }
        if ($accion === 'cancelar' || $t === 'cancelar') {
            $this->limpiar($request);
            return [$this->menu('Operación cancelada. ¿Qué desea hacer?')];
        }

        if ($request->hasFile('archivo') && $paso !== 'adjuntos') {
            return [$this->txt('Para adjuntar documentos primero inicie el registro de una solicitud y llegue al paso de adjuntos.'),
                    $this->menu('¿Qué desea hacer?')];
        }

        return match ($paso) {
            'dni'          => $this->recibirDni($request, $usuario, $texto),
            'post_consulta'=> preg_match('/^\d{8}$|^\d{11}$/', $texto) ? $this->recibirDni($request, $usuario, $texto) : $this->postConsulta($request, $usuario, $accion),
            'credito'      => $this->elegirCredito($request, $usuario, $accion),
            'cuotas', 'desc_int', 'monto', 'desc_int_canc', 'desc_cap', 'pct_sk'
                           => $this->recibirCondicion($request, $paso, $texto),
            'motivo'       => $this->recibirMotivo($request, $texto),
            'adjuntos'     => $this->recibirAdjunto($request, $accion),
            'confirmar'    => $this->confirmar($request, $usuario, $accion),
            'estado'       => str_starts_with($accion, 'fud:') || preg_match('/^fud-?\s*\d+$/i', $texto)
                                ? $this->detalleFud($usuario, $accion, $texto) : $this->enMenu($request, $usuario, $texto, $accion),
            default        => $this->enMenu($request, $usuario, $texto, $accion),
        };
    }

    private function enMenu(Request $request, User $usuario, string $texto, string $accion): array
    {
        $t = Str::lower(Str::ascii($texto));
        $op = $accion ?: match (true) {
            in_array($t, ['1', 'consultar', 'consulta']) => 'consultar',
            in_array($t, ['2', 'solicitud', 'registrar']) => 'solicitud',
            in_array($t, ['3', 'estado', 'mis solicitudes']) => 'estado',
            in_array($t, ['4', 'ayuda']) => 'ayuda',
            (bool) preg_match('/^\d{8}$|^\d{11}$/', $texto) => 'dni_directo',
            (bool) preg_match('/^fud-?\s*\d+$/i', $texto) => 'fud_directo',
            default => '',
        };

        switch ($op) {
            case 'consultar':
            case 'solicitud':
                $this->set($request, ['paso' => 'dni', 'modo' => $op]);
                return [$this->txt('Escriba el *DNI o RUC* del cliente.'.($op === 'solicitud' ? "\n\nLuego elegirá el crédito para registrar la solicitud." : ''))];
            case 'dni_directo':
                $this->set($request, ['modo' => 'consultar']);
                return $this->recibirDni($request, $usuario, $texto);
            case 'estado':
                return $this->listarEstado($request, $usuario);
            case 'fud_directo':
                return $this->detalleFud($usuario, 'fud:'.preg_replace('/\D/', '', $texto), '');
            case 'ayuda':
                return [$this->txt("*Canal de Recuperaciones — RCT*\n\nDesde aquí puede:\n• Consultar si un cliente es apto para la campaña (DNI o RUC).\n• Registrar una solicitud de conciliación (FUD).\n• Ver el estado de sus solicitudes.\n\nEscriba *menú* para ver las opciones o *cancelar* para detener una operación. Las solicitudes registradas aquí aparecen en RCT web, en _Mis propuestas_."),
                        $this->menu('¿Qué desea hacer?')];
            default:
                return [$this->menu('No entendí su mensaje. Elija una opción:')];
        }
    }

    // ── Consulta por DNI ─────────────────────────────────────

    private function recibirDni(Request $request, User $usuario, string $texto): array
    {
        $doc = preg_replace('/\D/', '', $texto);
        if (!preg_match('/^\d{8}$|^\d{11}$/', $doc)) {
            return [$this->txt('El documento debe tener 8 dígitos (DNI) u 11 dígitos (RUC). Escríbalo nuevamente o escriba *cancelar*.')];
        }

        $creditos = ClienteCartera::with('tienda')->where('nro_documento', $doc)->orderByDesc('saldo_capital')->limit(5)->get();
        if ($creditos->isEmpty()) {
            $this->set($request, ['paso' => 'post_consulta', 'dni' => $doc, 'aptos' => []]);
            return [$this->botones("No se encontraron créditos con el documento *{$doc}* en la base de campaña.",
                [['otra', 'Otra consulta'], ['menu', 'Menú']])];
        }

        $aptos = [];
        $lineas = ['*'.$creditos->first()->nombre_cliente.'*', ($creditos->first()->tipo_documento ?? 'DNI')." {$doc}"];
        foreach ($creditos as $c) {
            $motivo = $this->motivoNoApto($c);
            if (!$motivo) $aptos[] = $c->id;
            $apr = $motivo ? null : $this->asignador->asignar($c, $usuario->id);
            $lineas[] = '';
            $lineas[] = "*Op. {$c->operacion}* · ".(RegistroFud::ETIQUETAS[$c->tipo_negociacion_campana] ?? 'Sin lineamiento');
            $lineas[] = 'Situación: '.ucfirst(strtolower((string) $c->estado_cartera))." · {$c->dias_atraso} días · {$c->calificacion_rcc}";
            $lineas[] = 'Saldo capital: S/ '.number_format($c->saldo_capital, 2);
            $lineas[] = 'Tienda: '.($c->tienda->nombre ?? '—');
            if ($c->lineamiento_campana) $lineas[] = 'Lineamiento: '.$c->lineamiento_campana;
            $lineas[] = 'Conciliaciones 12 meses: '.min($c->veces_conciliado, 4).' de 4';
            if ($apr) $lineas[] = "Aprobaría: {$apr->name} ({$apr->rol->nombre})";
            $lineas[] = $motivo ? "Estado: *No apto* — {$motivo}" : 'Estado: *Apto para la campaña*';
        }

        $this->set($request, ['paso' => 'post_consulta', 'dni' => $doc, 'aptos' => $aptos]);

        if (($this->get($request, 'modo') === 'solicitud') && $aptos) {
            return array_merge([$this->txt(implode("\n", $lineas))], $this->pedirCredito($request));
        }

        $opciones = $aptos ? [['registrar', 'Registrar solicitud'], ['otra', 'Otra consulta'], ['menu', 'Menú']]
                           : [['otra', 'Otra consulta'], ['menu', 'Menú']];
        return [$this->txt(implode("\n", $lineas)),
                $this->botones($aptos ? '¿Desea registrar una solicitud para este cliente?' : 'El cliente no tiene créditos aptos para registrar una solicitud.', $opciones)];
    }

    private function motivoNoApto(ClienteCartera $c): ?string
    {
        $excl = ['es_pignoraticio' => 'crédito pignoraticio', 'es_leasing' => 'leasing', 'es_multigas' => 'cartera Multigas',
                 'es_cartera_adq' => 'cartera adquirida', 'es_fraude' => 'registrado como fraude'];
        foreach ($excl as $campo => $txt) if ($c->$campo) return "excluido de la campaña ({$txt})";
        if ($c->es_prog_gobierno) return 'crédito con garantía de programa de gobierno';
        if (!$c->activo_campana) return 'no figura en la base de campaña del mes';
        if ($c->superaLimite()) return 'alcanzó el límite de 4 conciliaciones en 12 meses';

        $abierto = PropuestaFud::where('cliente_cartera_id', $c->id)->vigentes()
            ->whereIn('estado_fud', ['En revisión', 'Observado'])->first();
        if ($abierto) {
            return 'tiene la solicitud FUD-'.str_pad($abierto->id, 5, '0', STR_PAD_LEFT).' '.mb_strtolower($abierto->estado_fud);
        }
        return null;
    }

    private function postConsulta(Request $request, User $usuario, string $accion): array
    {
        if ($accion === 'otra') {
            $this->set($request, ['paso' => 'dni', 'modo' => 'consultar']);
            return [$this->txt('Escriba el *DNI o RUC* del cliente.')];
        }
        if ($accion === 'registrar' && $this->get($request, 'aptos')) {
            return $this->pedirCredito($request);
        }
        return [$this->menu('Elija una opción:')];
    }

    // ── Registro de solicitud ────────────────────────────────

    private function pedirCredito(Request $request): array
    {
        $aptos = ClienteCartera::whereIn('id', $this->get($request, 'aptos', []))->get();
        if ($aptos->count() === 1) {
            return $this->iniciarSolicitud($request, $aptos->first());
        }

        $this->set($request, ['paso' => 'credito']);
        return [$this->lista('El cliente tiene más de un crédito apto. Elija el crédito para la solicitud.', 'Ver créditos', [[
            'titulo' => 'Créditos aptos',
            'filas'  => $aptos->take(10)->map(fn($c) => [
                'id' => 'cred:'.$c->id,
                'titulo' => Str::limit('Op. '.$c->operacion, 24, ''),
                'descripcion' => Str::limit((RegistroFud::ETIQUETAS[$c->tipo_negociacion_campana] ?? '').' · S/ '.number_format($c->saldo_capital, 2), 72, ''),
            ])->values()->all(),
        ]])];
    }

    private function elegirCredito(Request $request, User $usuario, string $accion): array
    {
        $id = (int) Str::after($accion, 'cred:');
        if (!str_starts_with($accion, 'cred:') || !in_array($id, $this->get($request, 'aptos', []))) {
            return array_merge([$this->txt('Elija uno de los créditos de la lista.')], $this->pedirCredito($request));
        }
        return $this->iniciarSolicitud($request, ClienteCartera::findOrFail($id));
    }

    private function iniciarSolicitud(Request $request, ClienteCartera $c): array
    {
        $usuario = $this->usuario();
        $tipo = isset(RegistroFud::ETIQUETAS[$c->tipo_negociacion_campana]) ? $c->tipo_negociacion_campana : 'cancelacion';
        $apr  = $this->asignador->asignar($c, $usuario->id);

        $this->set($request, ['credito_id' => $c->id, 'tipo' => $tipo, 'cond' => [], 'adjuntos' => []]);

        $intro = $this->txt("*Nueva solicitud FUD*\nCliente: {$c->nombre_cliente}\nOperación: {$c->operacion}\nNegociación: ".RegistroFud::ETIQUETAS[$tipo]
            ."\nAprobador asignado: ".($apr ? "{$apr->name} ({$apr->rol->nombre})" : '—')
            ."\n\nEscriba *cancelar* en cualquier momento para detener el registro.");

        return [$intro, $this->siguientePregunta($request, $tipo === 'cuotas' ? 'cuotas' : ($tipo === 'cancelacion' ? 'monto' : 'pct_sk'))];
    }

    private function siguientePregunta(Request $request, string $paso): array
    {
        $this->set($request, ['paso' => $paso]);
        $c = ClienteCartera::find($this->get($request, 'credito_id'));
        return $this->txt(match ($paso) {
            'cuotas'        => "Paso 1 de 3 · ¿Qué cuotas pagará el cliente?\nEscriba el rango, por ejemplo: *3-5*",
            'desc_int'      => "Paso 2 de 3 · Porcentaje de descuento de *intereses* (0 a 100).\nEl moratorio se exonera al 100%.",
            'monto'         => 'Paso 1 de 4 · Monto que pagará el cliente en soles. Saldo capital: S/ '.number_format($c->saldo_capital, 2),
            'desc_int_canc' => 'Paso 2 de 4 · Porcentaje de descuento de *intereses* (0 a 100).',
            'desc_cap'      => "Paso 3 de 4 · Porcentaje de descuento de *capital* (0 a 100).\nEscriba *0* si no aplica.",
            'pct_sk'        => 'Paso 1 de 2 · Porcentaje de beneficio propuesto sobre el *saldo capital* (0 a 100).',
            'motivo'        => "Último paso · Describa el *sustento* de la propuesta: situación del cliente, voluntad de pago y gestiones realizadas (mínimo 20 caracteres).",
        });
    }

    private function recibirCondicion(Request $request, string $paso, string $texto): array
    {
        $cond = $this->get($request, 'cond', []);
        $c = ClienteCartera::find($this->get($request, 'credito_id'));

        if ($paso === 'cuotas') {
            if (!preg_match('/(\d+)\s*(?:-|a|al|hasta)?\s*(\d+)?/i', $texto, $m)) {
                return [$this->txt('No reconocí el rango. Escríbalo así: *3-5* (o un solo número si es una cuota).')];
            }
            $desde = (int) $m[1]; $hasta = (int) ($m[2] ?? $m[1]);
            if ($desde < 1 || $hasta < $desde || $hasta > 360) return [$this->txt('El rango de cuotas no es válido. Ejemplo: *3-5*.')];
            $cond += ['cuota_desde' => $desde, 'cuota_hasta' => $hasta];
            $this->set($request, ['cond' => $cond]);
            return [$this->siguientePregunta($request, 'desc_int')];
        }

        $n = $this->numero($texto);
        if ($paso === 'monto') {
            if ($n === null || $n <= 0) return [$this->txt('Escriba un monto válido en soles, por ejemplo: *8500*.')];
            if ($n > (float) $c->saldo_capital * 1.5) return [$this->txt('El monto parece excesivo frente al saldo capital. Verifique y escríbalo nuevamente.')];
            $this->set($request, ['cond' => $cond + ['monto_a_pagar' => round($n, 2)]]);
            return [$this->siguientePregunta($request, 'desc_int_canc')];
        }

        if ($n === null || $n < 0 || $n > 100) return [$this->txt('Escriba un porcentaje entre 0 y 100, por ejemplo: *40*.')];
        $campo = ['desc_int' => 'desc_interes', 'desc_int_canc' => 'desc_interes_canc', 'desc_cap' => 'desc_capital', 'pct_sk' => 'beneficio_pct_sk'][$paso];
        $this->set($request, ['cond' => $cond + [$campo => $n]]);

        return [$this->siguientePregunta($request, $paso === 'desc_int_canc' ? 'desc_cap' : 'motivo')];
    }

    private function recibirMotivo(Request $request, string $texto): array
    {
        if (mb_strlen($texto) < 20) {
            return [$this->txt('El sustento debe tener al menos 20 caracteres ('.mb_strlen($texto).' de 20). Escríbalo con más detalle.')];
        }
        $this->set($request, ['motivo' => $texto, 'paso' => 'adjuntos']);
        return [$this->botones("Puede adjuntar documentos de la negociación (PDF, JPG o PNG) con el clip.\nCuando termine, pulse *Continuar*.",
            [['continuar', 'Continuar'], ['cancelar', 'Cancelar']])];
    }

    private function recibirAdjunto(Request $request, string $accion): array
    {
        if ($request->hasFile('archivo')) {
            $f = $request->file('archivo');
            if (!in_array(strtolower($f->getClientOriginalExtension()), ['pdf', 'jpg', 'jpeg', 'png'])) {
                return [$this->txt('Formato no admitido. Envíe el documento en PDF, JPG o PNG.')];
            }
            $adj = $this->get($request, 'adjuntos', []);
            $adj[] = ['ruta' => $f->store('fud/canal-whatsapp', 'private'), 'nombre' => $f->getClientOriginalName(), 'size' => $f->getSize()];
            $this->set($request, ['adjuntos' => $adj]);
            return [$this->botones('Documento recibido: *'.$f->getClientOriginalName().'* ('.count($adj).' en total). Puede enviar otro o continuar.',
                [['continuar', 'Continuar'], ['cancelar', 'Cancelar']])];
        }
        if ($accion !== 'continuar') {
            return [$this->botones('Envíe un documento con el clip o pulse *Continuar*.', [['continuar', 'Continuar'], ['cancelar', 'Cancelar']])];
        }

        $this->set($request, ['paso' => 'confirmar']);
        return [$this->resumen($request)];
    }

    private function resumen(Request $request): array
    {
        $c = ClienteCartera::find($this->get($request, 'credito_id'));
        $tipo = $this->get($request, 'tipo');
        $cond = $this->get($request, 'cond', []);
        $apr = $this->asignador->asignar($c, $this->usuario()->id);
        $p = fn($v) => rtrim(rtrim(number_format((float) $v, 2), '0'), '.').'%';

        $l = ["*Resumen de la solicitud*", '', "Cliente: {$c->nombre_cliente}", "Operación: {$c->operacion}", 'Negociación: '.RegistroFud::ETIQUETAS[$tipo]];
        if ($tipo === 'cuotas') {
            $l[] = "Cuotas: {$cond['cuota_desde']} a {$cond['cuota_hasta']}";
            $l[] = 'Descuento: '.$p($cond['desc_interes']).' intereses · 100% moratorio';
        } elseif ($tipo === 'cancelacion') {
            $l[] = 'Monto a pagar: S/ '.number_format($cond['monto_a_pagar'], 2);
            $l[] = 'Descuento: '.$p($cond['desc_interes_canc']).' intereses · '.$p($cond['desc_capital']).' capital';
        } else {
            $l[] = 'Beneficio sobre saldo capital: '.$p($cond['beneficio_pct_sk']);
        }
        $l[] = 'Aprobador: '.($apr ? "{$apr->name} ({$apr->rol->nombre})" : '—');
        $l[] = 'Adjuntos: '.count($this->get($request, 'adjuntos', [])).' documento(s)';
        $l[] = '';
        $l[] = '_Sustento:_ '.Str::limit($this->get($request, 'motivo'), 280);

        return $this->botones(implode("\n", $l), [['confirmar', 'Confirmar envío'], ['cancelar', 'Cancelar']]);
    }

    private function confirmar(Request $request, User $usuario, string $accion): array
    {
        if ($accion !== 'confirmar') return [$this->resumen($request)];

        $c = ClienteCartera::findOrFail($this->get($request, 'credito_id'));
        $tipo = $this->get($request, 'tipo');
        $datos = $this->get($request, 'cond', []) + [
            'tipo_negociacion' => $tipo,
            'motivo'           => $this->get($request, 'motivo'),
            'tipo_operacion'   => 'CAMPAÑA',
            'cierra_mes_vigente' => true,
            'mayor_tres_uit'   => $c->esMayor3UIT(),
            'fecha_limite'     => $tipo === 'cancelacion' ? now()->addDays(30)->toDateString() : null,
            'etapa_procesal'   => $tipo === 'cancelacion' || $tipo === 'cuotas' ? null : 'Pre-judicial',
        ];

        try {
            $fud = $this->registro->registrar($c, $usuario, $datos, $this->get($request, 'adjuntos', []), 'whatsapp');
        } catch (\DomainException $e) {
            $this->limpiar($request);
            return [$this->txt('No se pudo registrar la solicitud: '.$e->getMessage()), $this->menu('¿Qué desea hacer?')];
        }

        $this->limpiar($request);
        $codigo = 'FUD-'.str_pad($fud->id, 5, '0', STR_PAD_LEFT);
        return [
            $this->txt("Solicitud *{$codigo}* registrada.\n\nEnviada a *{$fud->aprobador->name}* ({$fud->aprobador->rol->nombre}) para su aprobación.\nEstado: *En revisión*.\n\nLa encontrará también en RCT web, en _Mis propuestas_. Le avisaremos cuando haya una decisión."),
            $this->botones('¿Desea hacer algo más?', [['estado', 'Mis solicitudes'], ['consultar', 'Consultar cliente'], ['menu', 'Menú']]),
        ];
    }

    // ── Estado de solicitudes ────────────────────────────────

    private function listarEstado(Request $request, User $usuario): array
    {
        $fuds = PropuestaFud::with('cliente')->porEnviador($usuario->id)->vigentes()
            ->orderByDesc('id')->limit(10)->get();
        if ($fuds->isEmpty()) return [$this->menu('Aún no tiene solicitudes registradas.')];

        $this->set($request, ['paso' => 'estado']);
        return [$this->lista('Sus últimas solicitudes. Elija una para ver el detalle.', 'Ver solicitudes', [[
            'titulo' => 'Solicitudes recientes',
            'filas'  => $fuds->map(fn($f) => [
                'id' => 'fud:'.$f->id,
                'titulo' => 'FUD-'.str_pad($f->id, 5, '0', STR_PAD_LEFT).' · '.$f->estado_fud,
                'descripcion' => Str::limit($f->cliente->nombre_cliente.' · '.$f->fecha_envio->format('d/m/Y'), 72, ''),
            ])->values()->all(),
        ]])];
    }

    private function detalleFud(User $usuario, string $accion, string $texto): array
    {
        $id = (int) (str_starts_with($accion, 'fud:') ? Str::after($accion, 'fud:') : preg_replace('/\D/', '', $texto));
        $raiz = PropuestaFud::find($id);
        $f = $raiz?->ultimaVersion();
        if (!$f || $raiz->enviado_por !== $usuario->id) {
            return [$this->menu('No encontré esa solicitud entre las suyas.')];
        }

        $sus = $f->sustentos()->latest('id')->first();
        $l = ['*FUD-'.str_pad($f->id, 5, '0', STR_PAD_LEFT).'* · '.$f->tipo_negociacion_desc,
              'Cliente: '.$f->cliente->nombre_cliente, 'Operación: '.$f->cliente->operacion,
              'Enviada: '.$f->fecha_envio->format('d/m/Y'), 'Estado: *'.$f->estado_fud.'*',
              'Aprobador: '.($f->aprobadoPor->name ?? $f->aprobador->name ?? '—')];
        if ($f->decidido_en) $l[] = 'Decisión: '.$f->decidido_en->format('d/m/Y H:i');
        if ($f->comentario_aprobador) $l[] = 'Comentario: _'.$f->comentario_aprobador.'_';
        if ($sus) $l[] = 'Sustentos: '.($sus->estado === 'Aprobado' ? 'conformes' : mb_strtolower($sus->estado));
        if ($f->estaObservado()) $l[] = "\nPara corregirla ingrese a RCT web, en _Mis propuestas_.";

        return [$this->txt(implode("\n", $l)), $this->botones('¿Desea hacer algo más?', [['estado', 'Mis solicitudes'], ['menu', 'Menú']])];
    }

    // ── Mensajes (formatos permitidos por WhatsApp Business) ─

    private function saludo(User $u): array
    {
        $h = (int) now()->format('G');
        $s = $h < 12 ? 'Buenos días' : ($h < 19 ? 'Buenas tardes' : 'Buenas noches');
        return $this->menu("{$s}, *{$u->name}*.\nBienvenido al canal de Recuperaciones de Caja Trujillo. ¿Qué desea hacer?");
    }

    private function menu(string $texto): array
    {
        return $this->lista($texto, 'Ver opciones', [[
            'titulo' => 'Opciones',
            'filas'  => [
                ['id' => 'consultar', 'titulo' => 'Consultar cliente', 'descripcion' => 'Ver si un cliente es apto por DNI o RUC'],
                ['id' => 'solicitud', 'titulo' => 'Registrar solicitud', 'descripcion' => 'Nueva propuesta de conciliación (FUD)'],
                ['id' => 'estado',    'titulo' => 'Mis solicitudes', 'descripcion' => 'Estado de sus solicitudes recientes'],
                ['id' => 'ayuda',     'titulo' => 'Ayuda', 'descripcion' => 'Qué puede hacer en este canal'],
            ],
        ]]);
    }

    private function txt(string $t): array
    {
        return ['tipo' => 'texto', 'texto' => $t];
    }

    private function botones(string $t, array $botones): array
    {
        return ['tipo' => 'botones', 'texto' => $t,
                'botones' => array_map(fn($b) => ['id' => $b[0], 'titulo' => Str::limit($b[1], 20, '')], array_slice($botones, 0, 3))];
    }

    private function lista(string $t, string $boton, array $secciones): array
    {
        return ['tipo' => 'lista', 'texto' => $t, 'boton' => Str::limit($boton, 20, ''), 'secciones' => $secciones];
    }

    // ── Estado de la conversación ────────────────────────────

    private function usuario(): User
    {
        return User::with('rol')->where('email', config('rct.whatsapp.usuario'))->firstOrFail();
    }

    private function numero(string $t): ?float
    {
        $t = str_replace(['S/', 's/', '%', ' '], '', $t);
        $t = preg_replace('/,(?=\d{3}(\D|$))/', '', $t);   // separador de miles
        $t = str_replace(',', '.', $t);
        return is_numeric($t) ? (float) $t : null;
    }

    private function get(Request $r, string $k, $def = null)
    {
        return $r->session()->get(self::SESION.'.datos.'.$k, $def);
    }

    private function set(Request $r, array $valores): void
    {
        foreach ($valores as $k => $v) {
            if ($k === 'paso') $r->session()->put(self::SESION.'.paso', $v);
            else $r->session()->put(self::SESION.'.datos.'.$k, $v);
        }
    }

    private function limpiar(Request $r): void
    {
        $r->session()->put(self::SESION.'.paso', 'menu');
        $r->session()->forget(self::SESION.'.datos');
    }

    private function guardar(Request $r, array $msg): void
    {
        $h = $r->session()->get(self::SESION.'.historial', []);
        $h[] = $msg + ['hora' => now()->format('H:i')];
        $r->session()->put(self::SESION.'.historial', array_slice($h, -80));
    }
}
