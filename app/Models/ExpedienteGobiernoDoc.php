<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExpedienteGobiernoDoc extends Model
{
    protected $table = 'expediente_gobierno_docs';
    public $timestamps = false;

    protected $fillable = [
        'expediente_id','tipo_doc','nombre_archivo','ruta_archivo','hash',
        'estado_validacion','hallazgos','validado_en','modo_validacion',
        'conforme_manual','comentario_revisor','vigente','cargado_en',
    ];

    protected $casts = [
        'hallazgos'       => 'array',
        'validado_en'     => 'datetime',
        'cargado_en'      => 'datetime',
        'conforme_manual' => 'boolean',
        'vigente'         => 'boolean',
    ];

    // Conforme por la validación automática o marcado conforme por un revisor
    public function esConforme(): bool
    {
        return $this->estado_validacion === 'conforme' || $this->conforme_manual;
    }

    public function expediente()
    {
        return $this->belongsTo(ExpedienteGobierno::class, 'expediente_id');
    }
}