<?php

namespace App\Servicios;

use App\Models\PlantillaRecordatorio;
use App\Models\Proyecto;

class TextoRecordatorios
{
    public const TIPOS = [
        'recordatorio_72' => 'Recordatorio de 72 horas',
        'recordatorio_24' => 'Recordatorio de 24 horas',
        'recordatorio_manual' => 'Recordatorio manual',
        'revision_pendiente' => 'Revisión pendiente de docentes',
    ];

    public function predeterminado(string $tipo): string
    {
        return match ($tipo) {
            'recordatorio_72' => "Hola, {nombre}.\nTu entrega de {actividad} para el proyecto {proyecto} vence el {fecha_limite}. Revisa lo pendiente con tu equipo.",
            'recordatorio_24' => "Hola, {nombre}.\nTu entrega de {actividad} para el proyecto {proyecto} vence el {fecha_limite}. Atiende lo pendiente cuanto antes.",
            'revision_pendiente' => "Hola, {nombre}.\nLa versión {version} de {actividad} del equipo {equipo}, proyecto {proyecto}, está pendiente de tu revisión.",
            default => "Hola, {nombre}.\nTienes pendiente la entrega de {actividad} para el proyecto {proyecto}. Consulta los requisitos y la fecha límite: {fecha_limite}.",
        };
    }

    public function plantilla(?int $docenteId, string $tipo): string
    {
        return $docenteId ? (PlantillaRecordatorio::query()->where('docente_id', $docenteId)->where('tipo', $tipo)->value('texto') ?? $this->predeterminado($tipo)) : $this->predeterminado($tipo);
    }

    public function docenteId(Proyecto $proyecto): ?int
    {
        return $proyecto->equipo?->grupoAcademico?->lider_proyecto_id;
    }

    public function render(string $plantilla, array $valores): string
    {
        return strtr($plantilla, collect($valores)->mapWithKeys(fn ($valor, $clave) => ['{'.$clave.'}' => (string) $valor])->all());
    }
}
