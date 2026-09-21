<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExpedienteGobiernoDoc extends Model
{
    protected $table = 'expediente_gobierno_docs';
    public $timestamps = false;

    protected $fillable = [
        'expediente_id','tipo_doc','nombre_archivo','ruta_archivo',
    ];

    public function expediente()
    {
        return $this->belongsTo(ExpedienteGobierno::class, 'expediente_id');
    }
}