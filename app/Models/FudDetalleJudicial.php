<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FudDetalleJudicial extends Model
{
    protected $table = 'fud_detalle_judicial';
    public $timestamps = false;

    protected $fillable = [
        'propuesta_fud_id','monto_gravamen',
        'etapa_procesal','beneficio_pct_sk',
    ];

    protected $casts = [
        'monto_gravamen'   => 'decimal:2',
        'beneficio_pct_sk' => 'decimal:2',
    ];

    public function propuesta()
    {
        return $this->belongsTo(PropuestaFud::class, 'propuesta_fud_id');
    }
}