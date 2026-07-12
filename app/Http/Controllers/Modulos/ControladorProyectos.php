<?php

namespace App\Http\Controllers\Modulos;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Modulos\Soporte\AutorizaDireccion;
use App\Models\ApartadoGuia;
use App\Models\Asignatura;
use App\Models\Equipo;
use App\Models\GuiaIntegradora;
use App\Models\Proyecto;
use App\Models\User;
use App\Soporte\SistemaInterfaz;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ControladorProyectos extends Controller
{
    use AutorizaDireccion;

    public function mostrar(Request $request): View
    {
        $usuario = $request->user()->loadMissing('role');
        $rol = $usuario->role?->nombre ?? 'estudiante';

        return view('modulos.gestion-proyectos.proyectos', [
            'active' => 'proyectos',
            'navegacion' => SistemaInterfaz::navegacionPara($rol),
            'pagina' => SistemaInterfaz::pagina('proyectos'),
            'roleName' => $usuario->role?->nombre_visible ?? 'Estudiante / Equipo',
            ...$this->datos(),
        ]);
    }

    public function guardar(Request $request): RedirectResponse
    {
        $this->autorizarDireccion($request);

        $datos = $request->validate([
            'guia_integradora_id' => ['required', 'exists:guias_integradoras,id'],
            'equipo_id' => ['required', 'exists:equipos,id'],
            'titulo' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string'],
        ]);

        Proyecto::query()->updateOrCreate(
            ['guia_integradora_id' => $datos['guia_integradora_id'], 'equipo_id' => $datos['equipo_id']],
            ['titulo' => $datos['titulo'], 'descripcion' => $datos['descripcion'] ?? null, 'estado' => 'en_proceso'],
        );

        return redirect()->route('modulos.show', 'proyectos')->with('estado', 'Proyecto guardado correctamente.');
    }

    public function asignarDocente(Request $request): RedirectResponse
    {
        $this->autorizarDireccion($request);

        $datos = $request->validate([
            'proyecto_id' => ['required', 'exists:proyectos,id'],
            'docente_id' => ['required', 'exists:usuarios,id'],
            'tipo_participacion' => ['required', 'string', 'max:50'],
        ]);

        $proyecto = Proyecto::query()->findOrFail($datos['proyecto_id']);
        $proyecto->docentes()->syncWithoutDetaching([
            $datos['docente_id'] => [
                'tipo_participacion' => $datos['tipo_participacion'],
                'activo' => true,
                'creado_en' => now(),
                'actualizado_en' => now(),
            ],
        ]);

        return redirect()->route('modulos.show', 'proyectos')->with('estado', 'Docente asignado al proyecto correctamente.');
    }

    public function quitarDocente(Request $request): RedirectResponse
    {
        $this->autorizarDireccion($request);

        $datos = $request->validate([
            'proyecto_id' => ['required', 'exists:proyectos,id'],
            'docente_id' => ['required', 'exists:usuarios,id'],
        ]);

        Proyecto::query()->findOrFail($datos['proyecto_id'])
            ->docentes()
            ->updateExistingPivot($datos['docente_id'], [
                'activo' => false,
                'actualizado_en' => now(),
            ]);

        return redirect()->route('modulos.show', 'proyectos')->with('estado', 'Docente retirado del proyecto correctamente.');
    }

    public function asignarAsignatura(Request $request): RedirectResponse
    {
        $this->autorizarDireccion($request);

        $datos = $request->validate([
            'proyecto_id' => ['required', 'exists:proyectos,id'],
            'asignatura_id' => ['required', 'exists:asignaturas,id'],
            'docente_id' => ['nullable', 'exists:usuarios,id'],
            'participa_evaluacion' => ['nullable', 'boolean'],
        ]);

        $proyecto = Proyecto::query()->findOrFail($datos['proyecto_id']);
        $proyecto->asignaturas()->syncWithoutDetaching([
            $datos['asignatura_id'] => [
                'docente_id' => $datos['docente_id'] ?? null,
                'participa_evaluacion' => $request->boolean('participa_evaluacion', true),
                'creado_en' => now(),
                'actualizado_en' => now(),
            ],
        ]);

        return redirect()->route('modulos.show', 'proyectos')->with('estado', 'Asignatura participante vinculada al proyecto correctamente.');
    }

    /**
     * @return array<string, mixed>
     */
    private function datos(): array
    {
        $proyectos = Proyecto::query()
            ->with([
                'equipo.grupoAcademico.carrera:id,clave,nombre',
                'equipo.asesores:id,nombre,matricula',
                'guiaIntegradora:id,nombre,version',
                'docentes:id,nombre,matricula',
                'asignaturas:id,nombre,clave',
            ])
            ->withCount(['docentes as docentes_count', 'asignaturas as asignaturas_count'])
            ->orderBy('titulo')
            ->get();

        return [
            'proyectos' => $proyectos,
            'equipos' => Equipo::query()->with('grupoAcademico.carrera:id,clave')->orderBy('nombre')->get(),
            'guias' => GuiaIntegradora::query()->orderBy('nombre')->get(['id', 'nombre', 'version', 'estado']),
            'docentes' => User::query()->whereHas('role', fn ($query) => $query->where('nombre', 'docente_asesor'))->orderBy('nombre')->get(['id', 'nombre', 'matricula']),
            'asignaturas' => Asignatura::query()->orderBy('nombre')->get(['id', 'nombre', 'clave']),
            'apartados' => ApartadoGuia::query()->with('guiaIntegradora:id,nombre')->orderBy('orden')->get(['id', 'guia_integradora_id', 'orden', 'titulo']),
            'metricasProyectos' => [
                ['label' => 'Proyectos', 'value' => (string) $proyectos->count()],
                ['label' => 'Con docentes', 'value' => (string) $proyectos->where('docentes_count', '>', 0)->count()],
                ['label' => 'Multidisciplinarios', 'value' => (string) $proyectos->where('asignaturas_count', '>', 1)->count()],
            ],
        ];
    }
}
