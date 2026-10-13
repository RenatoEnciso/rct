<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Espejo de un crédito del core financiero (solo lectura para RCT). */
class CoreCredito extends Model
{
    protected $table = 'core_creditos';
    protected $guarded = ['id'];
    protected $casts = [
        'fecha_desembolso' => 'date', 'actualizado_en' => 'datetime',
        'monto_desembolso' => 'decimal:2', 'saldo_capital' => 'decimal:2', 'interes_devengado' => 'decimal:2',
        'factor_cobertura' => 'decimal:4',
    ];

    public function zona()   { return $this->belongsTo(Zona::class); }
    public function tienda() { return $this->belongsTo(Tienda::class); }
    public function pagos()  { return $this->hasMany(CorePago::class, 'credito_sicmact', 'credito_sicmact'); }
}
