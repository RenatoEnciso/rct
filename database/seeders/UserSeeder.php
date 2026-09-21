<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Rol;
use App\Models\Tienda;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $rolEjec  = Rol::where('nombre', 'Ejecutivo de Cobranza')->first();
        $rolCZR   = Rol::where('nombre', 'Coordinador Zonal de Recuperaciones')->first();
        $rolJefe  = Rol::where('nombre', 'Jefe de Recuperaciones')->first();
        $tienda1  = Tienda::first();

        User::firstOrCreate(['email' => 'ejecutivo1@cajatrujillo.pe'], [
            'name'      => 'Ejecutivo 1',
            'password'  => Hash::make('password'),
            'rol_id'    => $rolEjec->id,
            'tienda_id' => $tienda1->id,
        ]);

        User::firstOrCreate(['email' => 'czr@cajatrujillo.pe'], [
            'name'      => 'Coordinador Zonal',
            'password'  => Hash::make('password'),
            'rol_id'    => $rolCZR->id,
            'tienda_id' => $tienda1->id,
        ]);

        User::firstOrCreate(['email' => 'jefe@cajatrujillo.pe'], [
            'name'      => 'Jefe de Recuperaciones',
            'password'  => Hash::make('password'),
            'rol_id'    => $rolJefe->id,
            'tienda_id' => $tienda1->id,
        ]);
    }
}