<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\{Hash, Storage};
use App\Models\{
    User, Rol, Zona, Tienda, ClienteCartera, PropuestaFud,
    FudDetalleCuotas, FudDetalleCancelacion, FudDetalleJudicial, FudAdjunto,
    Sustento, SustentoDocumento, Seguimiento, ExpedienteGobierno, ExpedienteGobiernoDoc
};
use App\Services\AsignadorAprobador;

/**
 * Datos de demostración coherentes para presentar RCT.
 * - Aditivo: no borra datos existentes. Si ya se ejecutó, no hace nada.
 * - Determinista: misma semilla → mismos datos en cada instalación.
 * Uso: php artisan db:seed --class=DemoSeeder
 */
class DemoSeeder extends Seeder
{
    private const MARCADOR = 'gerente@cajatrujillo.pe';
    private const PASSWORD = 'password';

    private \Faker\Generator $faker;
    private AsignadorAprobador $asignador;
    private Carbon $hoy;
    private array $tiendas = [];        // nombre => Tienda
    private array $ejecutivosZona = []; // zona_id => [User]
    private array $czrZona = [];        // zona_id => User
    private User $jefe;
    private int $docSeq = 0;

    public function run(): void
    {
        if (User::where('email', self::MARCADOR)->exists()) {
            $this->command?->warn('Los datos de demo ya existen; no se vuelven a crear.');
            return;
        }

        Model::unguard();
        mt_srand(2026);
        $this->faker = \Faker\Factory::create('es_PE');
        $this->faker->seed(2026);
        $this->asignador = app(AsignadorAprobador::class);
        $this->hoy = now()->startOfDay();

        $this->crearTiendas();
        $this->crearUsuarios();
        $clientes = $this->crearCartera(175);
        $this->crearFuds($clientes);
        $this->crearProgramasGobierno(25);

        Model::reguard();
        $this->command?->info('Demo lista: '.ClienteCartera::count().' créditos, '
            .PropuestaFud::count().' FUD, '.Sustento::count().' sustentos, '
            .ExpedienteGobierno::count().' expedientes de gobierno.');
    }

    // ── Estructura ───────────────────────────────────────────

    private function crearTiendas(): void
    {
        $data = [
            'Norte'          => ['Agencia Principal', 'Tarapoto', 'Bagua Chica', 'Chiclayo', 'Piura'],
            'Sur'            => ['Agencia Sur', 'Arequipa', 'Tacna'],
            'Lima 1'         => ['Agencia Lima 1', 'San Juan de Lurigancho'],
            'Lima 2'         => ['Villa El Salvador', 'Montenegro'],
            'Centro Oriente' => ['Agencia Centro Oriente', 'Huánuco', 'Pucallpa'],
        ];
        foreach ($data as $zonaNombre => $tiendas) {
            $zona = Zona::firstOrCreate(['nombre' => $zonaNombre]);
            foreach ($tiendas as $t) {
                $this->tiendas[$t] = Tienda::firstOrCreate(['zona_id' => $zona->id, 'nombre' => $t]);
            }
        }
    }

    private function crearUsuarios(): void
    {
        $rol = fn(string $n) => Rol::firstOrCreate(['nombre' => $n])->id;
        $usuario = function (string $email, string $nombre, string $rolNombre, string $tienda) use ($rol) {
            return User::firstOrCreate(['email' => $email], [
                'name'      => $nombre,
                'password'  => Hash::make(self::PASSWORD),
                'rol_id'    => $rol($rolNombre),
                'tienda_id' => $this->tiendas[$tienda]->id,
            ]);
        };

        $czr = [
            ['czr@cajatrujillo.pe',        'Coordinador Zonal',        'Agencia Principal'],
            ['czr.sur@cajatrujillo.pe',    'Patricia Salazar Rivas',   'Agencia Sur'],
            ['czr.lima1@cajatrujillo.pe',  'Jorge Medina Paredes',     'Agencia Lima 1'],
            ['czr.lima2@cajatrujillo.pe',  'Rosa Huamán Quispe',       'Villa El Salvador'],
            ['czr.centro@cajatrujillo.pe', 'Luis Alberto Rojas Vega',  'Agencia Centro Oriente'],
        ];
        foreach ($czr as [$e, $n, $t]) {
            $u = $usuario($e, $n, 'Coordinador Zonal de Recuperaciones', $t);
            $this->czrZona[$this->tiendas[$t]->zona_id] = $u;
        }
        $this->jefe = $usuario('jefe@cajatrujillo.pe', 'Jefe de Recuperaciones', 'Jefe de Recuperaciones', 'Agencia Principal');
        $usuario(self::MARCADOR, 'Carlos Benites Alvarado', 'Gerente de División de Negocios', 'Agencia Principal');

        $ejecutivos = [
            ['ejecutivo1@cajatrujillo.pe',  'Ejecutivo 1',                'Agencia Principal',      'Ejecutivo de Cobranza'],
            ['ejecutivo2@cajatrujillo.pe',  'María Fernanda Castillo',    'Chiclayo',               'Ejecutivo de Cobranza'],
            ['ejecutivo3@cajatrujillo.pe',  'Diego Alonso Vásquez',       'Piura',                  'Ejecutivo de Cobranza'],
            ['ejecutivo4@cajatrujillo.pe',  'Karen Mendoza Ruiz',         'Tarapoto',               'Ejecutivo de Cobranza'],
            ['ejecutivo5@cajatrujillo.pe',  'Renzo Gutiérrez Polo',       'Arequipa',               'Ejecutivo de Cobranza'],
            ['ejecutivo6@cajatrujillo.pe',  'Lucía Torres Cárdenas',      'Tacna',                  'Ejecutivo de Cobranza'],
            ['ejecutivo7@cajatrujillo.pe',  'Miguel Ángel Ramos',         'San Juan de Lurigancho', 'Ejecutivo de Cobranza'],
            ['ejecutivo8@cajatrujillo.pe',  'Ana Lucía Paredes',          'Agencia Lima 1',         'Ejecutivo de Cobranza'],
            ['ejecutivo9@cajatrujillo.pe',  'Pedro Chávez Linares',       'Montenegro',             'Ejecutivo de Cobranza'],
            ['ejecutivo10@cajatrujillo.pe', 'Sofía Espinoza Llanos',      'Huánuco',                'Ejecutivo de Cobranza'],
            ['ejecutivo11@cajatrujillo.pe', 'Javier Ríos Mendoza',        'Pucallpa',               'Ejecutivo de Cobranza'],
            ['senior1@cajatrujillo.pe',     'Gabriela Núñez Ortiz',       'Agencia Principal',      'Ejecutivo Senior de Cobranza'],
            ['senior2@cajatrujillo.pe',     'Óscar Villanueva Soto',      'Villa El Salvador',      'Ejecutivo Senior de Cobranza'],
            ['senior3@cajatrujillo.pe',     'Elena Flores Gamarra',       'Agencia Centro Oriente', 'Ejecutivo Senior de Cobranza'],
            ['senior4@cajatrujillo.pe',     'Raúl Cabrera Díaz',          'Agencia Sur',            'Ejecutivo Senior de Cobranza'],
        ];
        foreach ($ejecutivos as [$e, $n, $t, $r]) {
            $u = $usuario($e, $n, $r, $t);
            $this->ejecutivosZona[$this->tiendas[$t]->zona_id][] = $u;
        }
    }

    // ── Cartera ──────────────────────────────────────────────

    private function crearCartera(int $n): array
    {
        $perfiles = [
            // [peso, tipo_negociacion, estado_cartera, atraso min-max, calificaciones, saldo min-max]
            [33, 'cuotas',        'VENCIDO',       [31, 150],   ['CPP', 'DEFICIENTE', 'DUDOSO'], [2500, 38000]],
            [25, 'cancelacion',   'VENCIDO',       [31, 360],   ['CPP', 'DEFICIENTE', 'DUDOSO', 'PÉRDIDA'], [1800, 65000]],
            [9,  'extrajudicial', 'EXTRAJUDICIAL', [120, 700],  ['DUDOSO', 'PÉRDIDA'], [6000, 120000]],
            [6,  'extrajudicial', 'JUDICIAL',      [200, 1100], ['PÉRDIDA'], [9000, 180000]],
            [21, 'castigada',     'CASTIGADO',     [400, 2200], ['PÉRDIDA'], [1200, 90000]],
            [6,  'focmac',        'FOCMAC',        [365, 1800], ['PÉRDIDA'], [1500, 45000]],
        ];
        $lineamientos = [
            'cuotas'        => ['DESCUENTO hasta 30% de intereses comp. y mora', 'DESCUENTO hasta 60% de intereses comp. y mora', 'DESCUENTO hasta 70% de intereses comp. y mora'],
            'cancelacion'   => ['DESCUENTO hasta 50% de saldo capital y 100% de intereses comp. y mora', 'Aplica la EXONERACION según lineamiento de campaña'],
            'extrajudicial' => ['DESCUENTO hasta 70% de saldo capital y 100% de intereses comp. y mora'],
            'castigada'     => ['DESCUENTO hasta 70% de saldo capital y 100% de intereses comp. y mora'],
            'focmac'        => ['Aplica la EXONERACION según lineamiento de campaña'],
        ];
        $tiposCredito = [
            ['PEQUEÑA EMPRESA', 'PEQUEÑA EMPRESA'], ['MICROEMPRESA', 'MICROEMPRESA'],
            ['CONSUMO', 'CONSUMO NO REVOLVENTE'], ['MEDIANA EMPRESA', 'MEDIANA EMPRESA'],
            ['HIPOTECARIO', 'HIPOTECARIO PARA VIVIENDA'],
        ];
        $tiendas = array_values($this->tiendas);
        $base    = (int) (ClienteCartera::max('id') ?? 0) + 1000;
        $clientes = [];

        for ($i = 0; $i < $n; $i++) {
            $p      = $this->elegirPonderado($perfiles);
            [$tc, $mod] = $tiposCredito[mt_rand(0, count($tiposCredito) - 1)];
            $tienda = $tiendas[mt_rand(0, count($tiendas) - 1)];
            $dias   = mt_rand($p[3][0], $p[3][1]);
            $saldo  = round(mt_rand($p[5][0] * 100, $p[5][1] * 100) / 100, 2);
            $calif  = $p[4][mt_rand(0, count($p[4]) - 1)];
            $excl   = mt_rand(1, 100) <= 4;
            $usos   = $this->elegirPonderado([[70, 0], [16, 1], [8, 2], [4, 3], [2, 4]])[1];

            $clientes[] = ClienteCartera::create([
                'zona_id'           => $tienda->zona_id,
                'tienda_id'         => $tienda->id,
                'credito_sicmact'   => 'SIC-'.str_pad($base + $i, 6, '0', STR_PAD_LEFT),
                'cuenta'            => 200000000 + $base + $i,
                'operacion'         => 100000000 + ($base + $i) * 7,
                'nombre_cliente'    => $this->nombrePersona(),
                'tipo_credito'      => $tc,
                'tipo_cliente'      => 'NATURAL',
                'modulo'            => $mod,
                'saldo_capital'     => $saldo,
                'moneda'            => 'PEN',
                'uit_referencia'    => 5150.00,
                'monto_desembolso'  => round($saldo * (1 + mt_rand(15, 80) / 100), 2),
                'dias_atraso'       => $dias,
                'rango_atraso'      => $this->rango($dias),
                'calificacion_rcc'  => $calif,
                'situacion_credito' => $calif,
                'estado_cartera'    => $p[2],
                'transaccion'       => mt_rand(1, 100) <= 20 ? '300/140' : '300/100',
                'veces_conciliado'  => $usos,
                'conc_operacion_12m'=> $usos,
                'conc_cuenta_12m'   => $usos,
                'tipo_negociacion_campana' => $p[1],
                'lineamiento_campana'      => $lineamientos[$p[1]][mt_rand(0, count($lineamientos[$p[1]]) - 1)],
                'autonomia'         => $saldo > 200000 ? 'Gerente de División'
                                       : ($saldo > 15450 || in_array($p[1], ['castigada', 'focmac']) ? 'Jefe de Recuperaciones' : 'CZR'),
                'activo_campana'    => true,
                'es_pignoraticio'   => $excl && mt_rand(0, 1),
                'es_leasing'        => false,
                'es_multigas'       => $excl && !mt_rand(0, 2),
                'es_cartera_adq'    => false,
                'es_prog_gobierno'  => false,
                'es_fraude'         => false,
                'asesor_negocio'    => $this->faker->firstName().' '.$this->faker->lastName(),
                'tipo_documento'    => 'DNI',
                'id_tipo_documento' => 1,
                'nro_documento'     => (string) mt_rand(10000000, 79999999),
                'telefono'          => '9'.mt_rand(10000000, 99999999),
                'direccion'         => $this->faker->streetAddress().', '.$tienda->nombre,
                'garante_nombre'    => mt_rand(1, 100) <= 40 ? $this->nombrePersona() : null,
                'garante_celular'   => mt_rand(1, 100) <= 40 ? '9'.mt_rand(10000000, 99999999) : null,
            ]);
        }
        return $clientes;
    }

    // ── FUD, decisiones y sustentos ──────────────────────────

    private function crearFuds(array $clientes): void
    {
        $motivos = [
            'Cliente afectado por la caída de ventas de su negocio tras las lluvias de la temporada; muestra voluntad de pago y propone regularizar.',
            'Titular con enfermedad familiar que redujo sus ingresos los últimos meses; cuenta con apoyo del garante para pagar en tienda.',
            'Negocio de abarrotes con menor rotación por competencia en la zona; el cliente ha cumplido promesas de pago anteriores.',
            'Cliente retomó actividad laboral formal y puede asumir el pago con el beneficio de la campaña; se verificó capacidad de pago.',
            'Actividad agrícola afectada por el precio del producto; el cliente vendió parte de la cosecha y dispone del monto propuesto.',
            'Cliente con deuda antigua que busca limpiar su historial crediticio; familiar asume el pago al contado con el descuento.',
            'Transportista con unidad en mantenimiento prolongado; ya retomó operaciones y propone ponerse al día con la cuota.',
            'Comerciante del mercado mayorista con disminución de ingresos por el efecto inflacionario; mantiene contacto permanente con el asesor.',
        ];
        $obsAprobador = [
            'Precisar las cuotas a pagar y adjuntar captura del estado de cuenta actualizado.',
            'El porcentaje de interés solicitado supera el lineamiento; ajustar la propuesta.',
            'Adjuntar evidencia de la gestión de cobranza y del contacto con el garante.',
            'Detallar la fuente de los fondos para la cancelación.',
        ];
        $rechazos = [
            'El cliente no cumple con la voluntad de pago demostrada en gestiones anteriores.',
            'Propuesta fuera de lineamiento: el crédito presenta garantía realizable.',
            'Cliente con otro crédito vencido en la Caja (alineamiento interno).',
        ];

        $elegibles = array_values(array_filter($clientes, fn($c) =>
            !$c->es_pignoraticio && !$c->es_multigas && $c->veces_conciliado < 4));
        shuffle($elegibles);

        foreach ($elegibles as $idx => $cli) {
            if ($idx >= 165) break;

            // Más actividad reciente: la campaña empezó el 01/09
            $hace   = (int) floor(92 * pow(mt_rand(0, 1000) / 1000, 1.3));
            $envio  = $this->hoy->copy()->subDays($hace);
            $ejec   = $this->ejecutivoDe($cli);
            $res    = $this->elegirPonderado([
                [52, 'Aprobado'], [11, 'En revisión'], [8, 'Observado'], [7, 'Rechazado'],
                [9, 'Corregido-Aprobado'], [4, 'Corregido-Revision'], [9, 'Aprobado'],
            ])[1];
            // Lo que sigue en revisión es reciente
            if (in_array($res, ['En revisión', 'Corregido-Revision'])) {
                $envio = $this->hoy->copy()->subDays(mt_rand(0, 6));
            }

            $nivel = $this->asignador->nivelRequerido($cli);
            $apr   = $this->asignador->asignar($cli, $ejec->id);
            $tipo  = $cli->tipo_negociacion_campana;

            if (str_starts_with($res, 'Corregido')) {
                // Versión 1 observada y luego corregida
                $dec1 = $this->despues($envio, 1, 3);
                $v1 = $this->fud($cli, $ejec, $apr, $nivel, $tipo, $envio, $motivos, 'Rechazado', $dec1,
                    $obsAprobador[mt_rand(0, count($obsAprobador) - 1)]);
                $this->log($apr, 'Decisión FUD', 'Observado', $v1, $dec1, $v1->comentario_aprobador);
                $envio2 = $this->despues($dec1, 0, 2);
                $estado2 = $res === 'Corregido-Aprobado' ? 'Aprobado' : 'En revisión';
                $dec2 = $estado2 === 'Aprobado' ? $this->despues($envio2, 0, 2) : null;
                $v2 = $this->fud($cli, $ejec, $apr, $nivel, $tipo, $envio2, $motivos, $estado2, $dec2, null, $v1);
                $this->log($ejec, 'Corrección de FUD', 'En revisión', $v2, $envio2, 'Corrección #1 enviada tras observación.');
                if ($estado2 === 'Aprobado') $this->aprobar($v2, $apr, $cli, $dec2);
                continue;
            }

            $dec = $res === 'En revisión' ? null : $this->despues($envio, 0, 4);
            $comentario = match ($res) {
                'Observado' => $obsAprobador[mt_rand(0, count($obsAprobador) - 1)],
                'Rechazado' => $rechazos[mt_rand(0, count($rechazos) - 1)],
                default     => null,
            };
            $fud = $this->fud($cli, $ejec, $apr, $nivel, $tipo, $envio, $motivos, $res, $dec, $comentario);
            if ($res === 'Aprobado') {
                $this->aprobar($fud, $apr, $cli, $dec);
            } elseif ($dec) {
                $this->log($apr, 'Decisión FUD', $res, $fud, $dec, $comentario);
            }
        }
    }

    private function fud(ClienteCartera $cli, User $ejec, User $apr, int $nivel, string $tipo, Carbon $envio,
                         array $motivos, string $estado, ?Carbon $dec, ?string $comentario, ?PropuestaFud $padre = null): PropuestaFud
    {
        $etiquetas = [
            'cuotas' => 'Pago de Cuotas', 'cancelacion' => 'Cancelación Total',
            'extrajudicial' => 'Cartera Extrajudicial / Judicial', 'castigada' => 'Cartera Castigada', 'focmac' => 'Cartera FOCMAC',
        ];
        $enCampana = $envio->gte('2026-09-01');
        $creado    = $envio->copy()->setTime(mt_rand(8, 17), mt_rand(0, 59));

        $fud = PropuestaFud::create([
            'parent_fud_id'         => $padre?->id,
            'cliente_cartera_id'    => $cli->id,
            'enviado_por'           => $ejec->id,
            'aprobador_id'          => $apr->id,
            'tipo_negociacion'      => $tipo,
            'tipo_negociacion_desc' => $etiquetas[$tipo],
            'en_campana'            => $enCampana,
            'tipo_operacion'        => $enCampana ? 'CAMPAÑA' : 'ORDINARIA',
            'motivo'                => $padre
                ? 'Corrección: '.$motivos[mt_rand(0, count($motivos) - 1)].' Se atendió lo observado por el aprobador.'
                : $motivos[mt_rand(0, count($motivos) - 1)],
            'nivel_autonomia_req'   => $nivel,
            'estado_fud'            => $estado,
            'veces_observado'       => $padre ? $padre->veces_observado + 1 : 0,
            'aprobado_por'          => $dec ? $apr->id : null,
            'comentario_aprobador'  => $comentario ?? ($estado === 'Aprobado' && mt_rand(0, 1) ? 'Conforme. Proceder con la ejecución en tienda.' : null),
            'fecha_decision'        => $dec,
            'fecha_envio'           => $envio,
            'created_at'            => $creado,
            'updated_at'            => $dec ? $dec->copy()->setTime(mt_rand(9, 18), mt_rand(0, 59)) : $creado,
        ]);

        match ($tipo) {
            'cuotas' => FudDetalleCuotas::create([
                'propuesta_fud_id'   => $fud->id,
                'cuota_desde'        => $d = mt_rand(2, 14),
                'cuota_hasta'        => $d + mt_rand(0, 2),
                'desc_interes'       => [30, 40, 60, 70][mt_rand(0, 3)],
                'desc_moratorio'     => 100,
                'pone_al_dia'        => (bool) mt_rand(0, 1),
                'cierra_mes_vigente' => true,
            ]),
            'cancelacion' => FudDetalleCancelacion::create([
                'propuesta_fud_id' => $fud->id,
                'desc_capital'     => $dc = [0, 0, 5, 25, 50][mt_rand(0, 4)],
                'monto_a_pagar'    => round($cli->saldo_capital * (1 - $dc / 100), 2),
                'desc_interes'     => [50, 70, 100][mt_rand(0, 2)],
                'fecha_limite'     => $envio->copy()->addDays(30),
                'mayor_tres_uit'   => $cli->esMayor3UIT(),
                'sunarp_positivo'  => false,
            ]),
            default => FudDetalleJudicial::create([
                'propuesta_fud_id' => $fud->id,
                'monto_gravamen'   => $tipo === 'extrajudicial' ? round($cli->saldo_capital * mt_rand(40, 90) / 100, 2) : null,
                'etapa_procesal'   => $tipo === 'extrajudicial'
                    ? ['Pre-judicial', 'Ejecución de garantías', 'Medidas cautelares', 'Etapa de remate'][mt_rand(0, 3)]
                    : 'Pre-judicial',
                'beneficio_pct_sk' => ['extrajudicial' => 70, 'castigada' => [70, 80][mt_rand(0, 1)], 'focmac' => 90][$tipo],
            ]),
        };

        // Evidencia de la negociación adjunta al FUD
        foreach (array_slice(['Evidencia negociación', 'Documento cliente', 'Captura sistema'], 0, mt_rand(1, 2)) as $t) {
            $nombre = ['Evidencia negociación' => 'evidencia_gestion', 'Documento cliente' => 'dni_titular', 'Captura sistema' => 'estado_cuenta'][$t]
                ."_{$cli->operacion}.pdf";
            FudAdjunto::create([
                'propuesta_fud_id' => $fud->id,
                'subido_por'       => $ejec->id,
                'nombre_archivo'   => $nombre,
                'ruta_archivo'     => $this->pdf("fud/{$fud->id}/adjuntos", $nombre, $t, $cli),
                'tipo_adjunto'     => $t,
                'size_bytes'       => mt_rand(80, 900) * 1024,
                'created_at'       => $creado,
            ]);
        }

        if (!$padre) $this->log($ejec, 'FUD enviado', 'En revisión', $fud, $envio);
        return $fud;
    }

    private function aprobar(PropuestaFud $fud, User $apr, ClienteCartera $cli, Carbon $dec): void
    {
        $this->log($apr, 'Decisión FUD', 'Aprobado', $fud, $dec, $fud->comentario_aprobador);
        $cli->increment('veces_conciliado');

        $diasDesde = $dec->diffInDays($this->hoy);
        $estado = $diasDesde < 3
            ? $this->elegirPonderado([[60, 'Pendiente'], [40, 'En revisión']])[1]
            : $this->elegirPonderado([[58, 'Aprobado'], [12, 'En revisión'], [14, 'Observado'], [11, 'Pendiente'], [5, 'Rechazado']])[1];

        $carga    = $estado === 'Pendiente' ? null : $this->despues($dec, 0, 3);
        $revision = in_array($estado, ['Aprobado', 'Observado', 'Rechazado']) ? $this->despues($carga, 0, 2) : null;
        $revisor  = $this->czrZona[$cli->zona_id] ?? $this->jefe;

        $sustento = Sustento::create([
            'propuesta_fud_id'    => $fud->id,
            'cargado_por'         => $fud->enviado_por,
            'revisado_por'        => $revision ? $revisor->id : null,
            'estado'              => $estado,
            'fecha_carga'         => $carga,
            'fecha_revision'      => $revision?->copy()->setTime(mt_rand(9, 18), mt_rand(0, 59)),
            'comentario_revision' => match ($estado) {
                'Observado' => 'Corregir el documento observado y volver a cargarlo.',
                'Rechazado' => 'Los documentos no corresponden a la operación aprobada.',
                'Aprobado'  => mt_rand(0, 1) ? 'Documentación conforme.' : null,
                default     => null,
            },
            'created_at'          => $dec,
            'updated_at'          => $revision ?? $carga ?? $dec,
        ]);

        if (!$carga) return;

        $this->log(User::find($fud->enviado_por), 'Carga de sustentos', 'En revisión', $fud, $carga);
        if ($revision) $this->log($revisor, 'Revisión de sustentos', $estado, $fud, $revision);

        $tipos = [];
        if ($cli->estado_cartera !== 'CASTIGADO') $tipos[] = ['Acuerdo de Pago', 'dc_'];
        $tipos[] = ['Nota de Abono', 'na_'];
        $tipos[] = ['Comprobante de Pago', 'cp_'];
        if ($cli->transaccion !== '300/140') $tipos[] = ['Hoja de Convenio', 'nf_'];
        if ($cli->esMayor3UIT() && $fud->tipo_negociacion === 'cancelacion') $tipos[] = ['Informe-Propuesta SK>3UIT', 'ip_'];

        $observado = $estado === 'Observado' ? mt_rand(0, count($tipos) - 1) : -1;
        foreach ($tipos as $i => [$tipo, $prefijo]) {
            $nombre = $prefijo.$cli->cuenta.'_'.$carga->format('Ymd').'.pdf';
            SustentoDocumento::create([
                'sustento_id'         => $sustento->id,
                'tipo_doc'            => $tipo,
                'nombre_archivo'      => $nombre,
                'ruta_archivo'        => $this->pdf('sustentos', $nombre, $tipo, $cli),
                'size_bytes'          => mt_rand(120, 1500) * 1024,
                'observaciones_carga' => [['t' => 'ok', 'tx' => 'Documento conforme. Firmas, formato y montos verificados.']],
                'estado_validacion'   => 'ok',
                'estado_revision'     => match (true) {
                    $estado === 'Aprobado' => 'aprobado',
                    $i === $observado      => 'observado',
                    $estado === 'Observado'=> 'aprobado',
                    default                => 'pendiente',
                },
                'comentario_revisor'  => $i === $observado
                    ? ['Falta la firma del titular.', 'El monto no coincide con lo aprobado.', 'Documento ilegible, volver a escanear.'][mt_rand(0, 2)]
                    : null,
                'cargado_en'          => $carga->copy()->setTime(mt_rand(9, 18), mt_rand(0, 59)),
            ]);
        }
    }

    // ── Programas de gobierno ────────────────────────────────

    private function crearProgramasGobierno(int $n): void
    {
        $docs = [
            'DNI / Reporte tributario con Ficha RUC', 'Hoja Resumen', 'Formulario Conocimiento del Cliente',
            'DJ del Programa – Anexo 02', 'DJ Patrimonial', 'Cargo del Contrato de Crédito',
            'Cronograma de Pagos', 'Pagaré', 'Clasificación Regulatoria (RCC, EXPERIAN)', 'Evidencia de Ingresos',
        ];
        $tiendas = array_values($this->tiendas);
        $base    = (int) ClienteCartera::max('id') + 5000;

        for ($i = 0; $i < $n; $i++) {
            $tienda = $tiendas[mt_rand(0, count($tiendas) - 1)];
            $saldo  = round(mt_rand(800000, 9000000) / 100, 2);
            $cli = ClienteCartera::create([
                'zona_id'          => $tienda->zona_id,
                'tienda_id'        => $tienda->id,
                'cuenta'           => 300000000 + $base + $i,
                'operacion'        => 400000000 + ($base + $i) * 3,
                'nombre_cliente'   => $this->nombrePersona(),
                'tipo_credito'     => ['PEQUEÑA EMPRESA', 'MICROEMPRESA', 'MEDIANA EMPRESA'][mt_rand(0, 2)],
                'saldo_capital'    => $saldo,
                'dias_atraso'      => $d = mt_rand(90, 700),
                'rango_atraso'     => $this->rango($d),
                'calificacion_rcc' => ['DEFICIENTE', 'DUDOSO', 'PÉRDIDA'][mt_rand(0, 2)],
                'estado_cartera'   => 'VENCIDO',
                'tipo_negociacion_campana' => 'cancelacion',
                'autonomia'        => $saldo > 15450 ? 'Jefe de Recuperaciones' : 'CZR',
                'activo_campana'   => true,
                'es_prog_gobierno' => true,
                'prog_gob_califica'=> true,
                'tipo_documento'   => 'RUC',
                'nro_documento'    => '10'.mt_rand(10000000, 79999999).mt_rand(0, 9),
                'telefono'         => '9'.mt_rand(10000000, 99999999),
            ]);

            $registro = $this->hoy->copy()->subDays((int) floor(90 * pow(mt_rand(0, 1000) / 1000, 1.2)));
            $cargados = mt_rand(1, 100) <= 45 ? 10 : mt_rand(3, 9);
            $czr      = $this->czrZona[$tienda->zona_id] ?? $this->jefe;

            $exp = ExpedienteGobierno::create([
                'cliente_cartera_id'  => $cli->id,
                'registrado_por'      => $czr->id,
                'programa'            => $this->elegirPonderado([[40, 'Reactiva Perú'], [30, 'FAE-MYPE'], [20, 'IMPULSA PERÚ'], [10, 'Otro']])[1],
                'motivo_habilitacion' => mt_rand(0, 1) ? 'Garantía rechazada por Cía. Seguros' : 'Pérdida de eficacia de la garantía',
                'confirmacion_dpto'   => $cargados === 10 || mt_rand(0, 1),
                'responsable'         => $czr->name,
                'fecha_registro'      => $registro,
                'estado'              => $cargados === 10 ? 'Completo' : 'Incompleto',
                'created_at'          => $registro,
                'updated_at'          => $registro,
            ]);

            foreach (array_slice($docs, 0, $cargados) as $tipo) {
                $nombre = \Illuminate\Support\Str::slug($tipo, '_')."_{$cli->operacion}.pdf";
                ExpedienteGobiernoDoc::create([
                    'expediente_id'  => $exp->id,
                    'tipo_doc'       => $tipo,
                    'nombre_archivo' => $nombre,
                    'ruta_archivo'   => $this->pdf('gobierno', $nombre, $tipo, $cli),
                    'cargado_en'     => $registro,
                ]);
            }
        }
    }

    // ── Utilidades ───────────────────────────────────────────

    private function ejecutivoDe(ClienteCartera $cli): User
    {
        $lista = $this->ejecutivosZona[$cli->zona_id] ?? array_merge(...array_values($this->ejecutivosZona));
        // Preferir el ejecutivo de la misma tienda
        foreach ($lista as $u) if ($u->tienda_id === $cli->tienda_id) return $u;
        return $lista[mt_rand(0, count($lista) - 1)];
    }

    private function log(User $user, string $tipo, string $estado, PropuestaFud $fud, Carbon $fecha, ?string $desc = null): void
    {
        $cli = $fud->cliente;
        Seguimiento::create([
            'user_id'          => $user->id,
            'propuesta_fud_id' => $fud->id,
            'tipo_operacion'   => $tipo,
            'cliente'          => $cli->nombre_cliente,
            'operacion'        => (string) $cli->operacion,
            'estado'           => $estado,
            'descripcion'      => $desc,
            'fecha_accion'     => $fecha,
            'created_at'       => $fecha->copy()->setTime(mt_rand(8, 18), mt_rand(0, 59)),
        ]);
    }

    /** Fecha entre $min y $max días después, sin pasar de hoy. */
    private function despues(Carbon $desde, int $min, int $max): Carbon
    {
        $f = $desde->copy()->addDays(mt_rand($min, $max));
        return $f->gt($this->hoy) ? $this->hoy->copy() : $f;
    }

    private function elegirPonderado(array $opciones): array
    {
        $total = array_sum(array_column($opciones, 0));
        $r = mt_rand(1, $total);
        foreach ($opciones as $o) {
            if (($r -= $o[0]) <= 0) return $o;
        }
        return end($opciones);
    }

    private function rango(int $dias): string
    {
        return match (true) {
            $dias <= 30  => '1-30',
            $dias <= 60  => '31-60',
            $dias <= 90  => '61-90',
            $dias <= 180 => '91-180',
            $dias <= 365 => '181-365',
            default      => '>365',
        };
    }

    private function nombrePersona(): string
    {
        $f = $this->faker;
        return mb_strtoupper($f->lastName().' '.$f->lastName().' '.$f->firstName().(mt_rand(0, 1) ? ' '.$f->firstName() : ''));
    }

    /** Guarda un PDF válido de una página que identifica el documento, para que la descarga funcione. */
    private function pdf(string $dir, string $nombre, string $tipo, ClienteCartera $cli): string
    {
        $ruta = $dir.'/'.(++$this->docSeq).'_'.$nombre;
        $esc  = fn($t) => str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'],
            iconv('UTF-8', 'Windows-1252//TRANSLIT', $t));
        $lineas = [
            [18, 'CAJA TRUJILLO - Recuperaciones'],
            [14, $tipo],
            [11, 'Cliente: '.$cli->nombre_cliente],
            [11, 'Cuenta: '.$cli->cuenta.'   Operacion: '.$cli->operacion],
            [11, 'Saldo capital: S/ '.number_format($cli->saldo_capital, 2)],
        ];
        $stream = '';
        $y = 780;
        foreach ($lineas as [$size, $txt]) {
            $stream .= "BT /F1 $size Tf 60 $y Td (".$esc($txt).") Tj ET\n";
            $y -= $size + 14;
        }
        $objs = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 5 0 R >> >> /Contents 4 0 R >>',
            "<< /Length ".strlen($stream)." >>\nstream\n$stream\nendstream",
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>',
        ];
        $pdf = "%PDF-1.4\n";
        $offsets = [];
        foreach ($objs as $i => $o) {
            $offsets[] = strlen($pdf);
            $pdf .= ($i + 1)." 0 obj\n$o\nendobj\n";
        }
        $xref = strlen($pdf);
        $pdf .= "xref\n0 ".(count($objs) + 1)."\n0000000000 65535 f \n";
        foreach ($offsets as $off) $pdf .= sprintf("%010d 00000 n \n", $off);
        $pdf .= "trailer\n<< /Size ".(count($objs) + 1)." /Root 1 0 R >>\nstartxref\n$xref\n%%EOF";

        Storage::disk('private')->put($ruta, $pdf);
        return $ruta;
    }
}
