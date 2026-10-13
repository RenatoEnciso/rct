<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Espejo de un pago registrado en el core financiero (solo lectura para RCT). */
class CorePago extends Model
{
    protected $table = 'core_pagos';
    protected $guarded = ['id'];
    protected $casts = [
        'fecha_pago' => 'datetime', 'registrado_en' => 'datetime',
        'monto_total' => 'decimal:2', 'capital' => 'decimal:2', 'interes' => 'decimal:2', 'mora' => 'decimal:2',
    ];

    public function credito() { return $this->belongsTo(CoreCredito::class, 'credito_sicmact', 'credito_sicmact'); }
    public function tienda()  { return $this->belongsTo(Tienda::class); }
}
