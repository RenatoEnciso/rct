<?php
namespace App\Services\Analitica;

use App\Contracts\Analitica\{DetectorAnomalias, ModeloPriorizacion, ModeloRecaida};
use App\Models\{AlertaControl, Campana, ClienteCartera, MetaZona, PriorizacionCartera, PropuestaFud, Recupero, SeguimientoBeneficio};
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Precalcula la analítica y la guarda en tablas (las pantallas solo leen).
 * Determinista: con los mismos datos produce siempre los mismos resultados.
 * Uso: php artisan rct:analitica-recalcular
 */
class GeneradorAnalitica
{
    public function __construct(
        private CalculadoraRecupero $calculadora,
        private ModeloPriorizacion $priorizacion,
        private ModeloRecaida $recaida,
        private DetectorAnomalias $anomalias,
    ) {}

    public function generar(?Carbon $hoy = null): array
    {
        $hoy ??= now()->startOfDay();
        return DB::transaction(fn() => [
            'recuperos'    => $this->recuperos(),
            'metas'        => $this->metas($hoy),
            'priorizacion' => $this->priorizar($hoy),
            'seguimiento'  => $this->seguimiento($hoy),
            'alertas'      => $this->alertas($hoy),
        ]);
    }

    /** FUD aprobados con sustento aprobado = pagados. La fecha de pago es la de carga del sustento. */
    private function recuperos(): int
    {
        Recupero::query()->delete();
        $fuds = PropuestaFud::vigentes()->aprobados()
            ->whereHas('sustentos', fn($q) => $q->where('estado', 'Aprobado'))
            ->with('cliente', 'detalleCuotas', 'detalleCancelacion', 'detalleJudicial', 'sustentos')->get();

        foreach ($fuds as $f) {
            $s = $f->sustentos->firstWhere('estado', 'Aprobado');
            Recupero::create(array_merge([
                'propuesta_fud_id'   => $f->id,
                'cliente_cartera_id' => $f->cliente_cartera_id,
                'zona_id'            => $f->cliente->zona_id,
                'tienda_id'          => $f->cliente->tienda_id,
                'asesor_id'          => $f->enviado_por,
                'tipo_negociacion'   => $f->tipo_negociacion,
                'fecha_pago'         => $s->fecha_carga ?? $f->fecha_decision,
            ], $this->calculadora->calcular($f)));
        }
        return $fuds->count();
    }

    /**
     * Meta mensual por zona: promedio mensual recuperado de la zona (antes de la campaña para los
     * meses previos; en campaña para los meses de campaña), con una variación fija por zona y mes.
     */
    private function metas(Carbon $hoy): int
    {
        MetaZona::query()->delete();
        $campana = Campana::where('activa', true)->first();
        $inicioCamp = Carbon::parse($campana->fecha_inicio ?? '2026-09-01')->startOfMonth();
        $finCamp = Carbon::parse($campana->fecha_fin ?? '2027-02-28')->startOfMonth();

        $n = 0;
        foreach (Recupero::all()->groupBy('zona_id') as $zonaId => $recs) {
            $promedio = function ($c) {
                $meses = max(1, $c->map(fn($r) => $r->fecha_pago->format('Y-m'))->unique()->count());
                return $c->sum(fn($r) => $r->recuperado) / $meses;
            };
            $basePrevia = $promedio($recs->filter(fn($r) => $r->fecha_pago->lt($inicioCamp)));
            // Ritmo de la campaña: recuperado desde el inicio entre los meses transcurridos (el mes en curso, prorrateado)
            $mesesCamp  = max(0.5, $inicioCamp->floatDiffInMonths($hoy->copy()->addDay()));
            $baseCamp   = $recs->filter(fn($r) => $r->fecha_pago->gte($inicioCamp))->sum(fn($r) => $r->recuperado) / $mesesCamp
                          ?: $basePrevia * 1.2;

            for ($m = Carbon::parse('2026-01-01'); $m->lte($finCamp); $m->addMonth()) {
                $u = Determinista::u("meta{$zonaId}{$m->format('Ym')}");
                $enCampana = $m->gte($inicioCamp);
                // Variación fija de la meta: entre 80 % y 120 % del ritmo de la zona
                $base = $enCampana ? $baseCamp : ($basePrevia ?: $baseCamp);
                MetaZona::create([
                    'zona_id' => $zonaId, 'campana_id' => $enCampana ? $campana?->id : null, 'mes' => $m->toDateString(),
                    'meta_recupero' => max(10000, round($base * (0.8 + 0.4 * $u), -3)),
                ]);
                $n++;
            }
        }
        return $n;
    }

    private function priorizar(Carbon $hoy): int
    {
        PriorizacionCartera::query()->delete();
        $cartera = ClienteCartera::disponibles()->get();
        $cumplidos = Recupero::pluck('cliente_cartera_id')->flip()->map(fn() => true)->all();

        $ctx = [
            'saldos'      => $cartera->pluck('saldo_capital')->map(fn($v) => (float) $v)->sort()->values()->all(),
            'provisiones' => $cartera->map(fn($c) => $this->priorizacion->provisionLiberable($c))->sort()->values()->all(),
            'recuperables'=> $cartera->map(fn($c) => $this->priorizacion->montoRecuperable($c) * $this->priorizacion->pago($c))->sort()->values()->all(),
            'cumplidos'   => $cumplidos,
            'hoy'         => $hoy,
        ];
        foreach ($cartera as $c) {
            PriorizacionCartera::create(['cliente_cartera_id' => $c->id, 'fecha_corte' => $hoy->toDateString()]
                + $this->priorizacion->evaluar($c, $ctx));
        }
        return $cartera->count();
    }

    private function seguimiento(Carbon $hoy): int
    {
        SeguimientoBeneficio::query()->delete();
        $n = 0;
        foreach (Recupero::with('propuesta.cliente', 'propuesta.detalleCuotas')->get() as $r) {
            if (!$this->recaida->aplica($r->propuesta)) continue;
            SeguimientoBeneficio::create([
                'propuesta_fud_id'   => $r->propuesta_fud_id,
                'cliente_cartera_id' => $r->cliente_cartera_id,
                'asesor_id'          => $r->asesor_id,
                'tipo_negociacion'   => $r->tipo_negociacion,
                'fecha_beneficio'    => $r->fecha_pago,
            ] + $this->recaida->evaluar($r->propuesta, $r, $hoy));
            $n++;
        }
        return $n;
    }

    /** Conserva el estado y comentario de las alertas ya gestionadas; retira las nuevas que dejaron de cumplirse. */
    private function alertas(Carbon $hoy): int
    {
        $detectadas = $this->anomalias->detectar($hoy);
        $vigentes = [];
        foreach ($detectadas as $a) {
            $clave = ['tipo' => $a['tipo'], 'entidad_tipo' => $a['entidad_tipo'], 'entidad_id' => $a['entidad_id']];
            $alerta = AlertaControl::firstOrNew($clave);
            if ($alerta->exists) unset($a['detectada_en']);
            $alerta->fill($a)->save();
            $vigentes[] = $alerta->id;
        }
        AlertaControl::whereNotIn('id', $vigentes)->where('estado', 'Nueva')->delete();
        return count($detectadas);
    }
}
