<?php
namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $fillable = [
        'name', 'email', 'password', 'rol_id', 'tienda_id'
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password'          => 'hashed',
    ];

    // ── Relaciones ──────────────────────────────────────────
    public function rol()
    {
        return $this->belongsTo(Rol::class, 'rol_id');
    }

    public function tienda()
    {
        return $this->belongsTo(Tienda::class, 'tienda_id');
    }

    public function propuestasEnviadas()
    {
        return $this->hasMany(PropuestaFud::class, 'enviado_por');
    }

    public function propuestasPorAprobar()
    {
        return $this->hasMany(PropuestaFud::class, 'aprobador_id');
    }

    public function seguimientos()
    {
        return $this->hasMany(Seguimiento::class, 'user_id');
    }

    // ── Helpers de rol ──────────────────────────────────────
    public function grupo(): string
    {
        return $this->rol->grupo;
    }

    public function nivel(): int
    {
        return $this->rol->nivel;
    }

    public function esGrupoFud(): bool
    {
        return $this->rol->grupo === 'fud';
    }

    public function esAprobador(): bool
    {
        return $this->rol->grupo === 'aprobador';
    }

    public function puedeAprobar(): bool
    {
        // Aprobadores siempre pueden; ejecutivos nivel >= 2 también
        return $this->esAprobador() || $this->nivel() >= 2;
    }
}