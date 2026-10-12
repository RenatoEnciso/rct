<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/** Lote de una solicitud formal de honramiento. Plazo: 30 días calendario desde la conformidad. */
class Lote extends Model
{
    public const PLAZO_DIAS = 30;

    protected $fillable = ['codigo', 'programa', 'fecha_solicitud', 'fecha_conformidad'];
    protected $casts = ['fecha_solicitud' => 'date', 'fecha_conformidad' => 'date'];

    public function expedientes()
    {
        return $this->hasMany(ExpedienteGobierno::class, 'lote_id');
    }

    /** Fecha límite para regularizar la carga: día siguiente a la conformidad + 30 días calendario. */
    public function fechaLimite(): ?Carbon
    {
        return $this->fecha_conformidad?->copy()->addDays(self::PLAZO_DIAS);
    }

    public function diasRestantes(): ?int
    {
        $limite = $this->fechaLimite();
        return $limite ? (int) now()->startOfDay()->diffInDays($limite, false) : null;
    }
}
