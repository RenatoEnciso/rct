<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rol extends Model
{
    protected $table = 'roles';
    protected $fillable = ['nombre', 'grupo', 'nivel', 'descripcion'];

    public function users()
    {
        return $this->hasMany(User::class, 'rol_id');
    }

    // Helpers
    public function esGrupoFud(): bool
    {
        return $this->grupo === 'fud';
    }

    public function esAprobador(): bool
    {
        return $this->grupo === 'aprobador';
    }
}