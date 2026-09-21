<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tienda extends Model
{
    protected $table = 'tiendas';
    public $timestamps = false;
    protected $fillable = ['zona_id', 'nombre'];

    public function zona()
    {
        return $this->belongsTo(Zona::class, 'zona_id');
    }

    public function users()
    {
        return $this->hasMany(User::class, 'tienda_id');
    }

    public function clientesCartera()
    {
        return $this->hasMany(ClienteCartera::class, 'tienda_id');
    }
}