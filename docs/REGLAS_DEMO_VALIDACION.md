# Reglas internas — Validación de documentos en modo DEMO

> **Documento interno del equipo de desarrollo. No se muestra en la interfaz.**
> RCT es por ahora un mockup funcional. La validación "con IA" de documentos
> (firmas, mismas firmas, formato, montos) se **simula** según el nombre del archivo,
> para que en la presentación se vean los distintos casos de error de forma controlada.

---

## 1. Por qué se simula

| Validación | Dificultad real | En la demo |
| --- | --- | --- |
| Formato / plantilla oficial | Media: extraer texto y comparar estructura | Simulada |
| Montos, DNI, N° de crédito coinciden con el expediente | Media: OCR o IA con visión + comparación | Simulada |
| Hay firma en el documento | Media: IA con visión lo detecta razonablemente | Simulada |
| La firma es la misma en todos los documentos | **Alta**: requiere firmas de referencia y modelos especializados; una IA general solo da una opinión, no una verificación confiable | Simulada |
| Búsqueda registral vigente (≤ 6 meses) | Baja: leer la fecha | Simulada |

Cuando el sistema pase a producción, el modo demo se apaga y se conecta un validador real
(ver sección 5). El modo demo **nunca** debe quedar activo en producción.

---

## 2. Activación

En `.env`:

```
RCT_VALIDACION_MODO=demo     # demo | real | off
```

En `config/rct.php`:

```php
'validacion' => [
    'modo' => env('RCT_VALIDACION_MODO', 'demo'),
    'demora_min_ms' => 1500,   // simula "Analizando con IA..."
    'demora_max_ms' => 3000,
],
```

---

## 3. Reglas por nombre de archivo

El validador demo busca **marcadores** en el nombre del archivo (sin importar mayúsculas).
Si no hay ninguno, el resultado es **Conforme**. Si hay varios, se muestran **todos** los errores.

| Marcador en el nombre | Resultado | Mensaje que ve el usuario |
| --- | --- | --- |
| *(ninguno)* | Conforme | Documento conforme. Firmas, formato y montos verificados. |
| `_sf` | Observado | Falta la firma del titular. |
| `_sfg` | Observado | Falta la firma del garante / participante. |
| `_fd` | Observado | La firma no coincide con la registrada en otros documentos del expediente. |
| `_fmt` | Observado | El documento no corresponde a la plantilla oficial vigente. |
| `_mto` | Observado | El monto del documento (S/ X) no coincide con el monto aprobado (S/ Y). |
| `_dni` | Observado | El DNI del documento no coincide con el titular del crédito. |
| `_cred` | Observado | El número de crédito del documento no coincide con el expediente. |
| `_vnc` | Observado | La búsqueda registral tiene más de 6 meses de antigüedad. |
| `_ileg` | Rechazado | Documento ilegible. Vuelva a escanear con mejor resolución. |
| `_tipo` | Observado | El tipo de documento no corresponde al esperado (se esperaba: Acuerdo de Pago). |

Ejemplos:

- `acuerdo_pago_12345.pdf` → Conforme
- `acuerdo_pago_12345_sf.pdf` → Falta firma del titular
- `nota_abono_12345_mto_fd.pdf` → Monto no coincide + firma no coincide

### Reglas de los mensajes

- En `_mto`, el monto "aprobado" (Y) se toma del expediente real, y el monto "del documento" (X)
  se calcula como Y ± un valor fijo (por ejemplo, Y − 150.00), para que el error se vea creíble.
- En `_tipo`, el tipo esperado se toma del espacio de carga donde se subió el archivo.
- Además de los marcadores, el validador demo hace **una verificación real**: si el mismo archivo
  (mismo hash) ya existe en el expediente → "Documento duplicado, ya fue cargado el DD/MM/AAAA".

---

## 4. Lo que NO debe pasar

- Los marcadores **no** aparecen en ninguna parte de la interfaz, ayudas, tooltips ni mensajes.
- La interfaz muestra el resultado como "Validación automática". La palabra "demo" no aparece en pantalla.
- Los marcadores sí quedan documentados aquí y en el código (comentario en la clase).
- El resultado es **determinista**: el mismo nombre de archivo siempre da el mismo resultado.
  Nada de aleatorio, para que la demo sea repetible.
- Un documento Observado o Rechazado **no se pierde**: queda guardado con su estado y
  un revisor puede marcarlo como "Conforme manual" con un comentario (esto sí es real).

---

## 5. Diseño técnico (para poder cambiarlo por el real después)

```
app/Contracts/ValidadorDocumentos.php          // interfaz
app/Services/Validacion/ValidadorDemo.php      // reglas de este documento
app/Services/Validacion/ValidadorReal.php      // futuro: OCR / IA con visión
app/Services/Validacion/ResultadoValidacion.php // estado + lista de hallazgos
```

- `AppServiceProvider` enlaza la interfaz con `ValidadorDemo` o `ValidadorReal` según `config('rct.validacion.modo')`.
- Cada hallazgo tiene: `codigo` (FIRMA_FALTANTE, FIRMA_DIFERENTE, FORMATO, MONTO, DNI, CREDITO, VIGENCIA, ILEGIBLE, TIPO, DUPLICADO), `severidad` (observado / rechazado) y `mensaje`.
- El resultado se guarda en la tabla de documentos (`estado_validacion`, `hallazgos` JSON, `validado_en`, `modo_validacion`).
- La columna `modo_validacion` registra internamente si el resultado vino del modo demo o del real, para que nunca se confundan los datos de prueba con los reales.
- Los mismos códigos de hallazgo los usará el validador real, así la interfaz no cambia.

---

## 6. Aplica también a programas de gobierno

Los documentos de expedientes de honramiento usan el mismo validador y los mismos marcadores.
Adicional para programas:

| Marcador | Mensaje |
| --- | --- |
| `_sld` | El saldo del documento no coincide con el saldo del crédito a la fecha de solicitud. |
| `_inc` | Expediente incompleto: falta(n) documento(s) del checklist del programa. |
