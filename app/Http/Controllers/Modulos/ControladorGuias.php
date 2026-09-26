<?php

namespace App\Http\Controllers\Modulos;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Modulos\Soporte\AutorizaDireccion;
use App\Models\ApartadoGuia;
use App\Models\Asignatura;
use App\Models\Carrera;
use App\Models\FirmaApartadoGuia;
use App\Models\GuiaIntegradora;
use App\Models\Periodo;
use App\Models\User;
use App\Servicios\CicloAcademico;
use App\Soporte\SistemaInterfaz;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ControladorGuias extends Controller
{
    use AutorizaDireccion;

    public function mostrar(Request $request): View
    {
        $usuario = $request->user()->loadMissing('role');
        $rol = $usuario->role?->nombre ?? 'estudiante';

        abort_unless(SistemaInterfaz::puedeVer($rol, 'guias'), 403);

        return view('modulos.gestion-proyectos.guias', [
            'active' => 'guias',
            'navegacion' => SistemaInterfaz::navegacionPara($rol),
            'pagina' => SistemaInterfaz::pagina('guias'),
            'roleName' => $usuario->role?->nombre_visible ?? 'Estudiante / Equipo',
            ...$this->datos($request),
        ]);
    }

    public function guardar(Request $request): RedirectResponse
    {
        $this->autorizarDireccion($request);

        $datos = $request->validate([
            'periodo_id' => ['required', 'exists:periodos,id'],
            'periodo_fin_id' => ['nullable', 'exists:periodos,id'],
            'asignatura_id' => ['required', 'exists:asignaturas,id'],
            'nombre' => ['required', 'string', 'max:255'],
            'cuatrimestre' => ['required', 'integer', 'between:1,12'],
            'competencias_evaluar' => ['nullable', 'string'],
            'objetivo_aprendizaje' => ['nullable', 'string'],
            'version' => ['required', 'string', 'max:30'],
            'estado' => ['sometimes', 'in:borrador'],
        ]);

        $asignatura = Asignatura::query()->findOrFail($datos['asignatura_id']);
        $inicio = Periodo::findOrFail($datos['periodo_id']);
        $fin = isset($datos['periodo_fin_id']) ? Periodo::findOrFail($datos['periodo_fin_id']) : $inicio;
        if ($inicio->estado === 'cerrado' || $fin->estado === 'cerrado' || $fin->fecha_fin < $inicio->fecha_fin) {
            throw ValidationException::withMessages(['periodo_id' => 'Selecciona periodos abiertos y un periodo final igual o posterior al inicial.']);
        }
        $guiaDuplicada = GuiaIntegradora::query()
            ->where('periodo_id', $datos['periodo_id'])
            ->where('cuatrimestre', $datos['cuatrimestre'])
            ->whereHas('asignatura', fn ($query) => $query->where('carrera_id', $asignatura->carrera_id))
            ->exists();

        if ($guiaDuplicada) {
            throw ValidationException::withMessages([
                'cuatrimestre' => 'Ya existe una guía para esta carrera, periodo y cuatrimestre. Esa guía se comparte con todos sus equipos.',
            ]);
        }

        GuiaIntegradora::query()->create([...$datos, 'estado' => 'borrador', 'creado_por' => $request->user()->id]);

        return redirect()->route('modulos.show', 'guias')->with('estado', 'Guía integradora guardada correctamente.');
    }

    public function publicar(Request $request, GuiaIntegradora $guia, CicloAcademico $ciclo): RedirectResponse
    {
        $this->autorizarDireccion($request);
        $ciclo->publicar($guia);

        return back()->with('estado', 'Guía publicada. Ya puede asignarse a los proyectos.');
    }

    public function guardarApartado(Request $request): RedirectResponse
    {
        $this->autorizarDireccion($request);

        $datos = $request->validate([
            'guia_integradora_id' => ['required', 'exists:guias_integradoras,id'],
            'orden' => ['required', 'integer', 'min:1'],
            'titulo' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string'],
            'fecha_limite' => ['nullable', 'date'],
            'ponderacion' => ['required', 'numeric', 'min:0', 'max:100'],
            'requiere_documento' => ['nullable', 'boolean'],
            'requiere_codigo' => ['nullable', 'boolean'],
        ]);

        ApartadoGuia::query()->updateOrCreate(
            ['guia_integradora_id' => $datos['guia_integradora_id'], 'orden' => $datos['orden']],
            [
                'titulo' => $datos['titulo'],
                'descripcion' => $datos['descripcion'] ?? null,
                'fecha_limite' => $datos['fecha_limite'] ?? null,
                'ponderacion' => $datos['ponderacion'],
                'requiere_documento' => $request->boolean('requiere_documento'),
                'requiere_codigo' => $request->boolean('requiere_codigo'),
            ],
        );

        return redirect()->route('modulos.show', 'guias')->with('estado', 'Apartado de guía guardado correctamente.');
    }

    public function asignarAsignaturaApartado(Request $request): RedirectResponse
    {
        $this->autorizarDireccion($request);

        $datos = $request->validate([
            'apartado_guia_id' => ['required', 'exists:apartados_guia,id'],
            'asignatura_id' => ['required', 'exists:asignaturas,id'],
            'rol_contribucion' => ['nullable', 'string', 'max:120'],
            'requiere_firma' => ['nullable', 'boolean'],
        ]);

        $apartado = ApartadoGuia::query()->findOrFail($datos['apartado_guia_id']);
        $apartado->asignaturasContribuyentes()->syncWithoutDetaching([
            $datos['asignatura_id'] => [
                'rol_contribucion' => $datos['rol_contribucion'] ?? null,
                'requiere_firma' => $request->boolean('requiere_firma', true),
                'creado_en' => now(),
                'actualizado_en' => now(),
            ],
        ]);

        return redirect()->route('modulos.show', 'guias')->with('estado', 'Asignatura vinculada al apartado correctamente.');
    }

    public function guardarFirmaApartado(Request $request): RedirectResponse
    {
        $this->autorizarDireccion($request);

        $datos = $request->validate([
            'apartado_guia_id' => ['required', 'exists:apartados_guia,id'],
            'asignatura_id' => ['nullable', 'exists:asignaturas,id'],
            'docente_id' => ['nullable', 'exists:usuarios,id'],
            'orden' => ['required', 'integer', 'min:1'],
            'etiqueta' => ['required', 'string', 'max:120'],
            'requerida' => ['nullable', 'boolean'],
        ]);

        FirmaApartadoGuia::query()->updateOrCreate(
            [
                'apartado_guia_id' => $datos['apartado_guia_id'],
                'orden' => $datos['orden'],
                'etiqueta' => $datos['etiqueta'],
            ],
            [
                'asignatura_id' => $datos['asignatura_id'] ?? null,
                'docente_id' => $datos['docente_id'] ?? null,
                'requerida' => $request->boolean('requerida', true),
            ],
        );

        return redirect()->route('modulos.show', 'guias')->with('estado', 'Firma requerida guardada correctamente.');
    }

    public function asignarDocenteCalificador(Request $request): RedirectResponse
    {
        $this->autorizarDireccion($request);

        $datos = $request->validate([
            'apartado_guia_id' => ['required', 'exists:apartados_guia,id'],
            'docente_id' => ['required', 'exists:usuarios,id'],
        ]);

        $apartado = ApartadoGuia::query()
            ->with('guiaIntegradora.asignatura:id,carrera_id')
            ->findOrFail($datos['apartado_guia_id']);
        $carreraId = $apartado->guiaIntegradora->asignatura?->carrera_id;
        $docente = User::query()
            ->whereKey($datos['docente_id'])
            ->whereHas('role', fn ($query) => $query->whereIn('nombre', ['docente_lider', 'docente_materia']))
            ->where(function ($query) use ($carreraId): void {
                $query->where('carrera_id', $carreraId)
                    ->orWhereHas('carrerasComoDocente', fn ($carreras) => $carreras
                        ->where('carreras.id', $carreraId)
                        ->where('docentes_carrera.activo', true));
            })
            ->firstOrFail();

        if ($apartado->requiere_codigo && ! $docente->hasRole('docente_lider')) {
            return back()->withErrors(['docente_id' => 'Los apartados de código solo pueden asignarse al docente líder.']);
        }

        FirmaApartadoGuia::query()->updateOrCreate(
            ['apartado_guia_id' => $apartado->id, 'docente_id' => $docente->id],
            [
                'asignatura_id' => null,
                'orden' => (int) FirmaApartadoGuia::query()->where('apartado_guia_id', $apartado->id)->max('orden') + 1,
                'etiqueta' => 'Docente calificador',
                'requerida' => true,
            ],
        );

        return back()->with('estado', 'Docente asignado al apartado que calificará.');
    }

    public function quitarDocenteCalificador(Request $request): RedirectResponse
    {
        $this->autorizarDireccion($request);
        $datos = $request->validate([
            'apartado_guia_id' => ['required', 'exists:apartados_guia,id'],
            'docente_id' => ['required', 'exists:usuarios,id'],
        ]);

        FirmaApartadoGuia::query()
            ->where('apartado_guia_id', $datos['apartado_guia_id'])
            ->where('docente_id', $datos['docente_id'])
            ->delete();

        return back()->with('estado', 'Docente retirado del apartado.');
    }

    /**
     * @return array<string, mixed>
     */
    private function datos(Request $request): array
    {
        $periodos = Periodo::query()->orderByDesc('fecha_inicio')->get(['id', 'nombre', 'estado']);
        $carreras = Carrera::query()->where('estado', 'activa')->orderBy('nombre')->get(['id', 'nombre', 'clave']);
        $periodoSeleccionado = $request->query('periodo_guias')
            ?: $periodos->firstWhere('estado', 'activo')?->id
            ?: $periodos->first()?->id;
        $carreraSeleccionada = $request->query('carrera_guias') ?: $carreras->first()?->id;
        $filtros = [
            'periodo_id' => $periodoSeleccionado,
            'carrera_id' => $carreraSeleccionada,
            'estado' => $request->query('estado_guias'),
            'busqueda' => trim((string) $request->query('busqueda_guias', '')),
        ];

        $guias = GuiaIntegradora::query()
            ->with([
                'periodo:id,nombre,estado',
                'periodoFin:id,nombre,estado',
                'asignatura:id,carrera_id,nombre,clave',
                'apartados.asignaturasContribuyentes:id,nombre,clave',
                'apartados.firmas.asignatura:id,nombre,clave',
                'apartados.firmas.docente:id,nombre,matricula',
            ])
            ->withCount(['apartados as apartados_count'])
            ->when($filtros['periodo_id'], fn ($query) => $query->where('periodo_id', $filtros['periodo_id']))
            ->when($filtros['carrera_id'], fn ($query) => $query->whereHas('asignatura', fn ($asignatura) => $asignatura->where('carrera_id', $filtros['carrera_id'])))
            ->when($filtros['estado'], fn ($query) => $query->where('estado', $filtros['estado']))
            ->when($filtros['busqueda'] !== '', function ($query) use ($filtros) {
                $busqueda = $filtros['busqueda'];
                $query->where(fn ($scope) => $scope
                    ->where('nombre', 'like', "%{$busqueda}%")
                    ->orWhere('version', 'like', "%{$busqueda}%")
                    ->orWhere('cuatrimestre', 'like', "%{$busqueda}%")
                    ->orWhereHas('asignatura', fn ($asignatura) => $asignatura
                        ->where('nombre', 'like', "%{$busqueda}%")
                        ->orWhere('clave', 'like', "%{$busqueda}%")));
            })
            ->orderByDesc('creado_en')
            ->get();

        $editables = $guias->filter(fn ($guia) => $guia->estado !== 'cerrada' && ($guia->periodoFin ?? $guia->periodo)->estado !== 'cerrado');
        $apartados = ApartadoGuia::query()
            ->with('guiaIntegradora:id,nombre,version')
            ->whereIn('guia_integradora_id', $editables->pluck('id'))
            ->orderBy('guia_integradora_id')
            ->orderBy('orden')
            ->get(['id', 'guia_integradora_id', 'orden', 'titulo']);

        return [
            'guias' => $guias,
            'guiasEditables' => $editables,
            'periodos' => $periodos,
            'carreras' => $carreras,
            'filtrosGuias' => $filtros,
            'asignaturas' => Asignatura::query()->with('carrera:id,clave')->orderBy('nombre')->get(['id', 'carrera_id', 'nombre', 'clave']),
            'apartados' => $apartados,
            'docentes' => User::query()
                ->with(['role:id,nombre,nombre_visible', 'carrerasComoDocente:id'])
                ->whereHas('role', fn ($query) => $query->whereIn('nombre', ['docente_lider', 'docente_materia']))
                ->orderBy('nombre')
                ->get(['id', 'carrera_id', 'nombre', 'matricula', 'rol_id']),
            'metricasGuias' => [
                ['label' => 'Guías', 'value' => (string) $guias->count()],
                ['label' => 'Publicadas', 'value' => (string) $guias->where('estado', 'publicada')->count()],
                ['label' => 'Apartados', 'value' => (string) $guias->sum('apartados_count')],
            ],
        ];
    }
}
