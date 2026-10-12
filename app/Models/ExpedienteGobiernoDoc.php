<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Documento del checklist de un expediente de honramiento.
 * estado: cargado | conforme | observado | no_disponible ("falta" = no hay fila vigente)
 * origen: rct (archivo en el sistema) | sftp (presentado, archivo en el SFTP) | excel (registrado al importar)
 */
class ExpedienteGobiernoDoc extends Model
{
    protected $table = 'expediente_gobierno_docs';
    public $timestamps = false;

    protected $fillable = [
        'expediente_id', 'tipo_documento_id', 'tipo_doc', 'estado', 'origen', 'cargado_por',
        'nombre_archivo', 'ruta_archivo', 'hash',
        'estado_validacion', 'hallazgos', 'validado_en', 'modo_validacion',
        'conforme_manual', 'comentario_revisor', 'revisado_por', 'revisado_en',
        'motivo_no_disponible', 'vigente', 'cargado_en',
    ];

    protected $casts = [
        'hallazgos'       => 'array',
        'validado_en'     => 'datetime',
        'cargado_en'      => 'datetime',
        'revisado_en'     => 'datetime',
        'conforme_manual' => 'boolean',
        'vigente'         => 'boolean',
    ];

    public function expediente() { return $this->belongsTo(ExpedienteGobierno::class, 'expediente_id'); }
    public function tipo()       { return $this->belongsTo(GobTipoDocumento::class, 'tipo_documento_id'); }
    public function cargador()   { return $this->belongsTo(User::class, 'cargado_por'); }
    public function revisor()    { return $this->belongsTo(User::class, 'revisado_por'); }

    public function tieneArchivo(): bool
    {
        return (bool) $this->ruta_archivo;
    }

    // Presentado: cargado (archivo o SFTP) o ya revisado conforme
    public function esPresentado(): bool
    {
        return in_array($this->estado, ['cargado', 'conforme']);
    }

    public function esConforme(): bool
    {
        return $this->estado === 'conforme';
    }
}
