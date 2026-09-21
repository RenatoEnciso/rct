<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Zona extends Model
{
    protected $table = 'zonas';
    public $timestamps = false;
    protected $fillable = ['nombre'];

    public function tiendas()
    {
        return $this->hasMany(Tienda::class, 'zona_id');
    }

    public function clientesCartera()
    {
        return $this->hasMany(ClienteCartera::class, 'zona_id');
    }
}