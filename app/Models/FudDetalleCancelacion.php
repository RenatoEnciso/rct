<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FudDetalleCancelacion extends Model
{
    protected $table = 'fud_detalle_cancelacion';
    public $timestamps = false;

    protected $fillable = [
        'propuesta_fud_id','monto_a_pagar','desc_interes',
        'desc_capital','fecha_limite','mayor_tres_uit','sunarp_positivo',
    ];

    protected $casts = [
        'fecha_limite'    => 'date',
        'mayor_tres_uit'  => 'boolean',
        'sunarp_positivo' => 'boolean',
        'monto_a_pagar'   => 'decimal:2',
    ];

    public function propuesta()
    {
        return $this->belongsTo(PropuestaFud::class, 'propuesta_fud_id');
    }
}