<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FudDetalleCuotas extends Model
{
    protected $table = 'fud_detalle_cuotas';
    public $timestamps = false;

    protected $fillable = [
        'propuesta_fud_id','cuota_desde','cuota_hasta',
        'desc_interes','desc_moratorio','pone_al_dia','cierra_mes_vigente',
    ];

    protected $casts = [
        'pone_al_dia'        => 'boolean',
        'cierra_mes_vigente' => 'boolean',
    ];

    public function propuesta()
    {
        return $this->belongsTo(PropuestaFud::class, 'propuesta_fud_id');
    }
}