<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Meta mensual de recupero por zona. */
class MetaZona extends Model
{
    protected $table = 'metas_zona';
    protected $guarded = ['id'];
    protected $casts = ['mes' => 'date', 'meta_recupero' => 'decimal:2'];

    public function zona() { return $this->belongsTo(Zona::class); }
}
