<?php

namespace App\Http\Controllers\Modulos;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Modulos\Soporte\AutorizaDireccion;
use App\Models\Asignatura;
use App\Models\Carrera;
use App\Soporte\SistemaInterfaz;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ControladorAsignaturas extends Controller
{
    use AutorizaDireccion;

    public function mostrar(Request $request): View
    {
        $usuario = $request->user()->loadMissing('role');
        $rol = $usuario->role?->nombre ?? 'estudiante';

        return view('modulos.control-academico.asignaturas', [
            'active' => 'asignaturas',
            'navegacion' => SistemaInterfaz::navegacionPara($rol),
            'pagina' => SistemaInterfaz::pagina('asignaturas'),
            'roleName' => $usuario->role?->nombre_visible ?? 'Estudiante / Equipo',
            ...$this->datos(),
        ]);
    }

    public function guardar(Request $request): RedirectResponse
    {
        $this->autorizarDireccion($request);

        $datos = $request->validate([
            'carrera_id' => ['required', 'exists:carreras,id'],
            'nombre' => ['required', 'string', 'max:255'],
            'clave' => ['nullable', 'string', 'max:50', 'unique:asignaturas,clave'],
            'grado' => ['nullable', 'integer', 'min:1', 'max:12'],
        ]);

        Asignatura::query()->create([...$datos, 'estado' => 'activo']);

        return redirect()->route('modulos.show', 'asignaturas')->with('estado', 'Asignatura guardada correctamente.');
    }

    /**
     * @return array<string, mixed>
     */
    private function datos(): array
    {
        $asignaturas = Asignatura::query()->with('carrera:id,nombre,clave')->orderBy('nombre')->get();

        return [
            'asignaturas' => $asignaturas,
            'carreras' => Carrera::query()->orderBy('nombre')->get(['id', 'nombre', 'clave']),
            'metricasAsignaturas' => [
                ['label' => 'Asignaturas', 'value' => (string) $asignaturas->count()],
                ['label' => 'Carreras vinculadas', 'value' => (string) $asignaturas->pluck('carrera_id')->filter()->unique()->count()],
                ['label' => 'Activas', 'value' => (string) $asignaturas->where('estado', 'activo')->count()],
            ],
        ];
    }
}
