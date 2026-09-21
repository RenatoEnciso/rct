<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Campana;

class CampanaSeeder extends Seeder
{
    public function run(): void
    {
        Campana::firstOrCreate(['nombre' => 'Sin Atrasos que Atrasan'], [
            'fecha_inicio'               => '2026-09-01',
            'fecha_fin'                  => '2027-02-28',
            'activa'                     => true,
            'max_conciliaciones_12m'     => 4,
            'max_dias_pago_fraccionado'  => 90,
            'limite_sk_uit'              => 15450.00,
        ]);
    }
}