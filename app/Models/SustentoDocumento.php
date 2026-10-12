<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SustentoDocumento extends Model
{
    protected $table = 'sustento_documentos';
    public $timestamps = false;

    public function sustento()
    {
        return $this->belongsTo(Sustento::class, 'sustento_id');
    }
        protected $fillable = [
        'sustento_id','tipo_doc','nombre_archivo','ruta_archivo','hash','size_bytes',
        'observaciones_carga','estado_validacion','hallazgos','validado_en','modo_validacion',
        'comentario_revisor','estado_revision','conforme_manual','vigente','cargado_en',
    ];

    protected $casts = [
        'observaciones_carga' => 'array',
        'hallazgos'           => 'array',
        'validado_en'         => 'datetime',
        'cargado_en'          => 'datetime',
        'conforme_manual'     => 'boolean',
        'vigente'             => 'boolean',
    ];

    // La validación automática encontró algo (observado o rechazado)
    public function tieneObservacionesCarga(): bool
    {
        return in_array($this->estado_validacion, ['observado', 'rechazado']);
    }
}
