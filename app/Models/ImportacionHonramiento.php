<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImportacionHonramiento extends Model
{
    protected $table = 'importaciones_honramiento';
    protected $fillable = ['user_id', 'archivo', 'filas', 'creados', 'actualizados', 'con_diferencias', 'con_error'];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
