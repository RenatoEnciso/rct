<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Crédito del programa que llegó a 90 días de atraso (detectado en la sincronización con el core). */
class CandidatoHonramiento extends Model
{
    protected $table = 'candidatos_honramiento';
    protected $guarded = ['id'];
    protected $casts = ['detectado_en' => 'datetime', 'notificado_en' => 'datetime'];
}
