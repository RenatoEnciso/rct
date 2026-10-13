<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Pago de un crédito del programa, sincronizado desde el core (no se registra a mano). */
class PagoPrograma extends Model
{
    protected $table = 'pagos_programa';
    protected $guarded = ['id'];
    protected $casts = [
        'fecha_pago' => 'datetime', 'sincronizado_en' => 'datetime',
        'monto_total' => 'decimal:2', 'capital' => 'decimal:2', 'interes' => 'decimal:2', 'mora' => 'decimal:2',
    ];

    public function tienda()        { return $this->belongsTo(Tienda::class); }
    public function sincronizacion(){ return $this->belongsTo(SincronizacionCore::class, 'sincronizacion_id'); }
}
