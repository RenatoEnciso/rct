<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Seguimiento extends Model
{
    protected $table = 'seguimiento';
    public $timestamps = false;

    protected $fillable = [
        'user_id','propuesta_fud_id','tipo_operacion',
        'cliente','operacion','estado','descripcion','fecha_accion',
    ];

    protected $casts = [
        'fecha_accion' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function propuesta()
    {
        return $this->belongsTo(PropuestaFud::class, 'propuesta_fud_id');
    }

    // Helper para registrar fácilmente desde los controllers
    public static function registrar(
        int $userId,
        string $tipo,
        string $estado,
        ?int $propuestaId = null,
        ?string $cliente = null,
        ?string $operacion = null,
        ?string $descripcion = null
    ): self {
        return self::create([
            'user_id'          => $userId,
            'propuesta_fud_id' => $propuestaId,
            'tipo_operacion'   => $tipo,
            'cliente'          => $cliente,
            'operacion'        => $operacion,
            'estado'           => $estado,
            'descripcion'      => $descripcion,
            'fecha_accion'     => now()->toDateString(),
        ]);
    }
}