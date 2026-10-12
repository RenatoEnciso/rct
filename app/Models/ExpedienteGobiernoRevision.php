<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExpedienteGobiernoRevision extends Model
{
    protected $table = 'expediente_gobierno_revisiones';
    public $timestamps = false;
    protected $fillable = ['expediente_id', 'nivel', 'resultado', 'comentario', 'user_id', 'origen', 'revisado_en'];
    protected $casts = ['revisado_en' => 'datetime'];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function expediente()
    {
        return $this->belongsTo(ExpedienteGobierno::class, 'expediente_id');
    }
}
