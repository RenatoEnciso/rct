<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Alerta de control (detección de anomalías) para Jefatura y Auditoría. */
class AlertaControl extends Model
{
    public const ESTADOS = ['Nueva', 'En revisión', 'Descartada', 'Confirmada'];
    public const ABIERTAS = ['Nueva', 'En revisión'];

    protected $table = 'alertas_control';
    protected $guarded = ['id'];
    protected $casts = [
        'serie' => 'array', 'detectada_en' => 'datetime',
        'valor_observado' => 'decimal:2', 'valor_referencia' => 'decimal:2',
    ];

    public function zona()      { return $this->belongsTo(Zona::class); }
    public function historial() { return $this->hasMany(AlertaHistorial::class, 'alerta_id')->latest('id'); }

    public function scopeAbiertas($q) { return $q->whereIn('estado', self::ABIERTAS); }

    /** Nombre e ícono de cada tipo de alerta. */
    public const TIPOS = [
        'descuento_tope'          => ['Descuento al tope', 'bi-percent'],
        'aprobacion_minutos'      => ['Aprobación en minutos', 'bi-stopwatch'],
        'excepciones_tienda'      => ['Excepciones concentradas', 'bi-shop'],
        'beneficios_consecutivos' => ['Beneficios consecutivos', 'bi-arrow-repeat'],
        'carga_fuera_horario'     => ['Carga fuera de horario', 'bi-moon-stars'],
    ];

    public function nombreTipo(): string { return self::TIPOS[$this->tipo][0] ?? ucfirst(str_replace('_', ' ', $this->tipo)); }
    public function iconoTipo(): string  { return self::TIPOS[$this->tipo][1] ?? 'bi-exclamation-diamond'; }

    public function formato(float $v): string
    {
        return match ($this->unidad) {
            '%'     => number_format($v, 1).'%',
            'min'   => $v >= 120 ? number_format($v / 60, 1).' horas' : round($v).' min',
            default => ($n = rtrim(rtrim(number_format($v, 1), '0'), '.')).' '.($n === '1' ? rtrim((string) $this->unidad, 's') : $this->unidad),
        };
    }

    /** Lectura de la comparación con la referencia (p. ej. "3.4 veces el promedio de la zona"). */
    public function comparacion(): ?string
    {
        $obs = (float) $this->valor_observado; $ref = (float) $this->valor_referencia;
        if ($obs <= 0 || $ref <= 0) return null;
        return $this->tipo === 'aprobacion_minutos'
            ? number_format($ref / $obs, 0, '.', ' ').' veces más rápido que con el resto de asesores'
            : number_format($obs / $ref, 1).' veces '.(['asesor' => 'el promedio de la zona', 'tienda' => 'el resto de tiendas'][$this->entidad_tipo] ?? 'lo habitual');
    }
}
