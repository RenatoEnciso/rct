<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Rol;

class RolSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['nombre' => 'Ejecutivo de Cobranza',               'grupo' => 'fud',       'nivel' => 1],
            ['nombre' => 'Ejecutivo Senior de Cobranza',        'grupo' => 'fud',       'nivel' => 2],
            ['nombre' => 'Coordinador Zonal de Recuperaciones', 'grupo' => 'aprobador', 'nivel' => 1],
            ['nombre' => 'Jefe de Recuperaciones',              'grupo' => 'aprobador', 'nivel' => 2],
            ['nombre' => 'Gerente de División de Negocios',     'grupo' => 'aprobador', 'nivel' => 3],
        ];
        foreach ($roles as $r) Rol::firstOrCreate(['nombre' => $r['nombre']], $r);
    }
}