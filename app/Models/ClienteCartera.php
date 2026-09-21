<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class ClienteCartera extends Model
{
    protected $table = 'clientes_cartera';

    protected $fillable = [
        'zona_id','tienda_id','cuenta','operacion','nombre_cliente',
        'tipo_credito','saldo_capital','dias_atraso','calificacion_rcc',
        'estado_cartera','transaccion','veces_conciliado','activo_campana',
        'es_pignoraticio','es_leasing','es_multigas',
        'es_cartera_adq','es_prog_gobierno','es_fraude',
    ];

    protected $casts = [
        'saldo_capital'    => 'decimal:2',
        'activo_campana'   => 'boolean',
        'es_pignoraticio'  => 'boolean',
        'es_leasing'       => 'boolean',
        'es_multigas'      => 'boolean',
        'es_cartera_adq'   => 'boolean',
        'es_prog_gobierno' => 'boolean',
        'es_fraude'        => 'boolean',
    ];

    // ── Relaciones ──────────────────────────────────────────
    public function zona()
    {
        return $this->belongsTo(Zona::class, 'zona_id');
    }

    public function tienda()
    {
        return $this->belongsTo(Tienda::class, 'tienda_id');
    }

    public function propuestas()
    {
        return $this->hasMany(PropuestaFud::class, 'cliente_cartera_id');
    }

    public function expedientesGobierno()
    {
        return $this->hasMany(ExpedienteGobierno::class, 'cliente_cartera_id');
    }

    // ── Scopes ──────────────────────────────────────────────
    public function scopeActivos(Builder $q): Builder
    {
        return $q->where('activo_campana', true);
    }

    public function scopeSinExclusiones(Builder $q): Builder
    {
        return $q->where('es_pignoraticio', false)
                 ->where('es_leasing', false)
                 ->where('es_multigas', false)
                 ->where('es_cartera_adq', false)
                 ->where('es_fraude', false);
    }

    public function scopeDisponibles(Builder $q): Builder
    {
        return $q->activos()->sinExclusiones()
                 ->where('veces_conciliado', '<', 4);
    }

    // ── Helpers ─────────────────────────────────────────────
    public function superaLimite(): bool
    {
        return $this->veces_conciliado >= 4;
    }

    public function esMayor3UIT(): bool
    {
        // 1 UIT 2026 = S/ 5,150 → 3 UIT = S/ 15,450
        return $this->saldo_capital > 15450;
    }

    public function getSaldoFormateadoAttribute(): string
    {
        return 'S/ ' . number_format($this->saldo_capital, 2);
    }
}