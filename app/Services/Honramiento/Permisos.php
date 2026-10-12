<?php
namespace App\Services\Honramiento;

use App\Models\{ExpedienteGobierno, User};

/**
 * Quién puede hacer qué en los expedientes de honramiento.
 * Tienda (ejecutivos): ve y carga los de su tienda.
 * Coordinador Zonal (nivel 1): ve y revisa los de su zona (revisión por zona).
 * Jefatura / Gerencia (nivel ≥ 2): ven todo; revisión central y, si la zona no tiene coordinador, también la de zona.
 */
class Permisos
{
    public function puedeVer(User $u, ExpedienteGobierno $e): bool
    {
        if ($u->esGrupoFud()) return $u->tienda_id === $e->tienda_id;
        if ($u->nivel() === 1) return $u->tienda?->zona_id === $e->zona_id;
        return true;
    }

    public function puedeCargar(User $u, ExpedienteGobierno $e): bool
    {
        return $this->puedeVer($u, $e) && $e->estado !== 'Conforme';
    }

    public function puedeRevisarZona(User $u, ExpedienteGobierno $e): bool
    {
        if (!$u->esAprobador()) return false;
        return $u->nivel() >= 2 || $u->tienda?->zona_id === $e->zona_id;
    }

    public function puedeRevisarCentral(User $u): bool
    {
        return $u->esAprobador() && $u->nivel() >= 2;
    }

    /** Nivel de revisión que le corresponde al usuario sobre el expediente (o null). */
    public function nivelRevision(User $u, ExpedienteGobierno $e): ?string
    {
        // Observado: permite registrar la observación en el historial (la conformidad exige corregir antes)
        if ($this->puedeRevisarCentral($u) && in_array($e->estado, ['Completo', 'Revisado por zona', 'Observado'])) {
            return $e->estado === 'Revisado por zona' || $e->revisado_zona_en || !$this->zonaTieneCoordinador($e) ? 'central' : 'zona';
        }
        if ($this->puedeRevisarZona($u, $e) && in_array($e->estado, ['Completo', 'Observado'])) return 'zona';
        return null;
    }

    public function zonaTieneCoordinador(ExpedienteGobierno $e): bool
    {
        return User::whereHas('rol', fn($q) => $q->where('nombre', 'Coordinador Zonal de Recuperaciones'))
            ->whereHas('tienda', fn($q) => $q->where('zona_id', $e->zona_id))->exists();
    }
}
