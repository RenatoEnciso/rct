<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Campana extends Model
{
    protected $table = 'campanas';
    protected $fillable = [
        'nombre','fecha_inicio','fecha_fin','activa',
        'max_conciliaciones_12m','max_dias_pago_fraccionado','limite_sk_uit'
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin'    => 'date',
        'activa'       => 'boolean',
    ];

    public static function activa(): ?self
    {
        return self::where('activa', true)->latest()->first();
    }
}