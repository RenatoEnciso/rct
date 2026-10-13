<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Montos en soles de un FUD pagado: recuperado, condonado y provisión liberada. */
class Recupero extends Model
{
    protected $table = 'recuperos';
    protected $guarded = ['id'];
    protected $casts = [
        'fecha_pago' => 'date',
        'recuperado_capital' => 'decimal:2', 'recuperado_interes' => 'decimal:2',
        'condonado_capital' => 'decimal:2', 'condonado_interes' => 'decimal:2', 'condonado_mora' => 'decimal:2',
        'provision_liberada' => 'decimal:2',
    ];

    public function propuesta() { return $this->belongsTo(PropuestaFud::class, 'propuesta_fud_id'); }
    public function cliente()   { return $this->belongsTo(ClienteCartera::class, 'cliente_cartera_id'); }
    public function zona()      { return $this->belongsTo(Zona::class); }
    public function tienda()    { return $this->belongsTo(Tienda::class); }
    public function asesor()    { return $this->belongsTo(User::class, 'asesor_id'); }

    public function getRecuperadoAttribute(): float { return (float) $this->recuperado_capital + (float) $this->recuperado_interes; }
    public function getCondonadoAttribute(): float  { return (float) $this->condonado_capital + (float) $this->condonado_interes + (float) $this->condonado_mora; }
}
