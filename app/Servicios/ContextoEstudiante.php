<?php

namespace App\Servicios;

use App\Models\Equipo;
use App\Models\Proyecto;
use Illuminate\Http\Request;

class ContextoEstudiante
{
    public function resolver(Request $request): array
    {
        $estudiante = $request->user()->loadMissing('role');
        abort_unless($estudiante->hasRole('estudiante'), 403);
        $seleccion = $request->input('proyecto_contexto');
        if ($seleccion !== null && $seleccion !== '') {
            $id = is_scalar($seleccion) ? filter_var($seleccion, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) : false;
            abort_unless($id, 403);
            $proyecto = $this->proyectos($estudiante->id)->whereKey($id)->first();
            abort_unless($proyecto, 403);

            return [$estudiante, $proyecto->equipo, $proyecto];
        }
        $equipo = Equipo::where('estado', 'activo')->whereHas('integrantes', fn ($q) => $q->where('usuarios.id', $estudiante->id))->latest('id')->first();

        return [$estudiante, $equipo, $equipo?->proyectos()->with('guiaIntegradora')->latest('id')->first()];
    }

    public function proyectos(int $estudianteId)
    {
        return Proyecto::query()->whereHas('equipo', fn ($q) => $q->where('estado', 'activo')
            ->whereHas('integrantes', fn ($q) => $q->where('usuarios.id', $estudianteId)));
    }
}
