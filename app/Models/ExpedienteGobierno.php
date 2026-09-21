<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExpedienteGobierno extends Model
{
    protected $table = 'expedientes_gobierno';

    protected $fillable = [
        'cliente_cartera_id','registrado_por','programa',
        'motivo_habilitacion','confirmacion_dpto',
        'responsable','fecha_registro','estado',
    ];

    protected $casts = [
        'fecha_registro'    => 'date',
        'confirmacion_dpto' => 'boolean',
    ];

    public function cliente()
    {
        return $this->belongsTo(ClienteCartera::class, 'cliente_cartera_id');
    }

    public function registrador()
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }

    public function documentos()
    {
        return $this->hasMany(ExpedienteGobiernoDoc::class, 'expediente_id');
    }

    public function estaCompleto(): bool
    {
        return $this->documentos()->count() >= 10;
    }
}