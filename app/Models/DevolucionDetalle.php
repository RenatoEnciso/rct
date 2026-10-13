<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Una fila del cruce: lo calculado por RCT frente a lo notificado por COFIDE para un crédito. */
class DevolucionDetalle extends Model
{
    protected $table = 'devolucion_detalles';
    protected $guarded = ['id'];
    protected $casts = ['capital' => 'decimal:2', 'factor_cobertura' => 'decimal:4', 'monto_rct' => 'decimal:2',
                        'monto_txt' => 'decimal:2', 'diferencia' => 'decimal:2'];

    public function devolucion() { return $this->belongsTo(DevolucionCofide::class, 'devolucion_id'); }
    public function expediente() { return $this->belongsTo(ExpedienteGobierno::class, 'expediente_id'); }
}
