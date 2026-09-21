<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Zona;
use App\Models\Tienda;

class ZonaTiendaSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            'Norte'         => ['Agencia Principal', 'Tarapoto', 'Bagua Chica'],
            'Sur'           => ['Agencia Sur'],
            'Lima 1'        => ['Agencia Lima 1'],
            'Lima 2'        => ['Villa El Salvador', 'Montenegro'],
            'Centro Oriente'=> ['Agencia Centro Oriente'],
        ];

        foreach ($data as $zonaNombre => $tiendas) {
            $zona = Zona::firstOrCreate(['nombre' => $zonaNombre]);
            foreach ($tiendas as $t) {
                Tienda::firstOrCreate(['zona_id' => $zona->id, 'nombre' => $t]);
            }
        }
    }
}