<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sustento extends Model
{
    protected $table = 'sustentos';

    public function propuesta()
    {
        return $this->belongsTo(PropuestaFud::class, 'propuesta_fud_id');
    }

    public function cargador()
    {
        return $this->belongsTo(User::class, 'cargado_por');
    }

    // Documentos vigentes (los reemplazados en una corrección quedan en el historial)
    public function documentos()
    {
        return $this->hasMany(SustentoDocumento::class, 'sustento_id')->where('vigente', true);
    }

    public function historial()
    {
        return $this->hasMany(SustentoDocumento::class, 'sustento_id')->where('vigente', false)->latest('id');
    }

    public function estaCompleto(): bool
    {
        return $this->estado === 'Aprobado'; // sustento validado por Recuperaciones
    }

    protected $fillable = [
    'propuesta_fud_id','cargado_por','revisado_por',
    'estado','fecha_carga','fecha_revision','comentario_revision',
];

protected $casts = [
    'fecha_carga'     => 'date',
    'fecha_revision'  => 'datetime',
];

public function revisador()
{
    return $this->belongsTo(User::class, 'revisado_por');
}


public function estaObservado(): bool
{
    return $this->estado === 'Observado';
}

public function puedeRevisarse(): bool
{
    return $this->estado === 'En revisión';
}
}