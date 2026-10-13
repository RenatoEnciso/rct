<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Pago de un crédito del programa, sincronizado desde el core (no se registra a mano). */
class PagoPrograma extends Model
{
    protected $table = 'pagos_programa';
    protected $guarded = ['id'];
    public const ESTADOS = ['Sincronizado', 'Agregado', 'Incluido en devolución', 'Devuelto', 'Sin reversión'];

    protected $casts = [
        'fecha_pago' => 'datetime', 'sincronizado_en' => 'datetime', 'agregado_en' => 'datetime', 'honrado' => 'boolean',
        'monto_total' => 'decimal:2', 'capital' => 'decimal:2', 'interes' => 'decimal:2', 'mora' => 'decimal:2',
        'reversion' => 'decimal:2', 'factor_cobertura' => 'decimal:4',
    ];

    public function expediente() { return $this->belongsTo(ExpedienteGobierno::class, 'expediente_id'); }

    public function tienda()        { return $this->belongsTo(Tienda::class); }
    public function sincronizacion(){ return $this->belongsTo(SincronizacionCore::class, 'sincronizacion_id'); }
}
