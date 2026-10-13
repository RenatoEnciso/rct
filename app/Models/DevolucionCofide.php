<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Devolución mensual a COFIDE (reversiones agregadas del mes) y su cruce con el TXT de COFIDE. */
class DevolucionCofide extends Model
{
    public const ESTADOS = ['Calculada', 'Con diferencias', 'Cuadrada', 'Devuelta'];

    protected $table = 'devoluciones_cofide';
    protected $guarded = ['id'];
    protected $casts = [
        'periodo' => 'date', 'fecha_limite' => 'date', 'txt_importado_en' => 'datetime', 'cerrado_en' => 'datetime',
        'aviso_vencimiento_en' => 'datetime', 'monto_calculado' => 'decimal:2', 'monto_notificado' => 'decimal:2', 'diferencia' => 'decimal:2',
    ];

    public function detalles()   { return $this->hasMany(DevolucionDetalle::class, 'devolucion_id'); }
    public function pagos()      { return $this->hasMany(PagoPrograma::class, 'devolucion_id'); }
    public function cerrador()   { return $this->belongsTo(User::class, 'cerrado_por'); }
    public function cruzador()   { return $this->belongsTo(User::class, 'cruzado_por'); }

    public function nombrePeriodo(): string { return ucfirst($this->periodo->translatedFormat('F Y')); }
    public function cerrada(): bool { return $this->estado === 'Devuelta'; }
    public function cuadra(): bool { return $this->txt_importado_en && $this->n_diferencias === 0 && bccomp((string) $this->diferencia, '0', 2) === 0; }
    public function diasParaVencer(): int { return (int) now()->startOfDay()->diffInDays($this->fecha_limite, false); }
}
