<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use App\Models\{CandidatoHonramiento, CoreCredito, CorePago, ExpedienteGobierno, Lote, PagoPrograma, Seguimiento, SincronizacionCore, Tienda, User};
use App\Services\Impulso\{Avisos, Calculos, Devoluciones, DocumentosLote, Lotes, SincronizadorCore};
use Illuminate\Support\Facades\DB;

/**
 * Impulso MyPerú — tres meses de operación coherente (ver docs/REGLAS_DEMO_IMPULSO.md):
 * core espejo, lotes en distintos estados, candidatos, pagos antes y después del honramiento,
 * devoluciones (un mes cuadrado en S/ 0,00 y otro con 2 diferencias) y TXT de COFIDE de ejemplo.
 * Determinista: misma semilla → mismos datos.
 */
class ImpulsoSeeder extends Seeder
{
    private Carbon $hoy;
    private \Faker\Generator $faker;
    private User $jefe;

    public function run(): void
    {
        if (!ExpedienteGobierno::exists()) {
            $this->command?->warn('Sin expedientes de honramiento: se omite Impulso MyPerú.');
            return;
        }
        mt_srand(4040);
        $this->faker = \Faker\Factory::create('es_PE');
        $this->faker->seed(4040);
        $this->hoy = now()->startOfDay();
        $this->jefe = User::where('email', 'jefe@cajatrujillo.pe')->first();
        $programa = config('rct.impulso.programa');

        Lote::query()->update(['programa' => $programa]);
        ExpedienteGobierno::query()->update(['programa' => $programa]);

        $this->coreDeExpedientes();
        $this->historialDeLotes();
        $this->creditosNuevos();
        $this->loteEnPreparacion();
        $this->pagosCore();
        $this->devoluciones();
        $this->sincronizaciones();
        $this->archivosDeEjemplo();

        $this->command?->info('Impulso MyPerú: '.CoreCredito::count().' créditos en el core, '.Lote::count().' lotes, '
            .count(app(Lotes::class)->candidatos()).' candidatos.');
    }

    /** Factor de cobertura según el monto desembolsado (tramos supuestos, config rct.impulso.tramos_cobertura). */
    private function factor(float $desembolso): string
    {
        foreach (config('rct.impulso.tramos_cobertura') as [$hasta, $factor]) {
            if ($hasta === null || $desembolso <= $hasta) return number_format($factor, 4, '.', '');
        }
        return '0.8000';
    }

    /** Un crédito espejo en el core por cada expediente; saldo e interés coherentes con el monto honrado. */
    private function coreDeExpedientes(): void
    {
        foreach (ExpedienteGobierno::with('lote')->orderBy('id')->get() as $e) {
            $honrado = (float) ($e->monto_honrado ?: mt_rand(8000, 90000));
            $desembolso = round($honrado * mt_rand(125, 175) / 100, 2);
            $factor = $this->factor($desembolso);
            $base = $honrado / (float) $factor;                         // saldo + interés
            $interes = round($base * mt_rand(20, 55) / 1000, 2);
            $saldo = round($base - $interes, 2);
            $corte = $e->lote?->fecha_conformidad?->copy()->subDays(3) ?? $this->hoy->copy()->subDays(60);

            CoreCredito::create([
                'credito_sicmact' => $e->credito_sicmact, 'cuenta' => $e->cuenta_bt, 'operacion' => $e->operacion,
                'tipo_documento' => $e->tipo_persona === 'juridica' ? 'RUC' : 'DNI', 'nro_documento' => $e->dni,
                'tipo_persona' => $e->tipo_persona ?? 'natural', 'cliente' => $e->cliente, 'zona_id' => $e->zona_id, 'tienda_id' => $e->tienda_id,
                'programa' => config('rct.impulso.programa'), 'producto' => $e->tipo_persona === 'juridica' ? 'CAPITAL DE TRABAJO PYME' : 'CAPITAL DE TRABAJO MYPE',
                'fecha_desembolso' => $corte->copy()->subMonths(mt_rand(10, 20)), 'monto_desembolso' => $desembolso,
                'saldo_capital' => $saldo, 'interes_devengado' => $interes,
                'dias_atraso' => 90 + (int) $corte->diffInDays($this->hoy) + mt_rand(0, 4), 'factor_cobertura' => $factor,
                'estado' => 'VENCIDO', 'actualizado_en' => now()->subMinutes(12),
            ]);
            $e->update([
                'saldo_insoluto' => $saldo, 'interes_devengado' => $interes, 'factor_cobertura' => $factor,
                'monto_honrado' => Calculos::montoHonrar($saldo, $interes, $factor),
            ]);
        }
    }

    /**
     * Lotes históricos del área (importados del Excel): corte 3 días antes de la conformidad.
     * Honrados a los 30 días los más antiguos y a los 20 el reciente (parcial); el último sigue cargado en COFIDE.
     */
    private function historialDeLotes(): void
    {
        $lotes = Lote::whereNotNull('fecha_conformidad')->orderBy('fecha_conformidad')->get();
        $docs = app(DocumentosLote::class);
        foreach ($lotes as $i => $l) {
            $conf = $l->fecha_conformidad->copy();
            $corte = $conf->copy()->subDays(3);
            $ultimo = $i === $lotes->count() - 1;
            $honr = $ultimo ? null : $conf->copy()->addDays($i === $lotes->count() - 2 ? 20 : 30);
            $exp = $l->expedientes()->orderBy('id')->get();

            $firmas = collect(config('rct.impulso.representantes'))->map(fn($r, $k) => $r + [
                'autorizado_en' => $corte->copy()->addDay()->setTime(10 + $k, 15)->format('Y-m-d H:i'), 'registrado_por' => $this->jefe->name,
            ])->all();
            $l->update([
                'fecha_corte' => $corte, 'fecha_solicitud' => $corte, 'fecha_carga' => $conf, 'creado_por' => $this->jefe->id,
                'documentos_generados_en' => $corte->copy()->setTime(16, 40), 'firmas' => $firmas,
                'registros_cofide' => $exp->count(), 'constancia_cofide' => 'CF-'.$conf->format('ymd').'-'.str_pad((string) ($i + 31), 4, '0', STR_PAD_LEFT),
                'confirmado_en' => $conf->copy()->setTime(11, 20),
            ]);
            app(Lotes::class)->recalcularTotales($l);
            $l->refresh()->update(['saldo_cofide' => $l->saldo_rct]);

            // El lote reciente se honra parcialmente: COFIDE observa 2 créditos
            $parcial = !$ultimo && $i === $lotes->count() - 2;
            foreach ($exp as $k => $e) {
                $noHonrado = $parcial && in_array($k, [3, 11], true);
                $e->update($honr && !$noHonrado
                    ? ['estado_honramiento' => 'Honrado', 'fecha_honramiento' => $honr]
                    : ['estado_honramiento' => $honr ? 'No honrado' : 'Solicitado', 'fecha_honramiento' => null]);
            }
            $l->update([
                'estado' => $honr ? ($parcial ? 'Honrado parcial' : 'Honrado') : 'Cargado en COFIDE',
                'fecha_honramiento' => $honr, 'reporte_importado_en' => $honr?->copy()->setTime(9, 30),
                'reporte_archivo' => $honr ? 'Reporte_Honramiento_COFIDE_'.preg_replace('/\D/', '', $l->codigo).'.xlsx' : null,
                'aviso_agencias_en' => $honr?->copy()->setTime(9, 35),
            ]);
            $docs->word($l);
            $docs->excel($l);
        }
    }

    /** Créditos del programa aún no solicitados: candidatos (≥ 90 días), próximos (75–89) y al día. */
    private function creditosNuevos(): void
    {
        $tiendas = Tienda::whereIn('zona_id', ExpedienteGobierno::distinct()->pluck('zona_id'))->get()->values();
        $base = 7_200_000;
        $perfiles = array_merge(array_fill(0, 22, 'candidato'), array_fill(0, 9, 'proximo'), array_fill(0, 24, 'vigente'));
        foreach ($perfiles as $i => $perfil) {
            $juridica = $i % 7 === 3;
            $tienda = $tiendas[$i % $tiendas->count()];
            $desembolso = round(mt_rand(1200, 18000) * 10 * ($juridica ? 2.5 : 1), 2);
            $saldo = round($desembolso * mt_rand(55, 85) / 100, 2);
            $dias = match ($perfil) { 'candidato' => mt_rand(90, 118), 'proximo' => mt_rand(76, 89), default => mt_rand(0, 45) };
            $sic = (string) ($base + $i * 37);
            CoreCredito::create([
                'credito_sicmact' => '6122'.$sic.'1', 'cuenta' => (string) mt_rand(400000, 799999), 'operacion' => (string) mt_rand(200000, 299999),
                'tipo_documento' => $juridica ? 'RUC' : 'DNI', 'nro_documento' => $juridica ? '20'.mt_rand(100000000, 609999999) : (string) mt_rand(10000000, 79999999),
                'tipo_persona' => $juridica ? 'juridica' : 'natural',
                'cliente' => $juridica ? mb_strtoupper($this->faker->company()).' S.A.C.' : mb_strtoupper($this->faker->lastName().' '.$this->faker->lastName().' '.$this->faker->firstName()),
                'zona_id' => $tienda->zona_id, 'tienda_id' => $tienda->id, 'programa' => config('rct.impulso.programa'),
                'producto' => $juridica ? 'CAPITAL DE TRABAJO PYME' : 'CAPITAL DE TRABAJO MYPE',
                'fecha_desembolso' => $this->hoy->copy()->subMonths(mt_rand(8, 18)), 'monto_desembolso' => $desembolso,
                'saldo_capital' => $saldo, 'interes_devengado' => $dias >= 30 ? round($saldo * mt_rand(20, 45) / 1000, 2) : 0,
                'dias_atraso' => $dias, 'factor_cobertura' => $this->factor($desembolso),
                'estado' => $dias > 0 ? 'VENCIDO' : 'VIGENTE', 'actualizado_en' => now()->subMinutes(12),
            ]);
            if ($perfil === 'candidato') {
                CandidatoHonramiento::create(['credito_sicmact' => '6122'.$sic.'1', 'dias_atraso' => 90,
                    'detectado_en' => $this->hoy->copy()->subDays($dias - 90)->setTime(8, 30), 'notificado_en' => $this->hoy->copy()->subDays($dias - 90)->setTime(8, 31)]);
            }
        }
    }

    /** Lote del mes en curso: armado hace 2 días con 8 candidatos, documentos generados y una firma pendiente. */
    private function loteEnPreparacion(): void
    {
        $servicio = app(Lotes::class);
        $creditos = $servicio->candidatos()->sortBy('credito_sicmact')->take(8)->pluck('credito_sicmact')->all();
        $lote = $servicio->armar($creditos, $this->jefe, $this->hoy->copy()->subDays(2));
        $servicio->marcarDocumentos($lote);
        $lote->update(['documentos_generados_en' => $this->hoy->copy()->subDays(2)->setTime(17, 5)]);
        $rep = config('rct.impulso.representantes')[0];
        $lote->update(['firmas' => [$rep + ['autorizado_en' => $this->hoy->copy()->subDay()->setTime(9, 40)->format('Y-m-d H:i'), 'registrado_por' => $this->jefe->name]]]);
        app(DocumentosLote::class)->word($lote);
        app(DocumentosLote::class)->excel($lote);
    }

    /**
     * Pagos en el core: después del honramiento (reversión agregada) y antes del honramiento o en créditos
     * aún en solicitud (sin reversión). Más pagos hacia fin de mes. Se procesan con la sincronización real.
     */
    private function pagosCore(): void
    {
        $canales = [[34, 'Ventanilla'], [22, 'Agente Caja Trujillo'], [18, 'App móvil'], [14, 'Banca por internet'], [12, 'Débito automático']];
        $ahora = now()->subMinutes(40);
        $seq = 0;
        $crear = function (ExpedienteGobierno $e, Carbon $fecha) use (&$seq, $canales, $ahora) {
            if ($fecha->gt($ahora)) return;
            $capital = round(min((float) $e->saldo_insoluto * 0.18, mt_rand(300, 6200) + mt_rand(0, 99) / 100), 2);
            $interes = round($capital * mt_rand(4, 14) / 100, 2);
            $mora = mt_rand(1, 100) <= 45 ? round($capital * mt_rand(1, 30) / 1000, 2) : 0;
            $r = mt_rand(1, 100);
            foreach ($canales as [$peso, $canal]) { if (($r -= $peso) <= 0) break; }
            CorePago::create([
                'id_core' => 'CORE-'.$fecha->format('ymd').'-'.str_pad((string) ++$seq, 5, '0', STR_PAD_LEFT),
                'credito_sicmact' => $e->credito_sicmact, 'fecha_pago' => $fecha,
                'monto_total' => round($capital + $interes + $mora, 2), 'capital' => $capital, 'interes' => $interes, 'mora' => $mora,
                'tienda_id' => $e->tienda_id, 'canal' => $canal, 'registrado_en' => $fecha->copy()->addMinutes(mt_rand(2, 25)),
            ]);
        };
        // Día del mes con más pagos hacia el cierre
        $dia = fn(Carbon $mes) => mt_rand(1, 100) <= 55 ? mt_rand(24, $mes->daysInMonth) : mt_rand(1, $mes->daysInMonth);

        foreach (ExpedienteGobierno::with('lote')->orderBy('id')->get() as $i => $e) {
            $u = mt_rand(1, 100);
            if ($e->estado_honramiento === 'Honrado') {
                // 45 % paga después del honramiento: 1 a 3 pagos en meses distintos
                if ($u <= 45) {
                    $mes = $e->fecha_honramiento->copy()->startOfMonth();
                    for ($k = 0, $n = mt_rand(1, 3); $k < $n; $k++) {
                        $f = $mes->copy()->addMonths($k)->day($dia($mes->copy()->addMonths($k)))->setTime(mt_rand(8, 19), mt_rand(0, 59));
                        if ($f->lt($e->fecha_honramiento)) $f = $e->fecha_honramiento->copy()->addDays(mt_rand(1, 6))->setTime(mt_rand(9, 18), mt_rand(0, 59));
                        $crear($e, $f);
                    }
                }
                // 10 % pagó entre la solicitud y el honramiento: sin reversión
                if ($u > 90) $crear($e, $e->fecha_honramiento->copy()->subDays(mt_rand(3, 15))->setTime(mt_rand(9, 18), mt_rand(0, 59)));
            } elseif ($u <= 25) {
                // En solicitud o no honrados: pagos sin reversión
                $crear($e, ($e->lote?->fecha_corte ?? $this->hoy)->copy()->addDays(mt_rand(1, 8))->setTime(mt_rand(9, 18), mt_rand(0, 59)));
            }
        }

        $sinc = app(SincronizadorCore::class);
        $sinc->registrarBitacora = false;
        $reg = $sinc->ejecutar('programado', null, false);

        // Cada pago se sincronizó en la ronda de 30 minutos siguiente a su registro en el core
        foreach (PagoPrograma::with('expediente')->get() as $p) {
            $core = CorePago::where('id_core', $p->id_core)->first();
            $sinc = $core->registrado_en->copy()->addMinutes(30 - $core->registrado_en->minute % 30)->second(mt_rand(3, 9));
            $p->update(['sincronizado_en' => $sinc, 'agregado_en' => $p->estado === 'Agregado' ? $sinc : null, 'created_at' => $sinc, 'updated_at' => $sinc]);
        }
        foreach (PagoPrograma::with('expediente')->orderByDesc('fecha_pago')->take(25)->get() as $p) {
            Seguimiento::create([
                'user_id' => $this->jefe->id, 'tipo_operacion' => 'Pago sincronizado', 'estado' => $p->estado,
                'cliente' => $p->expediente?->cliente, 'operacion' => $p->credito_sicmact,
                'descripcion' => 'Pago sincronizado desde core el '.$p->sincronizado_en->format('d/m/Y H:i').': capital '.\App\Support\Estilo::solesPe($p->capital)
                    .($p->estado === 'Agregado' ? ' → reversión COFIDE '.\App\Support\Estilo::solesPe($p->reversion).' (agregado).' : ' → sin reversión.'),
                'fecha_accion' => $p->sincronizado_en->toDateString(), 'created_at' => $p->sincronizado_en,
            ]);
        }
        $reg->delete();
    }

    /**
     * Devoluciones: el mes de hace dos meses cuadró en S/ 0,00 y ya se devolvió; el mes anterior se cruzó
     * con 2 diferencias (un pago de fin de mes que COFIDE no consideró y un crédito que no figura en el TXT).
     */
    private function devoluciones(): void
    {
        $svc = app(Devoluciones::class);
        $cuadrada = $this->hoy->copy()->startOfMonth()->subMonthsNoOverflow(2);
        $conDif = $this->hoy->copy()->startOfMonth()->subMonthNoOverflow();

        $d1 = $svc->generar($cuadrada, $this->jefe);
        $svc->cruzar($d1, $svc->txtEjemplo($d1), 'NOTIFICACION_COFIDE_'.$cuadrada->format('Ym').'.txt', $this->jefe, false);
        $svc->cerrar($d1->fresh(), $this->jefe);
        $calc1 = $cuadrada->copy()->addMonth()->day(3)->setTime(9, 15);
        $d1->update(['created_at' => $calc1, 'txt_importado_en' => $cuadrada->copy()->addMonth()->day(8)->setTime(10, 20),
            'cerrado_en' => $cuadrada->copy()->addMonth()->day(9)->setTime(16, 5)]);

        $d2 = $svc->generar($conDif, $this->jefe);
        $det = $d2->detalles()->get();
        // Diferencia 1: COFIDE no consideró el último pago del mes de un crédito con varios pagos
        $ultimoPago = \App\Models\PagoPrograma::where('devolucion_id', $d2->id)->orderByDesc('fecha_pago')->get()
            ->first(fn($p) => $det->firstWhere('credito_sicmact', $p->credito_sicmact)?->pagos >= 1);
        $ajustes = [];
        if ($ultimoPago) {
            $fila = $det->firstWhere('credito_sicmact', $ultimoPago->credito_sicmact);
            $ajustes[$fila->credito_sicmact] = bcsub((string) $fila->monto_rct, (string) $ultimoPago->reversion, 2);
        }
        // Diferencia 2: un crédito no figura en el TXT
        $omitido = $det->sortBy('credito_sicmact')->first(fn($d) => !isset($ajustes[$d->credito_sicmact]));
        if ($omitido) $ajustes[$omitido->credito_sicmact] = null;
        $svc->cruzar($d2, $svc->txtEjemplo($d2, $ajustes), 'NOTIFICACION_COFIDE_'.$conDif->format('Ym').'.txt', $this->jefe, false);
        $d2->update(['created_at' => $conDif->copy()->addMonth()->day(2)->setTime(9, 5),
            'txt_importado_en' => $conDif->copy()->addMonth()->day(7)->setTime(9, 40)]);

        // Avisos que ya recibió el Jefe (campana): cruce con diferencias y devolución por vencer
        $avisos = app(Avisos::class);
        $avisos->cruceConDiferencias($d2->fresh());
        DB::table('notifications')->where('data', 'like', '%cruce_diferencias%')->update(['created_at' => $d2->fresh()->txt_importado_en->copy()->addMinute()]);
        $svc->avisarPorVencer();
        DB::table('notifications')->where('data', 'like', '%devolucion_por_vencer%')->update(['created_at' => $this->hoy->copy()->setTime(7, 0)]);
    }

    /**
     * Archivos de ejemplo para la demo (storage/app/private/impulso/ejemplos):
     * TXT de COFIDE de cada mes (el cuadrado, el que tiene diferencias y su versión corregida)
     * y el reporte de honramiento del lote que sigue cargado en COFIDE.
     */
    private function archivosDeEjemplo(): void
    {
        $disco = \Illuminate\Support\Facades\Storage::disk('private');
        $svc = app(Devoluciones::class);
        foreach (\App\Models\DevolucionCofide::orderBy('periodo')->get() as $d) {
            $mes = $d->periodo->format('Ym');
            if ($d->txt_ruta && $disco->exists($d->txt_ruta)) $disco->put("impulso/ejemplos/NOTIFICACION_COFIDE_{$mes}.txt", $disco->get($d->txt_ruta));
            if (!$d->cerrada()) $disco->put("impulso/ejemplos/NOTIFICACION_COFIDE_{$mes}_corregida.txt", $svc->txtEjemplo($d));
        }
        $cargado = Lote::where('estado', 'Cargado en COFIDE')->first();
        if ($cargado) {
            $ruta = app(\App\Services\Impulso\ReporteHonramiento::class)->ejemplo($cargado, $this->hoy->copy()->setTime(9, 0));
            $disco->put('impulso/ejemplos/'.basename($ruta), file_get_contents($ruta));
        }
    }

    /** Historial de sincronizaciones cada 30 minutos (la última hace 12 minutos). */
    private function sincronizaciones(): void
    {
        $ultima = now()->subMinutes(12)->second(0);
        $ultima->minute($ultima->minute - $ultima->minute % 30);
        for ($i = 15; $i >= 0; $i--) {
            $ini = $ultima->copy()->subMinutes(30 * $i);
            $fin = $ini->copy()->addSeconds(3 + $i % 4);
            $pagos = PagoPrograma::whereBetween('sincronizado_en', [$ini->copy()->subMinutes(30), $fin])->get();
            SincronizacionCore::create([
                'origen' => 'programado', 'iniciado_en' => $ini, 'finalizado_en' => $fin,
                'creditos_leidos' => CoreCredito::count(), 'pagos_leidos' => $pagos->count(), 'pagos_nuevos' => $pagos->count(),
                'pagos_programa' => $pagos->count(), 'agregados' => $pagos->where('estado', 'Agregado')->count(),
                'sin_reversion' => $pagos->where('estado', 'Sin reversión')->count(), 'estado' => 'ok',
            ]);
        }
    }
}
