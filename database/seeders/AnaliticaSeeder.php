<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use App\Models\{AlertaControl, AlertaHistorial, FudDetalleCancelacion, FudDetalleCuotas, PropuestaFud, Seguimiento, SustentoDocumento, Tienda, User};
use App\Services\Analitica\{Determinista, GeneradorAnalitica};

/**
 * Analítica de la demo (ver docs/REGLAS_DEMO_ANALITICA.md):
 * 1. Siembra en los datos los patrones que deben detectar las alertas de control.
 * 2. Precalcula recuperos, metas, priorización, recaída y alertas.
 * 3. Deja algunas alertas ya gestionadas por la Jefatura.
 * Determinista: no usa números aleatorios.
 */
class AnaliticaSeeder extends Seeder
{
    public function run(): void
    {
        if (!User::where('email', 'ejecutivo3@cajatrujillo.pe')->exists()) {
            $this->command?->warn('Sin datos de demo: se omite la analítica.');
            return;
        }

        $this->descuentoAlTope(['ejecutivo3@cajatrujillo.pe', 'senior2@cajatrujillo.pe']);
        $this->aprobacionEnMinutos(['ejecutivo4@cajatrujillo.pe', 'senior3@cajatrujillo.pe']);
        $this->excepciones(['Pucallpa' => 0.55, 'Bagua Chica' => 0.4]);
        $this->fueraDeHorario(['ejecutivo7@cajatrujillo.pe' => 0.6, 'ejecutivo10@cajatrujillo.pe' => 0.45]);

        $r = app(GeneradorAnalitica::class)->generar();
        $this->alertasGestionadas();

        $this->command?->info("Analítica: {$r['recuperos']} recuperos, {$r['priorizacion']} créditos priorizados, "
            ."{$r['seguimiento']} beneficios en seguimiento, {$r['alertas']} alertas de control.");
    }

    // ── Patrones de control ──────────────────────────────────

    /** El asesor propone siempre el descuento máximo del lineamiento. */
    private function descuentoAlTope(array $emails): void
    {
        foreach (User::whereIn('email', $emails)->pluck('id') as $uid) {
            $ids = PropuestaFud::where('enviado_por', $uid)->pluck('id');
            FudDetalleCuotas::whereIn('propuesta_fud_id', $ids)->update(['desc_interes' => 70, 'desc_moratorio' => 100]);
            foreach (FudDetalleCancelacion::whereIn('propuesta_fud_id', $ids)->with('propuesta.cliente')->get() as $d) {
                $d->update(['desc_capital' => 50, 'desc_interes' => 100, 'monto_a_pagar' => round($d->propuesta->cliente->saldo_capital * 0.5, 2)]);
            }
        }
    }

    /** El coordinador aprueba en pocos minutos todo lo de un mismo asesor. */
    private function aprobacionEnMinutos(array $emails): void
    {
        foreach (User::whereIn('email', $emails)->pluck('id') as $uid) {
            $fuds = PropuestaFud::where('enviado_por', $uid)->where('estado_fud', 'Aprobado')->whereNotNull('decidido_en')
                ->whereHas('aprobadoPor.rol', fn($q) => $q->where('nivel', 1))->get();
            foreach ($fuds as $f) {
                $dec = $f->created_at->copy()->addMinutes(Determinista::entre('min'.$f->id, 3, 9));
                $f->timestamps = false;
                $f->update(['decidido_en' => $dec, 'fecha_decision' => $dec->toDateString()]);
                Seguimiento::where('propuesta_fud_id', $f->id)->where('tipo_operacion', 'Decisión FUD')
                    ->update(['created_at' => $dec, 'fecha_accion' => $dec->toDateString()]);
            }
        }
    }

    /** Propuestas tramitadas como excepción: base baja en todas las tiendas y concentración en algunas. */
    private function excepciones(array $concentradas): void
    {
        $tiendas = Tienda::whereIn('nombre', array_keys($concentradas))->pluck('nombre', 'id');
        foreach (PropuestaFud::with('cliente')->where('fecha_envio', '>=', '2026-07-01')->get() as $f) {
            $nombre = $tiendas[$f->cliente->tienda_id] ?? null;
            $p = $nombre ? $concentradas[$nombre] : 0.03;
            if (Determinista::u('exc'.$f->id) < $p) {
                $f->timestamps = false;
                $f->update(['tipo_operacion' => 'ESPECIAL']);
            }
        }
    }

    /** Sustentos cargados de noche. */
    private function fueraDeHorario(array $porUsuario): void
    {
        foreach ($porUsuario as $email => $p) {
            $uid = User::where('email', $email)->value('id');
            foreach (SustentoDocumento::whereHas('sustento', fn($q) => $q->where('cargado_por', $uid))->get()->groupBy('sustento_id') as $sid => $docs) {
                if (Determinista::u('hor'.$sid) >= $p) continue;
                $base = $docs->min('cargado_en');
                if (!$base) continue;
                $noche = $base->copy()->setTime(Determinista::entre('h'.$sid, 21, 23), Determinista::entre('m'.$sid, 5, 50));
                if ($noche->gt(now())) $noche->subDay();
                foreach ($docs->values() as $i => $d) $d->update(['cargado_en' => $noche->copy()->addMinutes($i * 2)]);
            }
        }
    }

    // ── Alertas ya gestionadas ───────────────────────────────

    private function alertasGestionadas(): void
    {
        $jefe = User::where('email', 'jefe@cajatrujillo.pe')->first();
        $acciones = [
            'En revisión' => 'Se solicitó al coordinador zonal el sustento de las operaciones observadas.',
            'Descartada'  => 'Se verificó el sustento con la tienda: las operaciones son conformes.',
            'Confirmada'  => 'Se confirmó la observación. Se informó a Gerencia y se reforzará la supervisión.',
        ];
        foreach (AlertaControl::orderBy('id')->get() as $a) {
            $u = Determinista::u('estado'.$a->id);
            $estado = $u < 0.22 ? 'En revisión' : ($u < 0.30 ? 'Descartada' : ($u < 0.36 ? 'Confirmada' : null));
            if (!$estado) continue;
            $fecha = $a->detectada_en->copy()->addDay()->setTime(10, 20);
            if ($fecha->gt(now())) $fecha = now()->subHour();
            $a->update(['estado' => $estado, 'comentario' => $acciones[$estado], 'actualizado_por' => $jefe->id]);
            AlertaHistorial::create(['alerta_id' => $a->id, 'user_id' => $jefe->id, 'estado_anterior' => 'Nueva',
                'estado_nuevo' => $estado, 'comentario' => $acciones[$estado], 'created_at' => $fecha]);
        }
    }
}
