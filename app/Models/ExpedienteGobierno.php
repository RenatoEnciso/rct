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

    // Documentos vigentes; los reemplazados quedan como historial
    public function documentos()
    {
        return $this->hasMany(ExpedienteGobiernoDoc::class, 'expediente_id')->where('vigente', true);
    }

    public const CHECKLIST = [
        'DNI / Reporte tributario con Ficha RUC', 'Hoja Resumen', 'Formulario Conocimiento del Cliente',
        'DJ del Programa – Anexo 02', 'DJ Patrimonial', 'Cargo del Contrato de Crédito',
        'Cronograma de Pagos', 'Pagaré', 'Clasificación Regulatoria (RCC, EXPERIAN)', 'Evidencia de Ingresos',
    ];

    // Completo: los 10 documentos del checklist cargados y conformes (automático o manual)
    public function estaCompleto(): bool
    {
        $docs = $this->documentos()->get();
        return collect(self::CHECKLIST)->every(fn($t) => $docs->firstWhere('tipo_doc', $t)?->esConforme());
    }

    public function recalcularEstado(): void
    {
        $this->update(['estado' => $this->estaCompleto() ? 'Completo' : 'Incompleto']);
    }
}