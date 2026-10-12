<?php
namespace App\Services\Honramiento;

use App\Models\{ExpedienteGobierno, ExpedienteGobiernoDoc, ExpedienteGobiernoRevision, Gestion, GobTipoDocumento,
    ImportacionHonramiento, Lote, Seguimiento, Tienda, User, Zona};
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Importa el Excel de revisión de expedientes de honramiento.
 * 1) analizar(): lee, valida y compara cada fila (vista previa, no guarda nada).
 * 2) importar(): crea o actualiza por ID las filas sin error.
 * ESTADO y N° FALTANTES del Excel no se copian: el sistema los calcula y reporta diferencias.
 */
class ImportadorExcel
{
    public function __construct(private Checklist $checklist) {}

    public function analizar(string $ruta): array
    {
        $hoja = IOFactory::load($ruta)->getActiveSheet();
        $filas = $hoja->toArray(null, true, false, false);   // valores calculados, sin formato

        [$filaEnc, $mapa, $errorFormato] = $this->encabezados($filas);
        if ($errorFormato) return ['error' => $errorFormato, 'filas' => []];

        $zonas = Zona::all()->keyBy(fn($z) => FormatoExcel::clave($z->nombre));
        $tiendas = Tienda::all();
        $gestiones = Gestion::pluck('id', 'nombre')->mapWithKeys(fn($id, $n) => [FormatoExcel::clave($n) => $id]);
        $lotes = Lote::pluck('id', 'codigo')->mapWithKeys(fn($id, $c) => [FormatoExcel::clave($c) => $id]);
        $existentes = ExpedienteGobierno::whereNotNull('id_externo')->pluck('id', 'id_externo');

        $resultado = [];
        $vistos = [];
        foreach (array_slice($filas, $filaEnc + 1, null, true) as $i => $f) {
            if (!array_filter($f, fn($v) => $v !== null && trim((string) $v) !== '')) continue;   // fila vacía
            $r = $this->leerFila($f, $mapa, $i + 1);
            $this->validar($r, $vistos, $zonas, $tiendas, $gestiones, $lotes, $existentes);
            $resultado[] = $r;
        }

        return ['error' => null, 'filas' => $resultado];
    }

    private function encabezados(array $filas): array
    {
        foreach (array_slice($filas, 0, 15, true) as $n => $f) {
            $claves = array_map(fn($v) => FormatoExcel::clave($v), $f);
            if (!in_array('ID', $claves) || !in_array('DNI', $claves) || !in_array('ESTADO', $claves)) continue;

            $mapa = [];
            foreach (FormatoExcel::ANTES as $enc) {
                $col = array_search(FormatoExcel::clave($enc), $claves);
                if ($col === false) return [$n, [], "Falta la columna «{$enc}» en el archivo."];
                $mapa[$enc] = $col;
            }
            $colFalt = collect($claves)->search(fn($c) => str_contains($c, 'DOCUMENTOS FALTANTES'));
            $colObs = array_search('OBSERVACIONES', $claves);
            if ($colFalt === false) return [$n, [], 'Falta la columna «N° DOCUMENTOS FALTANTES».'];

            // Los 10 documentos están entre ESTADO y N° DOCUMENTOS FALTANTES
            $docs = range($mapa['ESTADO'] + 1, $colFalt - 1);
            if (count($docs) !== 10) {
                return [$n, [], 'Se esperaban 10 columnas de documentos entre ESTADO y N° DOCUMENTOS FALTANTES; se encontraron '.count($docs).'.'];
            }
            $mapa['DOCS'] = array_combine(range(1, 10), $docs);
            $mapa['FALTANTES'] = $colFalt;
            $mapa['OBSERVACIONES'] = $colObs === false ? null : $colObs;
            return [$n, $mapa, null];
        }
        return [0, [], 'No se encontró la fila de encabezados (GESTIÓN, ID, DNI, ESTADO…). Use la plantilla del sistema.'];
    }

    private function leerFila(array $f, array $m, int $numero): array
    {
        $v = fn($k) => $f[$m[$k]] ?? null;
        $docs = [];
        foreach ($m['DOCS'] as $n => $col) {
            $x = trim((string) ($f[$col] ?? ''));
            $docs[$n] = $x === '' ? 0 : (in_array($x, ['1', '0', '1.0', '0.0'], true) ? (int) $x : $x);
        }
        // Observaciones: la columna y cualquier celda escrita después de ella
        $obs = $m['OBSERVACIONES'] !== null
            ? collect(array_slice($f, $m['OBSERVACIONES']))->map(fn($x) => trim((string) $x))->filter()->implode(' | ')
            : '';

        $dni = FormatoExcel::texto($v('DNI'));
        // Excel quita el cero inicial de los DNI que empiezan con 0 (quedan con 7 dígitos)
        if (ctype_digit($dni) && strlen($dni) === 7) $dni = '0'.$dni;

        return [
            'fila'            => $numero,
            'gestion'         => trim((string) $v('GESTIÓN')),
            'fecha_central'   => FormatoExcel::fecha($v('FECHA DE REVISIÓN ACTUAL'))?->toDateString(),
            'fecha_zona'      => FormatoExcel::fecha($v('FECHA DE REVISIÓN POR ZONAS'))?->toDateString(),
            'id_externo'      => FormatoExcel::texto($v('ID')),
            'dni'             => $dni,
            'tipo_persona'    => Checklist::tipoPersona($dni),
            'credito_sicmact' => FormatoExcel::texto($v('CRÉDITO SICMACT')),
            'cuenta_bt'       => FormatoExcel::texto($v('CUENTA BT')),
            'operacion'       => FormatoExcel::texto($v('OPERACIÓN')),
            'zona'            => trim((string) $v('ZONA')),
            'tienda'          => trim((string) $v('TIENDA')),
            'cliente'         => trim((string) $v('CLIENTE')),
            'monto_texto'     => (string) $v('MONTO HONRADO'),
            'monto_honrado'   => FormatoExcel::monto($v('MONTO HONRADO')),
            'lote'            => trim((string) $v('LOTE')),
            'estado_excel'    => FormatoExcel::estadoExcel($v('ESTADO')),
            'estado_texto'    => trim((string) $v('ESTADO')),
            'docs'            => $docs,
            'faltantes_excel' => is_numeric($f[$m['FALTANTES']] ?? null) ? (int) $f[$m['FALTANTES']] : null,
            'observaciones'   => $obs,
            'errores'         => [],
            'diferencias'     => [],   // estado o faltantes del Excel distintos a lo calculado
            'avisos'          => [],   // informativos: catálogos nuevos, estado no reconocido
        ];
    }

    private function validar(array &$r, array &$vistos, $zonas, $tiendas, $gestiones, $lotes, $existentes): void
    {
        if ($r['id_externo'] === '') $r['errores'][] = 'Falta el ID.';
        elseif (isset($vistos[$r['id_externo']])) $r['errores'][] = "ID repetido en el archivo (también en la fila {$vistos[$r['id_externo']]}).";
        else $vistos[$r['id_externo']] = $r['fila'];

        if (!preg_match('/^\d{8}$|^\d{11}$/', $r['dni'])) $r['errores'][] = "DNI/RUC inválido: «{$r['dni']}» (8 u 11 dígitos).";
        if ($r['cliente'] === '') $r['errores'][] = 'Falta el nombre del cliente.';
        if ($r['zona'] === '' || $r['tienda'] === '') $r['errores'][] = 'Falta la zona o la tienda.';
        if ($r['monto_honrado'] === null) $r['errores'][] = "Monto honrado ilegible: «{$r['monto_texto']}».";
        foreach ($r['docs'] as $n => $x) {
            if (!in_array($x, [0, 1], true)) $r['errores'][] = 'Documento '.$n.' ('.FormatoExcel::DOCUMENTOS[$n]."): valor «{$x}», se esperaba 1 o 0.";
        }
        if ($r['estado_excel'] === 'desconocido') $r['avisos'][] = "Estado «{$r['estado_texto']}» no reconocido; se usará el calculado.";

        // Catálogos que se crearán al importar
        if ($r['gestion'] !== '' && !isset($gestiones[FormatoExcel::clave($r['gestion'])])) $r['avisos'][] = "Gestión nueva: «{$r['gestion']}».";
        if ($r['lote'] !== '' && !isset($lotes[FormatoExcel::clave($r['lote'])])) $r['avisos'][] = "Lote nuevo «{$r['lote']}»: registre su fecha de conformidad para controlar el plazo de 30 días.";
        $zona = $zonas[FormatoExcel::clave($r['zona'])] ?? null;
        if ($r['zona'] !== '' && !$zona) $r['avisos'][] = "Zona nueva: «{$r['zona']}».";
        if ($r['tienda'] !== '' && !$this->buscarTienda($tiendas, $r['tienda'], $zona?->id)) $r['avisos'][] = "Tienda nueva: «{$r['tienda']}».";

        $r['accion'] = $r['errores'] ? 'error' : (isset($existentes[$r['id_externo']]) ? 'actualizar' : 'crear');
        if ($r['errores']) return;

        // Estado documental que calcula el sistema con el checklist de la fila
        [$estado, $faltantes] = $this->simular($r);
        $r['estado_calculado'] = $estado;
        $r['faltantes_calculado'] = $faltantes;
        if ($r['estado_excel'] && $r['estado_excel'] !== 'desconocido' && $r['estado_excel'] !== $estado) {
            $r['diferencias'][] = "El Excel indica «{$r['estado_texto']}» pero según los documentos exigidos por COFIDE corresponde «{$estado}».";
        }
        if ($r['faltantes_excel'] !== null && $r['faltantes_excel'] !== $faltantes) {
            $r['diferencias'][] = "El Excel indica {$r['faltantes_excel']} faltante(s); el sistema calcula {$faltantes}.";
        }
    }

    /** Estado y faltantes (sin la capa de revisión) a partir de los 1/0 de la fila. */
    private function simular(array $r): array
    {
        $e = new ExpedienteGobierno(['tipo_persona' => $r['tipo_persona']]);
        $docs = collect();
        foreach ($this->tiposPorColumna($r) as $col => $tipos) {
            foreach ($tipos as $t) {
                if ($r['docs'][$col] === 1) $docs->push(new ExpedienteGobiernoDoc(['tipo_documento_id' => $t->id, 'estado' => 'cargado']));
            }
        }
        $definitivo = $r['estado_excel'] === 'Incompleto definitivo';
        if ($definitivo) {
            $presentes = $docs->pluck('tipo_documento_id')->all();
            foreach (GobTipoDocumento::catalogo() as $t) {
                if ($t->exigidoPara($r['tipo_persona']) && !in_array($t->id, $presentes)) {
                    $docs->push(new ExpedienteGobiernoDoc(['tipo_documento_id' => $t->id, 'estado' => 'no_disponible']));
                }
            }
        }
        $e->setRelation('documentos', $docs);
        $c = $this->checklist->calcular($e);
        return [$c['estado'], $c['faltantes']];
    }

    /** Tipos de documento que corresponden a cada columna del Excel para el tipo de persona. */
    private function tiposPorColumna(array $r): array
    {
        $out = [];
        foreach (GobTipoDocumento::catalogo() as $t) {
            $aplica = $t->exigidoPara($r['tipo_persona']) || (!$t->cofide_natural && !$t->cofide_juridica);
            if ($aplica) $out[$t->columna_excel][] = $t;
        }
        return $out;
    }

    private function buscarTienda($tiendas, string $nombre, ?int $zonaId): ?Tienda
    {
        $clave = FormatoExcel::clave(preg_replace('/^(TIENDA|AGENCIA|OFICINA)\s+/i', '', trim($nombre)));
        $coincide = fn($t) => in_array(FormatoExcel::clave($t->nombre), [$clave, FormatoExcel::clave($nombre)])
            || FormatoExcel::clave(preg_replace('/^(TIENDA|AGENCIA|OFICINA)\s+/i', '', $t->nombre)) === $clave;
        return $tiendas->first(fn($t) => $coincide($t) && (!$zonaId || $t->zona_id === $zonaId))
            ?? $tiendas->first($coincide);
    }

    // ── Importación ──────────────────────────────────────────

    public function importar(array $filas, User $usuario, string $archivo): ImportacionHonramiento
    {
        $creados = $actualizados = $diferencias = $errores = 0;

        DB::transaction(function () use ($filas, $usuario, &$creados, &$actualizados, &$diferencias, &$errores) {
            foreach ($filas as $r) {
                if ($r['accion'] === 'error') { $errores++; continue; }
                $nuevo = $this->guardarFila($r, $usuario);
                $nuevo ? $creados++ : $actualizados++;
                if (!empty($r['diferencias'])) $diferencias++;
            }
        });

        $imp = ImportacionHonramiento::create([
            'user_id' => $usuario->id, 'archivo' => $archivo, 'filas' => count($filas),
            'creados' => $creados, 'actualizados' => $actualizados, 'con_diferencias' => $diferencias, 'con_error' => $errores,
        ]);
        Seguimiento::registrar($usuario->id, 'Importación de expedientes de honramiento', 'Completada', null, null, null,
            "{$archivo}: {$creados} creados, {$actualizados} actualizados, {$errores} con error.");

        return $imp;
    }

    private function guardarFila(array $r, User $usuario): bool
    {
        $zona = $this->zona($r['zona']);
        $tienda = $this->tienda($r['tienda'], $zona);
        $gestion = $r['gestion'] !== '' ? Gestion::firstOrCreate(['nombre' => Str::upper(trim($r['gestion']))]) : null;
        $lote = $r['lote'] !== '' ? Lote::firstOrCreate(['codigo' => trim($r['lote'])]) : null;

        $e = ExpedienteGobierno::firstOrNew(['id_externo' => $r['id_externo']]);
        $nuevo = !$e->exists;
        $e->fill([
            'gestion_id' => $gestion?->id, 'dni' => $r['dni'], 'tipo_persona' => $r['tipo_persona'],
            'credito_sicmact' => $r['credito_sicmact'] ?: null, 'cuenta_bt' => $r['cuenta_bt'] ?: null, 'operacion' => $r['operacion'] ?: null,
            'cliente' => $r['cliente'], 'zona_id' => $zona->id, 'tienda_id' => $tienda->id, 'lote_id' => $lote?->id,
            'monto_honrado' => $r['monto_honrado'], 'programa' => $lote?->programa,
        ]);
        if ($nuevo) {
            $e->registrado_por = $usuario->id;
            $e->fecha_registro = now()->toDateString();
            $e->estado = 'Pendiente';
        }
        // Las observaciones del Excel se conservan como comentario solo si aportan algo más que la lista de documentos
        if ($r['observaciones'] !== '' && !$e->observaciones && !self::esListaDeDocumentos($r['observaciones'])) {
            $e->observaciones = $r['observaciones'];
        }
        $e->save();

        $this->sincronizarDocumentos($e, $r, $usuario);
        $this->registrarRevisiones($e, $r, $usuario);
        $e->recalcularEstado();

        return $nuevo;
    }

    private function sincronizarDocumentos(ExpedienteGobierno $e, array $r, User $usuario): void
    {
        $vigentes = $e->documentos()->get()->keyBy('tipo_documento_id');
        $definitivo = $r['estado_excel'] === 'Incompleto definitivo';

        foreach ($this->tiposPorColumna($r) as $col => $tipos) {
            foreach ($tipos as $t) {
                $doc = $vigentes->get($t->id);
                if ($r['docs'][$col] === 1) {
                    if (!$doc || $doc->estado === 'no_disponible') {
                        $doc?->update(['vigente' => false]);
                        ExpedienteGobiernoDoc::create([
                            'expediente_id' => $e->id, 'tipo_documento_id' => $t->id, 'tipo_doc' => $t->nombre,
                            'estado' => 'cargado', 'origen' => 'sftp', 'cargado_por' => $usuario->id, 'cargado_en' => now(),
                        ]);
                    }
                } elseif ($doc && $doc->origen !== 'rct' && $doc->estado !== 'no_disponible') {
                    // El Excel indica 0: se retira lo registrado desde Excel/SFTP (los archivos cargados en RCT se conservan)
                    $doc->update(['vigente' => false]);
                    $doc = null;
                }

                $falta = !$doc && $r['docs'][$col] !== 1;
                if ($definitivo && $falta && $t->exigidoPara($e->tipo_persona)) {
                    ExpedienteGobiernoDoc::create([
                        'expediente_id' => $e->id, 'tipo_documento_id' => $t->id, 'tipo_doc' => $t->nombre,
                        'estado' => 'no_disponible', 'origen' => 'excel', 'cargado_por' => $usuario->id, 'cargado_en' => now(),
                        'motivo_no_disponible' => 'Declarado no disponible por la tienda (Excel de revisión).',
                    ]);
                }
            }
        }
    }

    private function registrarRevisiones(ExpedienteGobierno $e, array $r, User $usuario): void
    {
        $conforme = in_array($r['estado_excel'], ['Completo', null], true);
        foreach (['zona' => $r['fecha_zona'], 'central' => $r['fecha_central']] as $nivel => $fecha) {
            if (!$fecha) continue;
            $ya = $e->revisiones()->where('nivel', $nivel)->where('origen', 'excel')->whereDate('revisado_en', $fecha)->exists();
            if ($ya) continue;
            ExpedienteGobiernoRevision::create([
                'expediente_id' => $e->id, 'nivel' => $nivel, 'resultado' => $conforme ? 'conforme' : 'observado',
                'comentario' => 'Revisión registrada en el Excel de seguimiento.'.($r['estado_texto'] ? " Estado: {$r['estado_texto']}." : ''),
                'user_id' => $usuario->id, 'origen' => 'excel', 'revisado_en' => $fecha.' 12:00:00',
            ]);
            if ($conforme) $e->forceFill([$nivel === 'zona' ? 'revisado_zona_en' : 'revisado_central_en' => $fecha.' 12:00:00']);
        }
        $e->saveQuietly();
    }

    /** True si el texto solo enumera documentos (p. ej. "DJ Patrimonial | Evidencia"), que RCT ya calcula. */
    public static function esListaDeDocumentos(string $texto): bool
    {
        $claves = ['DNI', 'REPORTE', 'FICHA', 'RUC', 'HOJA', 'RESUMEN', 'FORMULARIO', 'CONOCIMIENTO', 'SPLAFT', 'DJ', 'PATRIMONIAL',
                   'PROGRAMA', 'ANEXO', 'CARGO', 'CONTRATO', 'CRONOGRAMA', 'PAGARE', 'RCC', 'EXPERIAN', 'CLASIFICACION', 'EVIDENCIA', 'INGRESOS'];
        foreach (preg_split('/[|,;\/]+/', $texto) as $parte) {
            $palabras = array_filter(explode(' ', FormatoExcel::clave($parte)));
            if (!$palabras) continue;
            if (!array_intersect($palabras, $claves)) return false;
        }
        return true;
    }

    private function zona(string $nombre): Zona
    {
        $clave = FormatoExcel::clave($nombre);
        return Zona::all()->first(fn($z) => FormatoExcel::clave($z->nombre) === $clave)
            ?? Zona::create(['nombre' => mb_convert_case(mb_strtolower(trim($nombre)), MB_CASE_TITLE)]);
    }

    private function tienda(string $nombre, Zona $zona): Tienda
    {
        return $this->buscarTienda(Tienda::all(), $nombre, $zona->id)
            ?? Tienda::create(['zona_id' => $zona->id,
                'nombre' => mb_convert_case(mb_strtolower(preg_replace('/^(TIENDA|AGENCIA|OFICINA)\s+/i', '', trim($nombre))), MB_CASE_TITLE)]);
    }
}
