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

    /** Recupero esperado = monto recuperable × probabilidad de pago. */
    public function getRecuperoEsperadoAttribute(): float
    {
        return round((float) $this->monto_recuperable * (float) $this->probabilidad_pago / 100, 2);
    }
}
