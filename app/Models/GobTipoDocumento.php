<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/** Documento del checklist de honramiento y su exigencia COFIDE según tipo de persona. */
class GobTipoDocumento extends Model
{
    protected $table = 'gob_tipos_documento';
    public $timestamps = false;
    protected $guarded = [];
    protected $casts = ['cofide_natural' => 'boolean', 'cofide_juridica' => 'boolean', 'activo' => 'boolean'];

    private static ?Collection $cache = null;

    public static function catalogo(): Collection
    {
        return self::$cache ??= self::where('activo', true)->orderBy('orden')->get();
    }

    public function exigidoPara(string $tipoPersona): bool
    {
        return $tipoPersona === 'juridica' ? $this->cofide_juridica : $this->cofide_natural;
    }
}
