<?php

namespace App\Soporte;

use App\Models\Proyecto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class RegresoDocumentos
{
    /** Solo rutas conocidas: nunca se acepta una URL de regreso enviada por el navegador. */
    public static function destino(Request $request, Proyecto $proyecto): array
    {
        $rol = $request->user()->role->nombre;
        $rutas = [
            'docente-lider.estado-guias' => ['docente_lider', 'Volver al estado de las guías'],
            'docente-lider.cierres.mostrar' => ['docente_lider', 'Volver al cierre y prórrogas'],
            'docente-lider.codigo' => ['docente_lider', 'Volver a revisión de código'],
            'docente-materia.revisiones' => [['docente_materia', 'docente_lider'], 'Volver a revisiones'],
            'modulos.proyectos' => [['coordinacion', 'docente_lider'], 'Volver a proyectos'],
            'estudiante.proyecto' => ['estudiante', 'Volver a mi proyecto'],
        ];
        $origen = $request->query('origen');
        if (! is_string($origen) || ! isset($rutas[$origen]) || ! in_array($rol, (array) $rutas[$origen][0], true)) {
            $origen = match ($rol) {
                'docente_lider' => 'docente-lider.estado-guias',
                'docente_materia' => 'docente-materia.revisiones',
                'estudiante' => 'estudiante.proyecto',
                default => 'modulos.proyectos',
            };
        }
        $contexto = $request->query('contexto', []);
        $contexto = is_array($contexto) ? $contexto : [];
        $reglas = match ($origen) {
            'docente-lider.estado-guias' => ['periodo' => 'nullable|integer|min:1', 'grupo' => 'nullable|integer|min:1',
                'estado' => ['nullable', Rule::in(array_keys(\App\Servicios\EstadoGuias::ESTADOS))], 'buscar' => 'nullable|string|max:160',
                'page' => 'nullable|integer|between:1,1000000', 'por_pagina' => ['nullable', Rule::in([10, 20, 40])]],
            'modulos.proyectos' => ['periodo_proyectos' => 'nullable|integer|min:1', 'grupo_proyectos' => 'nullable|integer|min:1'],
            default => ['page' => 'nullable|integer|between:1,1000000'],
        };
        $validador = Validator::make($contexto, $reglas);
        $parametros = $validador->fails() ? [] : $validador->validated();
        if ($origen === 'docente-lider.cierres.mostrar') {
            $parametros = ['proyecto' => $proyecto->id];
        }
        if ($origen === 'estudiante.proyecto') {
            $parametros = ['proyecto_contexto' => $proyecto->id];
        }

        return ['url' => route($origen, $parametros), 'etiqueta' => $rutas[$origen][1]];
    }
}
