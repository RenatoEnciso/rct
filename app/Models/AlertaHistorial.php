<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AlertaHistorial extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'alerta_historial';
    protected $guarded = ['id'];

    public function alerta()  { return $this->belongsTo(AlertaControl::class, 'alerta_id'); }
    public function usuario() { return $this->belongsTo(User::class, 'user_id'); }
}
