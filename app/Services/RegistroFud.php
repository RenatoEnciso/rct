<?php
namespace App\Services;

use App\Models\{ClienteCartera, FudAdjunto, FudDetalleCancelacion, FudDetalleCuotas, FudDetalleJudicial, PropuestaFud, Seguimiento, User};
use Illuminate\Http\UploadedFile;

/**
 * Registro de una propuesta FUD, común a la web y al canal de mensajería:
 * valida el límite de usos, asigna el aprobador, guarda detalle, adjuntos y bitácora.
 */
class RegistroFud
{
    public const ETIQUETAS = [
        'cuotas'        => 'Pago de Cuotas',
        'cancelacion'   => 'Cancelación Total',
        'extrajudicial' => 'Cartera Extrajudicial / Judicial',
        'castigada'     => 'Cartera Castigada',
        'focmac'        => 'Cartera FOCMAC',
    ];

    public function __construct(private AsignadorAprobador $asignador) {}

    /**
     * @param array $datos     tipo_negociacion, motivo, tipo_operacion y campos del detalle
     * @param array $adjuntos  UploadedFile o ['ruta', 'nombre', 'size'] ya guardados en el disco privado
     * @throws \DomainException con un mensaje para el usuario
     */
    public function registrar(ClienteCartera $cliente, User $user, array $datos, array $adjuntos = [], string $canal = 'web'): PropuestaFud
    {
        if ($cliente->superaLimite()) {
            throw new \DomainException('Este cliente ya alcanzó el límite de 4 conciliaciones en 12 meses.');
        }

        $nivel     = $this->asignador->nivelRequerido($cliente);
        $aprobador = $this->asignador->asignar($cliente, $user->id);
        if (!$aprobador) {
            throw new \DomainException('No hay un aprobador disponible para el nivel de autonomía de este crédito.');
        }

        $tipo = $datos['tipo_negociacion'];
        $fud = PropuestaFud::create([
            'cliente_cartera_id'    => $cliente->id,
            'enviado_por'           => $user->id,
            'aprobador_id'          => $aprobador->id,
            'tipo_negociacion'      => $tipo,
            'tipo_negociacion_desc' => $datos['tipo_negociacion_desc'] ?? self::ETIQUETAS[$tipo] ?? $tipo,
            'en_campana'            => true,
            'tipo_operacion'        => $datos['tipo_operacion'] ?? 'CAMPAÑA',
            'motivo'                => $datos['motivo'],
            'nivel_autonomia_req'   => $nivel,
            'estado_fud'            => 'En revisión',
            'fecha_envio'           => now()->toDateString(),
        ]);

        self::guardarDetalle($tipo, $fud->id, $datos);

        foreach ($adjuntos as $a) {
            if ($a instanceof UploadedFile) {
                if (!$a->isValid()) continue;
                $a = ['ruta' => $a->store("fud/{$fud->id}/adjuntos", 'private'), 'nombre' => $a->getClientOriginalName(), 'size' => $a->getSize()];
            }
            FudAdjunto::create([
                'propuesta_fud_id' => $fud->id,
                'subido_por'       => $user->id,
                'nombre_archivo'   => $a['nombre'],
                'ruta_archivo'     => $a['ruta'],
                'tipo_adjunto'     => $datos['tipo_adjunto'] ?? 'Evidencia negociación',
                'size_bytes'       => $a['size'] ?? null,
            ]);
        }

        Seguimiento::registrar(
            $user->id, 'FUD enviado', 'En revisión', $fud->id,
            $cliente->nombre_cliente, $cliente->operacion,
            $canal === 'whatsapp' ? 'Registrado desde el canal WhatsApp.' : null
        );

        return $fud->setRelation('aprobador', $aprobador);
    }

    public static function guardarDetalle(string $tipo, int $fudId, array $d): void
    {
        $si = fn($k, $def = false) => array_key_exists($k, $d) ? filter_var($d[$k], FILTER_VALIDATE_BOOLEAN) : $def;

        match ($tipo) {
            'cuotas' => FudDetalleCuotas::create([
                'propuesta_fud_id'   => $fudId,
                'cuota_desde'        => $d['cuota_desde'] ?? null,
                'cuota_hasta'        => $d['cuota_hasta'] ?? null,
                'desc_interes'       => $d['desc_interes'] ?? null,
                'desc_moratorio'     => $d['desc_moratorio'] ?? 100,
                'pone_al_dia'        => $si('pone_al_dia'),
                'cierra_mes_vigente' => $si('cierra_mes_vigente', true),
            ]),
            'cancelacion' => FudDetalleCancelacion::create([
                'propuesta_fud_id' => $fudId,
                'monto_a_pagar'    => $d['monto_a_pagar'] ?? null,
                'desc_interes'     => $d['desc_interes_canc'] ?? null,
                'desc_capital'     => $d['desc_capital'] ?? 0,
                'fecha_limite'     => $d['fecha_limite'] ?? null,
                'mayor_tres_uit'   => $si('mayor_tres_uit'),
                'sunarp_positivo'  => $si('sunarp_positivo'),
            ]),
            default => FudDetalleJudicial::create([
                'propuesta_fud_id' => $fudId,
                'monto_gravamen'   => $d['monto_gravamen'] ?? null,
                'etapa_procesal'   => $d['etapa_procesal'] ?? null,
                'beneficio_pct_sk' => $d['beneficio_pct_sk'] ?? null,
            ]),
        };
    }
}
