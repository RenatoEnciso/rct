<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SustentoDocumento extends Model
{
    protected $table = 'sustento_documentos';
    public $timestamps = false;

    // protected $fillable = [
    //     'sustento_id','tipo_doc','nombre_archivo',
    //     'ruta_archivo','size_bytes',
    // ];

    public function sustento()
    {
        return $this->belongsTo(Sustento::class, 'sustento_id');
    }
    protected $fillable = [
    'sustento_id','tipo_doc','nombre_archivo','ruta_archivo','size_bytes',
    'observaciones_carga','estado_validacion','comentario_revisor','estado_revision',
];

protected $casts = [
    'observaciones_carga' => 'array',
];

public function tieneObservacionesCarga(): bool
{
    return in_array($this->estado_validacion, ['warn', 'error']);
}
}
