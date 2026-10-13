<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Resultado del modelo de priorización por crédito (precalculado). */
class PriorizacionCartera extends Model
{
    protected $table = 'priorizacion_cartera';
    protected $guarded = ['id'];
    protected $casts = [
        'fecha_corte' => 'date', 'probabilidad_pago' => 'decimal:2',
        'monto_recuperable' => 'decimal:2', 'provision_liberable' => 'decimal:2',
        'motivos' => 'array', 'componentes' => 'array',
    ];

    public function cliente() { return $this->belongsTo(ClienteCartera::class, 'cliente_cartera_id'); }

    /** Créditos sin propuesta desde el inicio de la campaña. */
    public function scopePendientes($q)
    {
        return $q->whereNotIn('cliente_cartera_id', self::gestionados());
    }

    public function scopeGestionadas($q)
    {
        return $q->whereIn('cliente_cartera_id', self::gestionados());
    }

    public static function gestionados()
    {
        return PropuestaFud::where('fecha_envio', '>=', Campana::inicioActual())->select('cliente_cartera_id');
    }

    public function scopeDeZona($q, ?int $zonaId)
    {
        return $q->when($zonaId, fn($q) => $q->whereHas('cliente', fn($c) => $c->where('zona_id', $zonaId)));
    }

    public static function iconoAccion(string $accion): string
    {
        return ['Llamar' => 'bi-telephone', 'Visitar' => 'bi-geo-alt', 'Ofrecer cancelación' => 'bi-cash-coin',
                'Ofrecer pago de cuotas' => 'bi-calendar-check'][$accion] ?? 'bi-arrow-right-circle';
    }

    /** Recupero esperado = monto recuperable × probabilidad de pago. */
    public function getRecuperoEsperadoAttribute(): float
    {
        return round((float) $this->monto_recuperable * (float) $this->probabilidad_pago / 100, 2);
    }
}
