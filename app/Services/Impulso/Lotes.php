<?php
namespace App\Services\Impulso;

use App\Contracts\FuenteCore;
use App\Models\{CandidatoHonramiento, ExpedienteGobierno, Lote, Seguimiento, User};
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Etapa 1 — Solicitud de honramiento por lotes:
 * candidatos a 90 días (según el core) → armado del lote → documentos → firma digital → confirmación de COFIDE.
 */
class Lotes
{
    public function __construct(private FuenteCore $core) {}

    /** Créditos del programa con ≥ 90 días de atraso que no están en ningún lote. */
    public function candidatos(): Collection
    {
        $enLote = ExpedienteGobierno::whereNotNull('credito_sicmact')->pluck('credito_sicmact')->flip();
        $dias = (int) config('rct.impulso.dias_candidato', 90);
        return $this->core->creditosPrograma(config('rct.impulso.programa'))
            ->filter(fn($c) => $c->dias_atraso >= $dias && !isset($enLote[$c->credito_sicmact]))
            ->sortByDesc('dias_atraso')->values();
    }

    /** Créditos que llegarán a 90 días en los próximos $ventana días. */
    public function proximos(int $ventana = 15): Collection
    {
        $dias = (int) config('rct.impulso.dias_candidato', 90);
        return $this->core->creditosPrograma(config('rct.impulso.programa'))
            ->filter(fn($c) => $c->dias_atraso < $dias && $c->dias_atraso >= $dias - $ventana)
            ->sortByDesc('dias_atraso')->values();
    }

    /** Registra los candidatos nuevos detectados en el core; devuelve los que se detectaron ahora. */
    public function detectarCandidatos(): Collection
    {
        $existentes = CandidatoHonramiento::pluck('credito_sicmact')->flip();
        return $this->candidatos()->reject(fn($c) => isset($existentes[$c->credito_sicmact]))
            ->map(fn($c) => CandidatoHonramiento::create([
                'credito_sicmact' => $c->credito_sicmact, 'dias_atraso' => $c->dias_atraso, 'detectado_en' => now(),
            ]))->values();
    }

    /** Arma un lote con los créditos indicados (corte = hoy). Crea un expediente por crédito. */
    public function armar(array $creditos, User $usuario, ?Carbon $corte = null): Lote
    {
        $corte ??= now();
        $candidatos = $this->candidatos()->whereIn('credito_sicmact', $creditos)->values();
        throw_if($candidatos->isEmpty(), new \DomainException('Seleccione al menos un candidato.'));

        return DB::transaction(function () use ($candidatos, $usuario, $corte) {
            $lote = Lote::create([
                'codigo' => $this->siguienteCodigo(), 'programa' => config('rct.impulso.programa'),
                'fecha_corte' => $corte->toDateString(), 'estado' => 'En preparación', 'creado_por' => $usuario->id,
            ]);
            $id = $this->siguienteIdExterno();
            foreach ($candidatos as $c) {
                $e = ExpedienteGobierno::create([
                    'id_externo' => (string) $id++, 'dni' => $c->nro_documento, 'tipo_persona' => $c->tipo_persona,
                    'credito_sicmact' => $c->credito_sicmact, 'cuenta_bt' => $c->cuenta, 'operacion' => $c->operacion,
                    'cliente' => $c->cliente, 'zona_id' => $c->zona_id, 'tienda_id' => $c->tienda_id, 'lote_id' => $lote->id,
                    'programa' => $lote->programa, 'registrado_por' => $usuario->id, 'fecha_registro' => $corte->toDateString(),
                    'estado' => 'Pendiente', 'estado_honramiento' => 'Solicitado',
                    'saldo_insoluto' => $c->saldo_capital, 'interes_devengado' => $c->interes_devengado,
                    'factor_cobertura' => $c->factor_cobertura,
                    'monto_honrado' => Calculos::montoHonrar($c->saldo_capital, $c->interes_devengado, $c->factor_cobertura ?? 0),
                ]);
                $e->recalcularEstado();
            }
            $this->recalcularTotales($lote);
            Seguimiento::registrar($usuario->id, 'Lote de honramiento', 'En preparación', null, null, $lote->codigo,
                "Lote {$lote->codigo} armado con {$lote->registros_rct} créditos (corte {$corte->format('d/m/Y')}).");
            return $lote;
        });
    }

    public function recalcularTotales(Lote $lote): void
    {
        $exp = $lote->expedientes()->get(['saldo_insoluto']);
        $lote->update(['registros_rct' => $exp->count(), 'saldo_rct' => Calculos::sumar($exp->pluck('saldo_insoluto'))]);
    }

    /** Se generaron la notificación (Word) y la plantilla (Excel): el lote queda pendiente de firma. */
    public function marcarDocumentos(Lote $lote): void
    {
        $lote->update(['documentos_generados_en' => now(),
            'estado' => $lote->estado === 'En preparación' ? 'Pendiente de firma' : $lote->estado]);
    }

    /** Registra la autorización de un representante ante COFIDE para usar su firma digital. */
    public function autorizarFirma(Lote $lote, int $indice, User $usuario): void
    {
        $rep = config('rct.impulso.representantes')[$indice] ?? null;
        throw_unless($rep, new \DomainException('Representante no válido.'));
        throw_unless($lote->documentos_generados_en, new \DomainException('Genere primero la notificación y la plantilla del lote.'));

        $firmas = collect($lote->firmas ?? [])->reject(fn($f) => $f['nombre'] === $rep['nombre'])->values()->all();
        $firmas[] = $rep + ['autorizado_en' => now()->format('Y-m-d H:i'), 'registrado_por' => $usuario->name];
        $lote->firmas = $firmas;
        if ($lote->firmasCompletas() && in_array($lote->estado, ['En preparación', 'Pendiente de firma'])) $lote->estado = 'Firmado';
        $lote->save();
        Seguimiento::registrar($usuario->id, 'Firma digital de lote', $lote->estado, null, null, $lote->codigo,
            "{$rep['nombre']} ({$rep['cargo']}) autorizó el uso de su firma digital para el lote {$lote->codigo}.");
    }

    /**
     * Confirmación de la plataforma de COFIDE: N° de registros y saldo insoluto total.
     * Si coinciden con RCT → Cargado en COFIDE (inicia el plazo de documentación); si no → Observado.
     */
    public function confirmar(Lote $lote, int $registros, string $saldo, Carbon $fechaCarga, ?string $constancia, User $usuario): bool
    {
        throw_unless($lote->firmasCompletas(), new \DomainException('Faltan autorizaciones de firma digital.'));
        $lote->fill([
            'registros_cofide' => $registros, 'saldo_cofide' => $saldo, 'fecha_carga' => $fechaCarga->toDateString(),
            'constancia_cofide' => $constancia, 'confirmado_en' => now(),
        ]);
        $ok = !$lote->tieneDiferencia();
        $lote->estado = $ok ? 'Cargado en COFIDE' : 'Observado';
        if ($ok) $lote->fecha_conformidad = $fechaCarga->toDateString();
        $lote->save();

        Seguimiento::registrar($usuario->id, 'Confirmación COFIDE', $lote->estado, null, null, $lote->codigo, $ok
            ? "COFIDE confirmó {$registros} registros y el saldo insoluto del lote {$lote->codigo}: coincide con RCT."
            : "COFIDE confirmó {$registros} registros (RCT: {$lote->registros_rct}) y saldo {$saldo} (RCT: {$lote->saldo_rct}): hay diferencias.");
        return $ok;
    }

    private function siguienteCodigo(): string
    {
        $n = Lote::pluck('codigo')->map(fn($c) => (int) $c)->max() ?? 0;
        return str_pad((string) ($n + 1), 3, '0', STR_PAD_LEFT).' Lote';
    }

    private function siguienteIdExterno(): int
    {
        return (int) (ExpedienteGobierno::whereRaw("id_externo REGEXP '^[0-9]+$'")->max(DB::raw('CAST(id_externo AS UNSIGNED)')) ?? 100000) + 1;
    }
}
