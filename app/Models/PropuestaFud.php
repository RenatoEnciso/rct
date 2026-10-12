<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class PropuestaFud extends Model
{
    protected $table = 'propuestas_fud';

    protected $fillable = [
        'parent_fud_id',
        'cliente_cartera_id','enviado_por','aprobador_id',
        'tipo_negociacion','tipo_negociacion_desc',
        'en_campana','tipo_operacion','motivo',
        'nivel_autonomia_req','estado_fud','veces_observado',
        'aprobado_por','comentario_aprobador',
        'fecha_decision','decidido_en','fecha_envio',
    ];

    protected $casts = [
        'fecha_envio'    => 'date',
        'fecha_decision' => 'date',
        'decidido_en'    => 'datetime',
        'en_campana'     => 'boolean',
    ];

    // ── Relaciones ───────────────────────────────────────────
    public function cliente()
    {
        return $this->belongsTo(ClienteCartera::class, 'cliente_cartera_id');
    }
    public function enviador()
    {
        return $this->belongsTo(User::class, 'enviado_por');
    }
    public function aprobador()
    {
        return $this->belongsTo(User::class, 'aprobador_id');
    }
    public function aprobadoPor()
    {
        return $this->belongsTo(User::class, 'aprobado_por');
    }
    public function detalleCuotas()
    {
        return $this->hasOne(FudDetalleCuotas::class, 'propuesta_fud_id');
    }
    public function detalleCancelacion()
    {
        return $this->hasOne(FudDetalleCancelacion::class, 'propuesta_fud_id');
    }
    public function detalleJudicial()
    {
        return $this->hasOne(FudDetalleJudicial::class, 'propuesta_fud_id');
    }
    public function sustentos()
    {
        return $this->hasMany(Sustento::class, 'propuesta_fud_id');
    }
    public function seguimientos()
    {
        return $this->hasMany(Seguimiento::class, 'propuesta_fud_id');
    }
    public function adjuntos()
    {
        return $this->hasMany(FudAdjunto::class, 'propuesta_fud_id');
    }

    // Versión padre (FUD original que fue observado)
    public function padre()
    {
        return $this->belongsTo(PropuestaFud::class, 'parent_fud_id');
    }

    // Versiones hijas (correcciones de este FUD)
    public function versiones()
    {
        return $this->hasMany(PropuestaFud::class, 'parent_fud_id');
    }

    // Hilo completo de versiones (recursivo hasta la raíz)
    public function hilo(): \Illuminate\Support\Collection
    {
        $items = collect([$this]);
        $actual = $this;
        while ($actual->parent_fud_id) {
            $actual = $actual->padre()->with('enviador','adjuntos')->first();
            if ($actual) $items->prepend($actual);
            else break;
        }
        return $items;
    }

    // Última corrección de la cadena (la versión vigente del expediente)
    public function ultimaVersion(): self
    {
        $actual = $this;
        while ($hija = $actual->versiones()->latest('id')->first()) {
            $actual = $hija;
        }
        return $actual;
    }

    // Una versión reemplazada por una corrección se muestra como "Corregido", no como rechazo
    public function estadoVisible(): string
    {
        if ($this->estado_fud === 'Rechazado' && $this->versiones()->exists()) return 'Corregido';
        return $this->estado_fud;
    }

    // ── Scopes ───────────────────────────────────────────────
    public function scopeEnRevision(Builder $q): Builder
    {
        return $q->where('estado_fud', 'En revisión');
    }
    public function scopeAprobados(Builder $q): Builder
    {
        return $q->where('estado_fud', 'Aprobado');
    }
    public function scopePorAprobador(Builder $q, int $userId): Builder
    {
        return $q->where('aprobador_id', $userId);
    }
    public function scopePorEnviador(Builder $q, int $userId): Builder
    {
        return $q->where('enviado_por', $userId);
    }
    // Expedientes vigentes: la última versión de cada FUD (sin correcciones posteriores).
    // Es la base de todos los indicadores, para no contar versiones reemplazadas.
    public function scopeVigentes(Builder $q): Builder
    {
        return $q->whereDoesntHave('versiones');
    }

    // Solo FUDs raíz (no correcciones) para el listado principal
    public function scopeRaiz(Builder $q): Builder
    {
        return $q->whereNull('parent_fud_id');
    }

    // ── Helpers ──────────────────────────────────────────────
    public function estaEnRevision(): bool  { return $this->estado_fud === 'En revisión'; }
    public function estaAprobado(): bool    { return $this->estado_fud === 'Aprobado'; }
    public function estaObservado(): bool   { return $this->estado_fud === 'Observado'; }
    public function esCorreccion(): bool    { return !is_null($this->parent_fud_id); }

    public function badgeClass(): string
    {
        return match($this->estadoVisible()) {
            'Corregido'   => 'badge-gray',
            'Aprobado'    => 'badge-ok',
            'Observado',
            'Rechazado'   => 'badge-danger',
            default       => 'badge-pending',
        };
    }

    public function getDetalle(): ?Model
    {
        return match($this->tipo_negociacion) {
            'cuotas'      => $this->detalleCuotas,
            'cancelacion' => $this->detalleCancelacion,
            default       => $this->detalleJudicial,
        };
    }



}