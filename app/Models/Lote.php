<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Lote de una solicitud de honramiento ante COFIDE (cada carga en la plataforma es un lote).
 * Estados: En preparación → Pendiente de firma → Firmado → Cargado en COFIDE → Honrado (parcial o total) / Observado.
 * Plazo de documentación: 30 días calendario desde el día siguiente a la conformidad.
 */
class Lote extends Model
{
    public const PLAZO_DIAS = 30;
    public const ESTADOS = ['En preparación', 'Pendiente de firma', 'Firmado', 'Cargado en COFIDE', 'Observado', 'Honrado parcial', 'Honrado'];

    protected $fillable = [
        'codigo', 'programa', 'fecha_solicitud', 'fecha_conformidad', 'fecha_corte', 'fecha_carga', 'estado',
        'registros_rct', 'saldo_rct', 'registros_cofide', 'saldo_cofide', 'constancia_cofide', 'confirmado_en',
        'firmas', 'documentos_generados_en', 'fecha_honramiento', 'reporte_importado_en', 'reporte_archivo',
        'aviso_agencias_en', 'creado_por',
    ];
    protected $casts = [
        'fecha_solicitud' => 'date', 'fecha_conformidad' => 'date', 'fecha_corte' => 'date', 'fecha_carga' => 'date',
        'fecha_honramiento' => 'date', 'confirmado_en' => 'datetime', 'documentos_generados_en' => 'datetime',
        'reporte_importado_en' => 'datetime', 'aviso_agencias_en' => 'datetime', 'firmas' => 'array',
        'saldo_rct' => 'decimal:2', 'saldo_cofide' => 'decimal:2',
    ];

    public function creador() { return $this->belongsTo(User::class, 'creado_por'); }

    /** La confirmación de COFIDE no coincide con lo calculado por RCT. */
    public function tieneDiferencia(): bool
    {
        if ($this->registros_cofide === null) return false;
        return (int) $this->registros_cofide !== (int) $this->registros_rct
            || bccomp((string) $this->saldo_cofide, (string) $this->saldo_rct, 2) !== 0;
    }

    public function diferenciaSaldo(): string
    {
        return $this->saldo_cofide === null ? '0.00' : bcsub((string) $this->saldo_cofide, (string) $this->saldo_rct, 2);
    }

    public function firmasCompletas(): bool
    {
        $firmas = collect($this->firmas ?? []);
        return collect(config('rct.impulso.representantes'))->every(fn($r) => $firmas->contains(fn($f) => $f['nombre'] === $r['nombre']));
    }

    /** Nombre para mensajes: "Lote 011" (el código del área ya incluye la palabra Lote). */
    public function nombre(): string
    {
        $n = preg_replace('/\D/', '', (string) $this->codigo);
        return $n !== '' ? 'Lote '.$n : (string) $this->codigo;
    }

    public function esHonrado(): bool { return in_array($this->estado, ['Honrado', 'Honrado parcial']); }

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
