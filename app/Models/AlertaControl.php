<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Alerta de control (detección de anomalías) para Jefatura y Auditoría. */
class AlertaControl extends Model
{
    public const ESTADOS = ['Nueva', 'En revisión', 'Descartada', 'Confirmada'];
    public const ABIERTAS = ['Nueva', 'En revisión'];

    protected $table = 'alertas_control';
    protected $guarded = ['id'];
    protected $casts = [
        'serie' => 'array', 'detectada_en' => 'datetime',
        'valor_observado' => 'decimal:2', 'valor_referencia' => 'decimal:2',
    ];

    public function zona()      { return $this->belongsTo(Zona::class); }
    public function historial() { return $this->hasMany(AlertaHistorial::class, 'alerta_id')->latest('id'); }

    public function scopeAbiertas($q) { return $q->whereIn('estado', self::ABIERTAS); }
}
