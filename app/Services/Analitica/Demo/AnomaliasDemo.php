<?php
namespace App\Services\Analitica\Demo;

use App\Contracts\Analitica\DetectorAnomalias;
use App\Models\{PropuestaFud, SustentoDocumento, Tienda, User, Zona};
use App\Services\Analitica\Determinista;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Reglas fijas de detección sobre los datos del sistema (ver docs/REGLAS_DEMO_ANALITICA.md).
 * Cada regla compara a la persona/tienda con el promedio de su zona.
 */
class AnomaliasDemo implements DetectorAnomalias
{
    private Carbon $hoy;
    private Collection $usuarios;
    private Collection $zonas;

    public function detectar(Carbon $hoy): array
    {
        $this->hoy = $hoy;
        $this->usuarios = User::with('tienda')->get()->keyBy('id');
        $this->zonas = Zona::pluck('nombre', 'id');
        $fuds = PropuestaFud::vigentes()->with('cliente', 'detalleCuotas', 'detalleCancelacion')->get();

        return array_merge(
            $this->descuentoAlTope($fuds),
            $this->aprobacionEnMinutos($fuds),
            $this->excepcionesPorTienda($fuds),
            $this->beneficiosConsecutivos($fuds),
            $this->cargaFueraDeHorario(),
        );
    }

    // ── Reglas ───────────────────────────────────────────────

    private function descuentoAlTope(Collection $fuds): array
    {
        $alTope = fn($f) => $f->tipo_negociacion === 'cuotas'
            ? ($f->detalleCuotas->desc_interes ?? 0) >= 70
            : ($f->detalleCancelacion->desc_capital ?? 0) >= 50;
        $neg = $fuds->whereIn('tipo_negociacion', ['cuotas', 'cancelacion'])->values();
        $share = fn(Collection $c) => $c->count() ? $c->filter($alTope)->count() / $c->count() : 0;

        $out = [];
        foreach ($neg->groupBy('enviado_por') as $uid => $propias) {
            $u = $this->usuarios[$uid] ?? null;
            if (!$u || $propias->count() < 5) continue;
            $zona = $u->tienda?->zona_id;
            $otros = $neg->filter(fn($f) => $f->enviado_por != $uid && $this->zonaDe($f->enviado_por) === $zona);
            if ($otros->count() < 5) $otros = $neg->where('enviado_por', '!=', $uid);
            $obs = $share($propias); $ref = $share($otros);
            if ($obs < 0.6 || $obs < 2 * $ref) continue;

            $k = $propias->filter($alTope)->count();
            $out[] = $this->alerta('descuento_tope', $obs >= 0.85 ? 'Alta' : 'Media', 'asesor', $u->id, $u->name, $zona,
                'Descuento al tope en casi todas sus propuestas',
                "{$u->name} propuso el descuento máximo del lineamiento en {$k} de sus {$propias->count()} propuestas de pago de cuotas y cancelación. "
                ."El promedio de los asesores de la zona {$this->zonas[$zona]} es ".$this->pct($ref).'. Revisar si la negociación justifica el beneficio máximo.',
                '% de propuestas con descuento máximo', $obs * 100, $ref * 100, '%',
                $this->serieMensual($propias, $otros, fn($c) => round($share($c) * 100, 1), 'fecha_envio', $u->name, 'Promedio de la zona'));
        }
        return $out;
    }

    private function aprobacionEnMinutos(Collection $fuds): array
    {
        $aprob = $fuds->where('estado_fud', 'Aprobado')->filter(fn($f) => $f->decidido_en && $f->aprobado_por)
            ->map(function ($f) { $f->minutos = max(1, $f->created_at->diffInMinutes($f->decidido_en)); return $f; });

        $out = [];
        foreach ($aprob->groupBy('aprobado_por') as $aid => $deAprobador) {
            $a = $this->usuarios[$aid] ?? null;
            if (!$a) continue;
            foreach ($deAprobador->groupBy('enviado_por') as $uid => $par) {
                $resto = $deAprobador->where('enviado_por', '!=', $uid);
                if ($par->count() < 4 || $resto->count() < 4) continue;
                $obs = $par->avg('minutos'); $ref = $resto->avg('minutos');
                if ($obs > 15 || $ref < 4 * $obs) continue;

                $asesor = $this->usuarios[$uid]->name ?? 'asesor';
                $porAsesor = $deAprobador->groupBy('enviado_por')->map(fn($c, $id) => [
                    'etiqueta' => $this->usuarios[$id]->name ?? '—', 'valor' => round($c->avg('minutos')), 'destacar' => $id == $uid,
                ])->sortBy('valor')->values()->all();

                $out[] = $this->alerta('aprobacion_minutos', 'Alta', 'aprobador', $a->id, $a->name, $a->tienda?->zona_id,
                    "Aprueba en minutos las propuestas de {$asesor}",
                    "{$a->name} aprobó las {$par->count()} propuestas de {$asesor} en un promedio de ".round($obs)." minutos desde su envío. "
                    .'Con el resto de asesores demora en promedio '.$this->duracion($ref).'. Verificar que se revisen los documentos antes de aprobar.',
                    'Minutos promedio hasta la aprobación', $obs, $ref, 'min',
                    ['tipo' => 'barras', 'titulo' => 'Tiempo promedio de aprobación por asesor (minutos)', 'items' => $porAsesor]);
            }
        }
        return $out;
    }

    private function excepcionesPorTienda(Collection $fuds): array
    {
        $porTienda = $fuds->groupBy(fn($f) => $f->cliente->tienda_id);
        $share = fn(Collection $c) => $c->count() ? $c->where('tipo_operacion', 'ESPECIAL')->count() / $c->count() : 0;
        $ref = $fuds->count() ? $fuds->where('tipo_operacion', 'ESPECIAL')->count() / $fuds->count() : 0;
        $tiendas = Tienda::all()->keyBy('id');

        $out = [];
        foreach ($porTienda as $tid => $c) {
            $k = $c->where('tipo_operacion', 'ESPECIAL')->count();
            $obs = $share($c);
            $refOtras = $share($fuds->filter(fn($f) => $f->cliente->tienda_id != $tid));
            if ($k < 4 || $obs < 3 * max($refOtras, 0.01)) continue;
            $t = $tiendas[$tid];
            $items = $porTienda->filter(fn($x, $id) => ($tiendas[$id]->zona_id ?? null) === $t->zona_id)
                ->map(fn($x, $id) => ['etiqueta' => $tiendas[$id]->nombre, 'valor' => round($share($x) * 100, 1), 'destacar' => $id == $tid])
                ->sortByDesc('valor')->values()->all();

            $out[] = $this->alerta('excepciones_tienda', $obs >= 0.4 ? 'Alta' : 'Media', 'tienda', $t->id, $t->nombre, $t->zona_id,
                "Excepciones concentradas en {$t->nombre}",
                "{$k} de las {$c->count()} propuestas de la tienda {$t->nombre} se tramitaron como excepción (operación especial), "
                .'frente a '.$this->pct($refOtras).' en el resto de tiendas. Revisar el sustento de las excepciones.',
                '% de propuestas tramitadas como excepción', $obs * 100, $refOtras * 100, '%',
                ['tipo' => 'barras', 'titulo' => '% de excepciones por tienda de la zona', 'items' => $items]);
        }
        return $out;
    }

    private function beneficiosConsecutivos(Collection $fuds): array
    {
        // Dos o más beneficios aprobados con al menos 60 días de diferencia (campañas o meses distintos)
        $candidatos = $fuds->where('estado_fud', 'Aprobado')->groupBy('cliente_cartera_id')
            ->filter(fn($c) => $c->count() >= 2
                && Carbon::parse($c->min('fecha_envio'))->diffInDays(Carbon::parse($c->max('fecha_envio'))) >= 60)
            ->sortByDesc(fn($c) => $c->first()->cliente->saldo_capital)->take(2);

        $out = [];
        foreach ($candidatos as $cid => $c) {
            $cli = $c->first()->cliente;
            $n = $c->count();
            $lista = $c->sortBy('fecha_envio')->map(fn($f) => Carbon::parse($f->fecha_envio)->format('d/m/Y').' ('.$f->tipo_negociacion_desc.')')->implode(', ');
            // Dos beneficios siguen dentro del límite de 4 en 12 meses: severidad baja; tres o más, media
            $out[] = $this->alerta('beneficios_consecutivos', $n >= 3 ? 'Media' : 'Baja', 'cliente', $cli->id, $cli->nombre_cliente, $cli->zona_id,
                'Cliente con beneficios en campañas consecutivas',
                "El crédito {$cli->credito_sicmact} recibió {$n} beneficios aprobados: {$lista}. "
                .'Evaluar si el beneficio está logrando normalizar al cliente antes de otorgar uno nuevo.',
                'Beneficios aprobados en 12 meses', $n, 1, 'beneficios',
                ['tipo' => 'linea_tiempo', 'items' => $c->sortBy('fecha_envio')->map(fn($f) => [
                    'fecha' => Carbon::parse($f->fecha_envio)->format('d/m/Y'), 'detalle' => $f->tipo_negociacion_desc, 'fud' => $f->id,
                ])->values()->all()]);
        }
        return $out;
    }

    private function cargaFueraDeHorario(): array
    {
        $cargas = SustentoDocumento::with('sustento')->get()->filter(fn($d) => $d->cargado_en && $d->sustento)
            ->groupBy('sustento_id')->map(fn($docs) => (object) [
                'usuario' => $docs->first()->sustento->cargado_por,
                'fecha'   => $docs->min('cargado_en'),
            ])->values();
        $fuera = fn($x) => $x->fecha->hour < 7 || $x->fecha->hour >= 21 || $x->fecha->isSunday();
        $share = fn(Collection $c) => $c->count() ? $c->filter($fuera)->count() / $c->count() : 0;

        $out = [];
        foreach ($cargas->groupBy('usuario') as $uid => $propias) {
            $u = $this->usuarios[$uid] ?? null;
            $k = $propias->filter($fuera)->count();
            if (!$u || $k < 3 || $share($propias) < 0.3) continue;
            $zona = $u->tienda?->zona_id;
            $otros = $cargas->filter(fn($x) => $x->usuario != $uid && $this->zonaDe($x->usuario) === $zona);
            $ejemplo = $propias->filter($fuera)->sortByDesc('fecha')->first()->fecha;
            $out[] = $this->alerta('carga_fuera_horario', $share($propias) >= 0.4 ? 'Media' : 'Baja', 'asesor', $u->id, $u->name, $zona,
                'Carga de sustentos fuera del horario de atención',
                "{$u->name} cargó {$k} de sus {$propias->count()} sustentos fuera del horario de atención (la más reciente el "
                .$ejemplo->format('d/m/Y').' a las '.$ejemplo->format('H:i').'). En su zona el promedio es '.$this->pct($share($otros)).'.',
                '% de cargas fuera de horario', $share($propias) * 100, $share($otros) * 100, '%',
                $this->serieMensual($propias, $otros, fn($c) => round($share($c) * 100, 1), 'fecha', $u->name, 'Promedio de la zona'));
        }
        return $out;
    }

    private function alerta(string $tipo, string $sev, string $entTipo, int $entId, string $entNombre, ?int $zona, string $titulo,
                            string $desc, string $metrica, float $obs, float $ref, string $unidad, array $serie): array
    {
        return [
            'tipo' => $tipo, 'severidad' => $sev, 'entidad_tipo' => $entTipo, 'entidad_id' => $entId, 'entidad_nombre' => $entNombre,
            'zona_id' => $zona, 'titulo' => $titulo, 'descripcion' => $desc, 'metrica' => $metrica,
            'valor_observado' => round($obs, 2), 'valor_referencia' => round($ref, 2), 'unidad' => $unidad, 'serie' => $serie,
            'detectada_en' => $this->hoy->copy()->subDays(Determinista::entre("det{$tipo}{$entId}", 0, 18))->setTime(7, 15),
        ];
    }

    /** Valor mensual (últimos 6 meses) de la entidad frente a su referencia. */
    private function serieMensual(Collection $propias, Collection $ref, callable $valor, string $campo, string $nombre, string $nombreRef): array
    {
        $meses = collect(range(5, 0))->map(fn($i) => $this->hoy->copy()->startOfMonth()->subMonths($i));
        $delMes = fn(Collection $c, Carbon $m) => $c->filter(fn($x) => Carbon::parse($x->{$campo})->isSameMonth($m));
        return [
            'tipo'       => 'mensual',
            'etiquetas'  => $meses->map(fn($m) => ucfirst($m->translatedFormat('M Y')))->all(),
            'series'     => [
                ['nombre' => $nombre,    'valores' => $meses->map(fn($m) => ($x = $delMes($propias, $m))->count() ? $valor($x) : null)->all()],
                ['nombre' => $nombreRef, 'valores' => $meses->map(fn($m) => ($x = $delMes($ref, $m))->count() ? $valor($x) : null)->all()],
            ],
        ];
    }

    private function zonaDe($userId): ?int
    {
        return $this->usuarios->get($userId)?->tienda?->zona_id;
    }

    private function pct(float $v): string
    {
        return number_format($v * 100, 1).'%';
    }

    private function duracion(float $minutos): string
    {
        return $minutos >= 120 ? round($minutos / 60, 1).' horas' : round($minutos).' minutos';
    }
}
