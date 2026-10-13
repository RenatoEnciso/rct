<?php
namespace App\Services\Impulso;

use App\Models\{Lote, User};
use App\Notifications\AvisoImpulso;
use App\Support\Estilo;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;

/**
 * Avisos del módulo Impulso MyPerú (sección 5 de docs/PROCESO_IMPULSO_MYPERU.md):
 * aviso en pantalla (toast), campana y correo, según el evento y el destinatario.
 * "Revisor de programas" y "Contabilidad" son parámetros (config rct.impulso.revisores / contabilidad).
 */
class Avisos
{
    /** Revisor de programas (hoy lo cumple la Jefatura de Recuperaciones). */
    public function revisores(): Collection
    {
        return User::whereIn('email', (array) config('rct.impulso.revisores', []))->get();
    }

    public function jefes(): Collection
    {
        return User::whereHas('rol', fn($q) => $q->where('nombre', 'Jefe de Recuperaciones'))->get();
    }

    /** Agencias de la zona: usuarios de sus tiendas (opcionalmente solo las indicadas) y el coordinador zonal. */
    public function agenciasZona(int $zonaId, ?array $tiendas = null): Collection
    {
        return User::with('rol')->whereHas('tienda', fn($q) => $q->where('zona_id', $zonaId))->get()
            ->filter(fn($u) => $u->esGrupoFud() ? ($tiendas === null || in_array($u->tienda_id, $tiendas)) : $u->rol->nombre === 'Coordinador Zonal de Recuperaciones')
            ->values();
    }

    /** Envía el aviso a los usuarios (sin repetir) y, si corresponde, a correos externos (solo correo). */
    public function enviar(iterable $usuarios, array $d, array $correosExtra = []): void
    {
        $lista = collect($usuarios)->filter()->unique('id')->values();
        if ($lista->isNotEmpty()) Notification::send($lista, new AvisoImpulso($d));
        if ($correosExtra && ($d['correo'] ?? false)) {
            Notification::route('mail', $correosExtra)->notify(new AvisoImpulso(['campana' => false] + $d));
        }
    }

    // ── Eventos ──────────────────────────────────────────────

    /** Pago detectado en el core para un crédito del programa. */
    public function pagoSincronizado(\App\Models\PagoPrograma $pago): void
    {
        $cliente = $pago->expediente->cliente ?? $pago->credito_sicmact;
        if ($pago->estado === 'Agregado') {
            $this->enviar($this->revisores()->merge($this->jefes()), [
                'evento' => 'pago_honrado', 'titulo' => 'Pago detectado: '.$cliente,
                'mensaje' => Estilo::solesPe($pago->capital).' a capital → Reversión COFIDE '.Estilo::solesPe($pago->reversion),
                'url' => route('impulso.pagos', ['q' => $pago->credito_sicmact]), 'icono' => 'bi-cash-coin', 'color' => 'ok',
                'toast' => true, 'correo' => true,
                'detalle' => [['Cliente', $cliente], ['Crédito', $pago->credito_sicmact], ['Fecha del pago', $pago->fecha_pago->format('d/m/Y H:i')],
                              ['Monto pagado', Estilo::solesPe($pago->monto_total)], ['Capital', Estilo::solesPe($pago->capital)],
                              ['Factor de cobertura', number_format($pago->factor_cobertura * 100, 2, ',', '').'%'],
                              ['Reversión a COFIDE', Estilo::solesPe($pago->reversion)], ['Canal', $pago->canal ?? '—']],
            ]);
        } else {
            $this->enviar($this->revisores(), [
                'evento' => 'pago_sin_reversion', 'titulo' => 'Pago sin reversión: '.$cliente,
                'mensaje' => Estilo::solesPe($pago->capital).' a capital. '.$pago->motivo.'.',
                'url' => route('impulso.pagos', ['q' => $pago->credito_sicmact]), 'icono' => 'bi-cash', 'color' => 'info', 'toast' => true,
            ]);
        }
    }

    /** Créditos que llegaron a 90 días: campana y correo al revisor. */
    public function candidatos(Collection $nuevos): void
    {
        if ($nuevos->isEmpty()) return;
        $n = $nuevos->count();
        $this->enviar($this->revisores(), [
            'evento' => 'candidatos', 'titulo' => $n === 1 ? '1 crédito llegó a 90 días de atraso' : "{$n} créditos llegaron a 90 días de atraso",
            'mensaje' => 'Son candidatos a solicitud de honramiento ante COFIDE. Inclúyalos en el próximo lote.',
            'url' => route('impulso.candidatos'), 'icono' => 'bi-hourglass-split', 'color' => 'warn', 'correo' => true,
            'detalle' => $nuevos->take(10)->map(fn($c) => [$c->credito_sicmact, $c->dias_atraso.' días de atraso'])->all(),
        ]);
    }

    /** La confirmación de COFIDE no coincide con RCT: toast, campana y correo al revisor y al Jefe. */
    public function loteConDiferencia(Lote $lote): void
    {
        $this->enviar($this->revisores()->merge($this->jefes()), [
            'evento' => 'lote_diferencia', 'titulo' => "{$lote->nombre()}: la confirmación de COFIDE no coincide",
            'mensaje' => "COFIDE confirmó {$lote->registros_cofide} registros y ".Estilo::solesPe($lote->saldo_cofide)
                .'; RCT calculó '.$lote->registros_rct.' registros y '.Estilo::solesPe($lote->saldo_rct).'. Revise la carga antes de continuar.',
            'url' => route('impulso.lotes.show', $lote), 'icono' => 'bi-exclamation-triangle', 'color' => 'danger',
            'toast' => true, 'correo' => true,
            'detalle' => [['Registros COFIDE / RCT', $lote->registros_cofide.' / '.$lote->registros_rct],
                          ['Diferencia de saldo', Estilo::solesPe($lote->diferenciaSaldo())]],
        ]);
    }

    /**
     * Lote honrado (reporte importado): toast, campana y correo al revisor;
     * campana y correo a las agencias de cada zona con sus créditos pendientes de documentación.
     */
    public function loteHonrado(Lote $lote): void
    {
        $exp = $lote->expedientes()->with('tienda')->get();
        $honrados = $exp->where('estado_honramiento', 'Honrado');
        $this->enviar($this->revisores(), [
            'evento' => 'lote_honrado', 'titulo' => "{$lote->nombre()} honrado por COFIDE",
            'mensaje' => $honrados->count().' créditos honrados por '.Estilo::solesPe(Calculos::sumar($honrados->pluck('monto_honrado')))
                .'. Se avisó a las agencias de cada zona para completar la documentación.',
            'url' => route('impulso.lotes.show', $lote), 'icono' => 'bi-bank', 'color' => 'ok', 'toast' => true, 'correo' => true,
        ]);

        foreach ($this->resumenZonas($lote) as $z) {
            $this->enviar($this->agenciasZona($z['zona_id'], $z['tiendas_ids']), [
                'evento' => 'aviso_agencias', 'titulo' => "{$lote->nombre()} honrado: {$z['pendientes']} expedientes por documentar en su zona",
                'mensaje' => "COFIDE honró {$z['honrados']} créditos de la zona {$z['zona']} por ".Estilo::solesPe($z['monto'])
                    .'. Cargue los documentos en PDF, un documento por archivo, con el nombre '.self::formatoArchivo().', dentro del plazo de '.Lote::PLAZO_DIAS.' días.',
                'url' => route('gobierno.index', ['lote_id' => $lote->id]), 'icono' => 'bi-folder2-open', 'color' => 'info', 'correo' => true,
                'detalle' => collect($z['por_tienda'])->map(fn($t) => [$t['tienda'], $t['pendientes'].' pendientes · '.Estilo::solesPe($t['monto'])])->values()->all(),
            ]);
        }
        $lote->update(['aviso_agencias_en' => now()]);
    }

    /** Créditos honrados del lote por zona y tienda (para el aviso a agencias). */
    public function resumenZonas(Lote $lote): Collection
    {
        $exp = $lote->expedientes()->with('zona', 'tienda')->where('estado_honramiento', 'Honrado')->get();
        return $exp->groupBy('zona_id')->map(fn($g, $zid) => [
            'zona_id'     => (int) $zid,
            'zona'        => $g->first()->zona->nombre ?? '—',
            'honrados'    => $g->count(),
            'pendientes'  => $g->whereNotIn('estado', ['Completo', 'Revisado por zona', 'Conforme'])->count(),
            'monto'       => Calculos::sumar($g->pluck('monto_honrado')),
            'tiendas_ids' => $g->pluck('tienda_id')->unique()->values()->all(),
            'por_tienda'  => $g->groupBy('tienda_id')->map(fn($t) => [
                'tienda' => $t->first()->tienda->nombre ?? '—', 'honrados' => $t->count(),
                'pendientes' => $t->whereNotIn('estado', ['Completo', 'Revisado por zona', 'Conforme'])->count(),
                'monto' => Calculos::sumar($t->pluck('monto_honrado')),
            ])->sortBy('tienda')->values()->all(),
        ])->sortBy('zona')->values();
    }

    /** Formato de nombre de archivo que se comunica a las agencias (supuesto, configurable). */
    public static function formatoArchivo(): string
    {
        return config('rct.impulso.formato_archivo', '{N° CRÉDITO}_{DOCUMENTO}.pdf');
    }
}
