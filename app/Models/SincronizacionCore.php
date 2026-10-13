<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Registro de cada sincronización con el core (programada cada 30 minutos, manual o simulada). */
class SincronizacionCore extends Model
{
    protected $table = 'sincronizaciones_core';
    protected $guarded = ['id'];
    protected $casts = ['iniciado_en' => 'datetime', 'finalizado_en' => 'datetime'];

    public function usuario() { return $this->belongsTo(User::class, 'user_id'); }

    public static function ultima(): ?self
    {
        return self::where('estado', 'ok')->whereNotNull('finalizado_en')->latest('finalizado_en')->first();
    }
}
