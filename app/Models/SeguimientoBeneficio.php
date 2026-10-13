<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Comportamiento del crédito a 30, 60 y 90 días del beneficio (análisis de recaída). */
class SeguimientoBeneficio extends Model
{
    protected $table = 'seguimiento_beneficio';
    protected $guarded = ['id'];
    protected $casts = ['fecha_beneficio' => 'date', 'fecha_recaida' => 'date'];

    public function propuesta() { return $this->belongsTo(PropuestaFud::class, 'propuesta_fud_id'); }
    public function cliente()   { return $this->belongsTo(ClienteCartera::class, 'cliente_cartera_id'); }
    public function asesor()    { return $this->belongsTo(User::class, 'asesor_id'); }
}
