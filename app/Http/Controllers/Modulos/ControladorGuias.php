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
use App\Soporte\AuditoriaAcademica;
use App\Soporte\SistemaInterfaz;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        $datos = $this->validarDatosGuia($request);
        $asignatura = $this->validarContextoGuia($datos);
        GuiaIntegradora::query()->create([...$datos, 'estado' => 'borrador', 'creado_por' => $request->user()->id]);

        return redirect()->route('modulos.show', [
            'modulo' => 'guias', 'periodo_guias' => $datos['periodo_id'], 'carrera_guias' => $asignatura->carrera_id,
        ])->with('estado', 'Guía integradora guardada correctamente.');
    }

    public function actualizar(Request $request, GuiaIntegradora $guia): RedirectResponse
    {
        $this->autorizarDireccion($request);
        $guia = GuiaIntegradora::whereKey($guia->id)->lockForUpdate()->firstOrFail();
        $datos = $this->validarDatosGuia($request);
        if ($guia->proyectos()->conHistorial()->exists()) {
            throw ValidationException::withMessages(['guia' => 'Esta guía tiene historial. Crea una nueva versión para corregirla.']);
        }
        $guia->fill($datos);
        if ($guia->isDirty(['periodo_id', 'periodo_fin_id', 'asignatura_id', 'cuatrimestre'])) {
            if ($guia->guia_origen_id || $guia->versiones()->exists() || $guia->proyectos()->exists()) {
                throw ValidationException::withMessages(['guia' => 'El contexto académico de una guía asignada debe conservarse. Puedes editar sus datos generales.']);
            }
            $this->validarContextoGuia($datos, $guia);
            if ($guia->isDirty(['asignatura_id', 'cuatrimestre']) && $guia->apartados()->exists()) {
                throw ValidationException::withMessages(['guia' => 'La asignatura principal y el cuatrimestre requieren una guía sin apartados configurados.']);
            }
            foreach ($guia->apartados()->whereNotNull('fecha_limite')->get() as $apartado) {
                if (! $guia->admiteFechaLimite($apartado->fecha_limite->toDateTimeString())) {
                    throw ValidationException::withMessages(['periodo_id' => 'El nuevo rango dejaría apartados fuera de las fechas de la guía.']);
                }
            }
        }
        if ($guia->isDirty('version')) {
            $origen = (int) ($guia->guia_origen_id ?? $guia->id);
            if (GuiaIntegradora::where(fn ($q) => $q->whereKey($origen)->orWhere('guia_origen_id', $origen))
                ->whereKeyNot($guia->id)->where('version', $guia->version)->exists()) {
                throw ValidationException::withMessages(['version' => 'Esta versión ya está registrada para la guía.']);
            }
        }
        $guia->save();

        return redirect()->route('modulos.guias', [
            'periodo_guias' => $guia->periodo_id, 'carrera_guias' => $guia->asignatura?->carrera_id,
        ])->with('estado', 'Datos de la guía actualizados.');
    }

    public function nuevaVersion(Request $request, GuiaIntegradora $guia): RedirectResponse
    {
        $this->autorizarDireccion($request);
        $datos = $request->validate([
            'version' => ['required', 'string', 'max:30', 'regex:/\A[0-9]+(?:\.[0-9]+)*\z/'],
            'motivo' => ['required', 'string', 'max:2000'],
        ]);
        $guia = GuiaIntegradora::whereKey($guia->id)->lockForUpdate()->firstOrFail();
        $origen = (int) ($guia->guia_origen_id ?? $guia->id);
        $versiones = GuiaIntegradora::where(fn ($q) => $q->whereKey($origen)->orWhere('guia_origen_id', $origen))->pluck('version');
        if ($versiones->contains(fn (string $version) => version_compare($datos['version'], $version, '<='))) {
            throw ValidationException::withMessages(['version' => 'Usa un número mayor que las versiones registradas para esta guía.']);
        }

        $nueva = $guia->replicate();
        $nueva->forceFill(['version' => $datos['version'], 'estado' => 'borrador', 'guia_origen_id' => $origen,
            'creado_por' => $request->user()->id])->save();
        foreach ($guia->apartados()->with(['firmas', 'asignaturasContribuyentes'])->get() as $apartado) {
            $copia = $apartado->replicate();
            $copia->guia_integradora_id = $nueva->id;
            $copia->save();
            foreach ($apartado->firmas as $firma) {
                $copia->firmas()->create($firma->only(['asignatura_id', 'docente_id', 'orden', 'etiqueta', 'requerida']));
            }
            $copia->asignaturasContribuyentes()->attach($apartado->asignaturasContribuyentes->mapWithKeys(
                fn ($asignatura) => [$asignatura->id => $asignatura->pivot->only(['rol_contribucion', 'requiere_firma'])]
            )->all());
        }
        AuditoriaAcademica::registrar($request->user()->id, 'guias', $nueva->id, 'crear_version',
            ['guia_id' => $guia->id, 'version' => $guia->version], ['version' => $nueva->version], $datos['motivo']);

        return redirect()->route('modulos.guias', ['periodo_guias' => $nueva->periodo_id,
            'carrera_guias' => $nueva->asignatura?->carrera_id, 'estado_guias' => 'borrador'])
            ->with('estado', 'Versión creada en borrador. Los proyectos existentes conservan la guía original.');
    }

    public function volverABorrador(Request $request, GuiaIntegradora $guia): RedirectResponse
    {
        $this->autorizarDireccion($request);
        $request->validate(['confirmar_borrador' => ['accepted']]);
        $guia = GuiaIntegradora::whereKey($guia->id)->lockForUpdate()->firstOrFail();
        if ($guia->estado !== 'publicada' || $guia->proyectos()->conHistorial()->exists()) {
            throw ValidationException::withMessages(['guia' => 'Solo se puede regresar a borrador una guía publicada sin historial de entregas, cierre o documentos.']);
        }
        $guia->update(['estado' => 'borrador']);

        return back()->with('estado', 'La guía regresó a borrador.');
    }

    public function eliminar(Request $request, GuiaIntegradora $guia): RedirectResponse
    {
        $this->autorizarDireccion($request);
        $request->validate(['confirmar_eliminacion' => ['accepted']]);
        $guia = GuiaIntegradora::whereKey($guia->id)->lockForUpdate()->firstOrFail();
        if ($guia->proyectos()->exists() || $guia->versiones()->exists()) {
            throw ValidationException::withMessages(['eliminar' => 'La guía está vinculada a proyectos u otras versiones y se conserva para su consulta.']);
        }
        $guia->delete();

        return back()->with('estado', 'Guía eliminada.');
    }

    private function validarDatosGuia(Request $request): array
    {
        $datos = $request->validate([
            'periodo_id' => ['required', 'exists:periodos,id'],
            'periodo_fin_id' => ['nullable', 'exists:periodos,id'],
            'asignatura_id' => ['required', 'exists:asignaturas,id'],
            'nombre' => ['required', 'string', 'max:255'],
            'cuatrimestre' => ['required', 'integer', 'between:1,12'],
            'competencias_evaluar' => ['nullable', 'string'],
            'objetivo_aprendizaje' => ['nullable', 'string'],
            'version' => ['required', 'string', 'max:30', 'regex:/\A[0-9]+(?:\.[0-9]+)*\z/'],
            'estado' => ['sometimes', 'in:borrador'],
        ], [
            'version.regex' => 'La versión debe contener números separados por puntos, por ejemplo 1.0.',
        ]);
        unset($datos['estado']);
        $datos['periodo_fin_id'] ??= null;

        return $datos;
    }

    private function validarContextoGuia(array $datos, ?GuiaIntegradora $guia = null): Asignatura
    {
        $asignatura = Asignatura::query()->findOrFail($datos['asignatura_id']);
        if ($asignatura->estado !== 'activo' || $asignatura->carrera?->estado !== 'activa') {
            throw ValidationException::withMessages(['asignatura_id' => 'Selecciona una asignatura activa de una carrera activa.']);
        }
        $inicio = Periodo::findOrFail($datos['periodo_id']);
        $fin = isset($datos['periodo_fin_id']) ? Periodo::findOrFail($datos['periodo_fin_id']) : $inicio;
        if ($inicio->estado === 'cerrado' || $fin->estado === 'cerrado' || $fin->fecha_fin < $inicio->fecha_fin) {
            throw ValidationException::withMessages(['periodo_id' => 'Selecciona periodos abiertos y un periodo final igual o posterior al inicial.']);
        }
        $guiaDuplicada = GuiaIntegradora::query()
            ->when($guia, fn ($q) => $q->whereKeyNot($guia->id))
            ->where('periodo_id', $datos['periodo_id'])
            ->where('cuatrimestre', $datos['cuatrimestre'])
            ->whereHas('asignatura', fn ($query) => $query->where('carrera_id', $asignatura->carrera_id))
            ->exists();

        if ($guiaDuplicada) {
            throw ValidationException::withMessages([
                'cuatrimestre' => 'Ya existe una guía para esta carrera, periodo y cuatrimestre. Esa guía se comparte con todos sus equipos.',
            ]);
        }

        return $asignatura;
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
            'orden' => ['required', 'integer', 'between:1,999'],
            'titulo' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string', 'max:20000'],
            'fecha_limite' => ['nullable', 'date', 'after:now', 'after_or_equal:2000-01-01', 'before_or_equal:2100-12-31'],
            'ponderacion' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:100'],
            'requiere_documento' => ['nullable', 'boolean'],
            'requiere_codigo' => ['nullable', 'boolean'],
        ], [
            'fecha_limite.after' => 'La fecha límite debe ser posterior al momento actual.',
        ]);

        $guia = GuiaIntegradora::query()->findOrFail($datos['guia_integradora_id']);
        if (! empty($datos['fecha_limite']) && ! $guia->admiteFechaLimite($datos['fecha_limite'])) {
            throw ValidationException::withMessages([
                'fecha_limite' => 'La fecha límite debe estar dentro de los periodos de la guía.',
            ]);
        }

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

    public function guardarApartados(Request $request): RedirectResponse
    {
        $this->autorizarDireccion($request);

        $datos = $request->validate([
            'guia_integradora_id' => ['required', 'exists:guias_integradoras,id'],
            'apartados' => ['required', 'array', 'min:1', 'max:20'],
            'apartados.*.orden' => ['required', 'integer', 'between:1,999', 'distinct'],
            'apartados.*.titulo' => ['required', 'string', 'max:255'],
            'apartados.*.descripcion' => ['nullable', 'string', 'max:20000'],
            'apartados.*.fecha_limite' => ['nullable', 'date', 'after:now', 'after_or_equal:2000-01-01', 'before_or_equal:2100-12-31'],
            'apartados.*.ponderacion' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:100'],
            'apartados.*.requiere_documento' => ['nullable', 'boolean'],
            'apartados.*.requiere_codigo' => ['nullable', 'boolean'],
        ], [
            'apartados.*.orden.distinct' => 'No repitas el orden de un apartado en la misma carga.',
            'apartados.*.fecha_limite.after' => 'Cada fecha límite debe ser posterior al momento actual.',
        ]);

        $guia = GuiaIntegradora::query()->with(['periodo', 'periodoFin', 'asignatura'])->findOrFail($datos['guia_integradora_id']);
        if ($guia->estado === 'cerrada' || ($guia->periodoFin ?? $guia->periodo)->estado === 'cerrado') {
            throw ValidationException::withMessages(['guia_integradora_id' => 'No se pueden modificar apartados de una guía cerrada.']);
        }
        foreach ($datos['apartados'] as $indice => $apartado) {
            if (! empty($apartado['fecha_limite']) && ! $guia->admiteFechaLimite($apartado['fecha_limite'])) {
                throw ValidationException::withMessages(["apartados.{$indice}.fecha_limite" => 'La fecha límite debe estar dentro de los periodos de la guía.']);
            }
        }

        DB::transaction(function () use ($request, $guia, $datos): void {
            foreach ($datos['apartados'] as $indice => $apartado) {
                ApartadoGuia::query()->updateOrCreate(
                    ['guia_integradora_id' => $guia->id, 'orden' => $apartado['orden']],
                    [
                        'titulo' => $apartado['titulo'],
                        'descripcion' => $apartado['descripcion'] ?? null,
                        'fecha_limite' => $apartado['fecha_limite'] ?? null,
                        'ponderacion' => $apartado['ponderacion'],
                        'requiere_documento' => $request->boolean("apartados.{$indice}.requiere_documento"),
                        'requiere_codigo' => $request->boolean("apartados.{$indice}.requiere_codigo"),
                    ],
                );
            }
        });

        return redirect()->route('modulos.show', [
            'modulo' => 'guias', 'periodo_guias' => $guia->periodo_id,
            'carrera_guias' => $guia->asignatura->carrera_id,
        ])->with('estado', count($datos['apartados']).' apartados guardados correctamente.');
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
                'periodo:id,nombre,estado,fecha_inicio,fecha_fin',
                'periodoFin:id,nombre,estado,fecha_inicio,fecha_fin',
                'asignatura:id,carrera_id,nombre,clave',
                'guiaOrigen:id,nombre,version',
                'apartados.asignaturasContribuyentes:id,nombre,clave',
                'apartados.firmas.asignatura:id,nombre,clave',
                'apartados.firmas.docente:id,nombre,matricula',
            ])
            ->withCount(['apartados as apartados_count'])
            ->withExists(['proyectos as tiene_historial' => fn ($q) => $q->conHistorial()])
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
        $guiasParaApartados = GuiaIntegradora::query()
            ->with([
                'periodo:id,nombre,estado,fecha_inicio,fecha_fin',
                'periodoFin:id,nombre,estado,fecha_inicio,fecha_fin',
            ])
            ->where('estado', '!=', 'cerrada')
            ->orderByDesc('creado_en')
            ->get()
            ->filter(fn ($guia) => ($guia->periodoFin ?? $guia->periodo)->estado !== 'cerrado')
            ->load(['asignatura:id,carrera_id', 'asignatura.carrera:id,clave,nombre', 'apartados' => fn ($query) => $query->orderBy('orden')]);

        $apartados = ApartadoGuia::query()
            ->with('guiaIntegradora:id,nombre,version')
            ->whereIn('guia_integradora_id', $editables->pluck('id'))
            ->orderBy('guia_integradora_id')
            ->orderBy('orden')
            ->get(['id', 'guia_integradora_id', 'orden', 'titulo']);

        return [
            'guias' => $guias,
            'guiasEditables' => $editables,
            'guiasParaApartados' => $guiasParaApartados,
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
