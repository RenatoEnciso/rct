<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FudAdjunto extends Model
{
    protected $table    = 'fud_adjuntos';
    public    $timestamps = false;

    protected $fillable = [
        'propuesta_fud_id','subido_por',
        'nombre_archivo','ruta_archivo',
        'tipo_adjunto','size_bytes',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function propuesta()
    {
        return $this->belongsTo(PropuestaFud::class, 'propuesta_fud_id');
    }

    public function subidor()
    {
        return $this->belongsTo(User::class, 'subido_por');
    }

    public function getSizeFormateadoAttribute(): string
    {
        if (!$this->size_bytes) return '—';
        $kb = round($this->size_bytes / 1024);
        return $kb > 1024
            ? round($kb / 1024, 1) . ' MB'
            : $kb . ' KB';
    }
}