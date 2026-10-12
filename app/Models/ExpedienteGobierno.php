<?php
namespace App\Models;

use App\Services\Honramiento\Checklist;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Expediente de honramiento de un crédito con garantía de Programa de Gobierno.
 * Estado y N° de faltantes se calculan (App\Services\Honramiento\Checklist); nunca se escriben a mano.
 */
class ExpedienteGobierno extends Model
{
    protected $table = 'expedientes_gobierno';

    public const ESTADOS = ['Pendiente', 'Incompleto', 'Incompleto definitivo', 'Completo', 'Observado', 'Revisado por zona', 'Conforme'];

    protected $fillable = [
        'gestion_id', 'id_externo', 'dni', 'tipo_persona', 'credito_sicmact', 'cuenta_bt', 'operacion', 'cliente',
        'zona_id', 'tienda_id', 'lote_id', 'monto_honrado', 'observaciones',
        'cliente_cartera_id', 'registrado_por', 'programa', 'motivo_habilitacion', 'confirmacion_dpto',
        'responsable', 'fecha_registro',
        // calculados por Checklist::recalcular()
        'estado', 'faltantes', 'revisado_zona_en', 'revisado_central_en',
    ];

    protected $casts = [
        'fecha_registro'      => 'date',
        'confirmacion_dpto'   => 'boolean',
        'monto_honrado'       => 'decimal:2',
        'revisado_zona_en'    => 'datetime',
        'revisado_central_en' => 'datetime',
    ];

    public function gestion()     { return $this->belongsTo(Gestion::class, 'gestion_id'); }
    public function lote()        { return $this->belongsTo(Lote::class, 'lote_id'); }
    public function zona()        { return $this->belongsTo(Zona::class, 'zona_id'); }
    public function tienda()      { return $this->belongsTo(Tienda::class, 'tienda_id'); }
    public function cartera()     { return $this->belongsTo(ClienteCartera::class, 'cliente_cartera_id'); }
    public function registrador() { return $this->belongsTo(User::class, 'registrado_por'); }

    // Documentos vigentes; los reemplazados quedan como historial
    public function documentos()
    {
        return $this->hasMany(ExpedienteGobiernoDoc::class, 'expediente_id')->where('vigente', true);
    }

    public function historialDocumentos()
    {
        return $this->hasMany(ExpedienteGobiernoDoc::class, 'expediente_id')->where('vigente', false)->latest('id');
    }

    public function revisiones()
    {
        return $this->hasMany(ExpedienteGobiernoRevision::class, 'expediente_id')->orderByDesc('revisado_en')->orderByDesc('id');
    }

    /** Checklist con el estado de cada documento (ver Checklist::documentos). */
    public function checklist()
    {
        return app(Checklist::class)->documentos($this);
    }

    public function recalcularEstado(): void
    {
        app(Checklist::class)->recalcular($this);
    }

    /** Observaciones automáticas (faltantes, observados, no disponibles y complementarios faltantes). */
    public function observacionesAutomaticas(): string
    {
        return app(Checklist::class)->observaciones($this);
    }

    // ── Plazo de regularización (30 días desde la conformidad del lote) ──

    public function situacionPlazo(): ?string
    {
        $dias = $this->lote?->diasRestantes();
        if ($dias === null || in_array($this->estado, ['Completo', 'Revisado por zona', 'Conforme'])) return null;
        return $dias < 0 ? 'vencido' : ($dias <= 7 ? 'por_vencer' : 'vigente');
    }

    public function scopeVisiblePara(Builder $q, User $u): Builder
    {
        // CZR: su zona · Jefatura y Gerencia: todo · Tienda: su tienda
        if ($u->esGrupoFud()) return $q->where('tienda_id', $u->tienda_id);
        if ($u->nivel() === 1 && $u->tienda) return $q->where('zona_id', $u->tienda->zona_id);
        return $q;
    }
}
