<?php

namespace App\Http\Controllers\Modulos;

use App\Correos\ContrasenaInicial;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Modulos\Soporte\AutorizaDireccion;
use App\Models\Carrera;
use App\Models\Equipo;
use App\Models\GrupoAcademico;
use App\Models\Periodo;
use App\Models\Role;
use App\Models\User;
use App\Soporte\SistemaInterfaz;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Throwable;

class ControladorUsuarios extends Controller
{
    use AutorizaDireccion;

    public function mostrar(Request $request): View
    {
        $usuario = $request->user()->loadMissing('role');
        $rol = $usuario->role?->nombre ?? 'estudiante';

        abort_unless(SistemaInterfaz::puedeVer($rol, 'usuarios'), 403);

        return view('modulos.control-academico.usuarios', [
            'active' => 'usuarios',
            'navegacion' => SistemaInterfaz::navegacionPara($rol),
            'pagina' => SistemaInterfaz::pagina('usuarios'),
            'roleName' => $usuario->role?->nombre_visible ?? 'Estudiante / Equipo',
            ...$this->datos($request),
        ]);
    }

    public function guardar(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->hasRole('coordinacion'), 403);

        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'matricula' => ['required', 'string', 'max:50', 'unique:usuarios,matricula'],
            'correo' => ['required', 'email', 'max:255', 'unique:usuarios,correo'],
            'rol_id' => ['required', 'exists:roles,id'],
            'carrera_id' => ['nullable', 'exists:carreras,id'],
            'grupo_academico_id' => ['nullable', 'exists:grupos_academicos,id'],
        ]);

        if ($request->user()->hasRole('docente_lider')) {
            $rolEstudiante = Role::query()->where('nombre', 'estudiante')->firstOrFail();
            abort_unless((int) $datos['rol_id'] === (int) $rolEstudiante->id && $datos['grupo_academico_id'], 403);
            abort_unless(GrupoAcademico::query()->whereKey($datos['grupo_academico_id'])->conMateriaLiderDelDocente((int) $request->user()->id)->exists(), 403);
        }

        $contrasenaTemporal = $this->generarContrasenaTemporal();

        $usuario = User::query()->create([
            'nombre' => $datos['nombre'],
            'matricula' => $datos['matricula'],
            'correo' => $datos['correo'],
            'rol_id' => $datos['rol_id'],
            'carrera_id' => $datos['carrera_id'] ?? null,
            'grupo_academico_id' => $datos['grupo_academico_id'] ?? null,
            'estado' => 'activo',
            'contrasena' => $contrasenaTemporal,
            'debe_cambiar_contrasena' => true,
        ]);

        Mail::to($usuario->correo)->send(new ContrasenaInicial(
            nombre: $usuario->nombre,
            matricula: $usuario->matricula,
            contrasenaTemporal: $contrasenaTemporal,
        ));

        return redirect()->route('modulos.show', 'usuarios')->with('estado', 'Usuario registrado correctamente. La contrasena temporal fue enviada al correo registrado.');
    }

    public function guardarDocentes(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->hasRole('coordinacion'), 403);

        $datos = $request->validate([
            'docentes' => ['required', 'array', 'min:1', 'max:30'],
            'docentes.*.nombre' => ['required', 'string', 'max:255'],
            'docentes.*.matricula' => ['required', 'string', 'max:50', 'distinct', 'unique:usuarios,matricula'],
            'docentes.*.correo' => ['required', 'email', 'max:255', 'distinct', 'unique:usuarios,correo'],
            'docentes.*.rol' => ['required', 'in:docente_lider,docente_materia'],
            'docentes.*.carrera_id' => ['required', 'exists:carreras,id'],
        ]);

        $roles = Role::query()->whereIn('nombre', ['docente_lider', 'docente_materia'])->pluck('id', 'nombre');
        $correosPendientes = [];

        DB::transaction(function () use ($datos, $roles, &$correosPendientes): void {
            foreach ($datos['docentes'] as $datosDocente) {
                $contrasenaTemporal = $this->generarContrasenaTemporal();
                $docente = User::query()->create([
                    'nombre' => $datosDocente['nombre'],
                    'matricula' => $datosDocente['matricula'],
                    'correo' => $datosDocente['correo'],
                    'rol_id' => $roles[$datosDocente['rol']],
                    'carrera_id' => $datosDocente['carrera_id'],
                    'estado' => 'activo',
                    'contrasena' => $contrasenaTemporal,
                    'debe_cambiar_contrasena' => true,
                ]);
                $docente->carrerasComoDocente()->syncWithoutDetaching([
                    $datosDocente['carrera_id'] => ['activo' => true, 'creado_en' => now(), 'actualizado_en' => now()],
                ]);
                $correosPendientes[] = [$docente, $contrasenaTemporal];
            }
        });

        $correosFallidos = 0;
        foreach ($correosPendientes as [$docente, $contrasenaTemporal]) {
            try {
                Mail::to($docente->correo)->send(new ContrasenaInicial(
                    nombre: $docente->nombre,
                    matricula: $docente->matricula,
                    contrasenaTemporal: $contrasenaTemporal,
                ));
            } catch (Throwable) {
                $correosFallidos++;
            }
        }

        return redirect()->route('modulos.show', ['modulo' => 'usuarios', 'seccion' => 'docentes'])
            ->with('estado', count($datos['docentes'])." docentes registrados. Correos fallidos: {$correosFallidos}.");
    }

    public function previsualizarAlumnos(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->hasRole('coordinacion'), 403);

        $datos = $request->validate([
            'periodo_id' => ['required', 'exists:periodos,id'],
            'listas' => ['required', 'array', 'min:1', 'max:12'],
            'listas.*.archivo' => ['required', 'file', 'mimes:xlsx,xls,csv,txt', 'max:10240'],
            'listas.*.carrera_id' => ['required', 'exists:carreras,id'],
            'listas.*.grado' => ['required', 'integer', 'between:1,12'],
            'listas.*.grupo' => ['required', 'string', 'max:10'],
        ]);

        $vistaPrevia = [];
        foreach ($datos['listas'] as $indice => $configuracion) {
            $archivo = $request->file("listas.{$indice}.archivo");
            $filas = $this->leerFilasCargaAlumnos($archivo->getRealPath());
            [$encabezados, $filaInicio] = $this->detectarEncabezados($filas);
            $faltantes = array_diff(['matricula', 'nombre_completo', 'correo_electronico'], array_keys($encabezados));

            if ($faltantes !== []) {
                throw ValidationException::withMessages([
                    "listas.{$indice}.archivo" => 'Faltan columnas: '.implode(', ', $faltantes).'.',
                ]);
            }

            $alumnos = collect(array_slice($filas, $filaInicio))
                ->map(fn (array $fila) => $this->mapearFilaCarga($fila, $encabezados))
                ->reject(fn (array $fila) => $this->filaVacia($fila))
                ->map(fn (array $fila) => [
                    'matricula' => $fila['matricula'] ?? '',
                    'nombre_completo' => $fila['nombre_completo'] ?? '',
                    'correo_electronico' => $fila['correo_electronico'] ?? '',
                ])
                ->values()
                ->all();

            $filasInvalidas = collect($alumnos)->filter(fn (array $alumno) =>
                $alumno['matricula'] === ''
                || $alumno['nombre_completo'] === ''
                || ! filter_var($alumno['correo_electronico'], FILTER_VALIDATE_EMAIL)
            );

            if ($filasInvalidas->isNotEmpty()) {
                throw ValidationException::withMessages([
                    "listas.{$indice}.archivo" => "La lista contiene {$filasInvalidas->count()} filas incompletas o con correo inválido.",
                ]);
            }

            $vistaPrevia[] = [
                'nombre' => $archivo->getClientOriginalName(),
                'carrera_id' => (int) $configuracion['carrera_id'],
                'grado' => (int) $configuracion['grado'],
                'grupo' => mb_strtoupper(trim($configuracion['grupo'])),
                'alumnos' => $alumnos,
            ];
        }

        $token = (string) Str::uuid();
        $request->session()->put("preview_alumnos.{$token}", [
            'periodo_id' => (int) $datos['periodo_id'],
            'listas' => $vistaPrevia,
        ]);

        return redirect()->route('modulos.show', ['modulo' => 'usuarios', 'seccion' => 'alumnos', 'preview' => $token]);
    }

    public function confirmarAlumnos(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->hasRole('coordinacion'), 403);
        $datos = $request->validate(['preview' => ['required', 'uuid']]);
        $carga = $request->session()->pull("preview_alumnos.{$datos['preview']}");
        abort_unless($carga, 419);

        $periodo = Periodo::query()->findOrFail($carga['periodo_id']);
        $rolEstudiante = Role::query()->where('nombre', 'estudiante')->firstOrFail();
        $resumen = [];
        $correosPendientes = [];

        DB::transaction(function () use ($carga, $periodo, $rolEstudiante, $request, &$resumen, &$correosPendientes): void {
            foreach ($carga['listas'] as $lista) {
                $grupo = GrupoAcademico::query()->firstOrCreate([
                    'periodo_id' => $periodo->id,
                    'carrera_id' => $lista['carrera_id'],
                    'grado' => $lista['grado'],
                    'grupo' => $lista['grupo'],
                ], ['nombre' => $lista['grado'].$lista['grupo']]);

                if ($request->user()->hasRole('docente_lider')) {
                    abort_unless(GrupoAcademico::query()->whereKey($grupo->id)->conMateriaLiderDelDocente((int) $request->user()->id)->exists(), 403);
                }

                $creados = 0;
                $actualizados = 0;
                foreach ($lista['alumnos'] as $fila) {
                    if ($fila['matricula'] === '' || $fila['nombre_completo'] === '' || ! filter_var($fila['correo_electronico'], FILTER_VALIDATE_EMAIL)) {
                        throw ValidationException::withMessages(['listas' => "La lista {$lista['nombre']} contiene datos incompletos o un correo inválido."]);
                    }
                    $existente = User::query()->where('matricula', $fila['matricula'])->first();
                    $contrasena = $existente ? null : $this->generarContrasenaTemporal();
                    $alumno = User::query()->updateOrCreate(['matricula' => $fila['matricula']], [
                        'nombre' => $fila['nombre_completo'],
                        'correo' => $fila['correo_electronico'],
                        'rol_id' => $rolEstudiante->id,
                        'carrera_id' => $lista['carrera_id'],
                        'grupo_academico_id' => $grupo->id,
                        'estado' => 'activo',
                        ...($contrasena ? ['contrasena' => $contrasena, 'debe_cambiar_contrasena' => true] : []),
                    ]);
                    if ($contrasena) {
                        $correosPendientes[] = [$alumno, $contrasena];
                    }
                    $existente ? $actualizados++ : $creados++;
                }
                $resumen[] = "{$lista['nombre']}: ".count($lista['alumnos'])." admitidos ({$creados} nuevos, {$actualizados} actualizados)";
            }
        });

        $correosFallidos = 0;
        foreach ($correosPendientes as [$alumno, $contrasena]) {
            try {
                Mail::to($alumno->correo)->send(new ContrasenaInicial(
                    nombre: $alumno->nombre,
                    matricula: $alumno->matricula,
                    contrasenaTemporal: $contrasena,
                ));
            } catch (Throwable) {
                $correosFallidos++;
            }
        }

        return redirect()->route('modulos.show', ['modulo' => 'usuarios', 'seccion' => 'alumnos', 'periodo_alumnos' => $periodo->id])
            ->with('estado', 'Carga completada. '.implode(' · ', $resumen)." · Correos fallidos: {$correosFallidos}.");
    }

    public function actualizarCarreraDocente(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->hasRole('coordinacion'), 403);

        $datos = $request->validate([
            'docente_id' => ['required', 'exists:usuarios,id'],
            'carrera_id' => ['required', 'exists:carreras,id'],
        ]);

        $docente = User::query()
            ->whereKey($datos['docente_id'])
            ->whereHas('role', fn ($query) => $query->whereIn('nombre', ['docente_lider', 'docente_materia']))
            ->firstOrFail();

        $docente->update(['carrera_id' => $datos['carrera_id']]);
        $docente->carrerasComoDocente()
            ->newPivotStatement()
            ->where('docente_id', $docente->id)
            ->update(['activo' => false, 'actualizado_en' => now()]);
        $docente->carrerasComoDocente()->syncWithoutDetaching([
            $datos['carrera_id'] => ['activo' => true, 'creado_en' => now(), 'actualizado_en' => now()],
        ]);

        return redirect()->route('modulos.show', 'usuarios')->with('estado', 'Carrera principal del docente actualizada correctamente.');
    }

    public function importarAlumnos(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->hasRole('coordinacion'), 403);

        $datos = $request->validate([
            'periodo_id' => ['required', 'exists:periodos,id'],
            'archivo' => ['required', 'file', 'mimes:xlsx,xls,csv,txt', 'max:10240'],
        ]);

        $periodo = Periodo::query()->findOrFail($datos['periodo_id']);
        $rolEstudiante = Role::query()->where('nombre', 'estudiante')->firstOrFail();
        $filas = $this->leerFilasCargaAlumnos($request->file('archivo')->getRealPath());
        [$encabezados, $filaInicio] = $this->detectarEncabezados($filas);

        $obligatorias = ['matricula', 'nombre_completo', 'correo_electronico', 'clave_carrera', 'grado', 'grupo'];
        $faltantes = array_diff($obligatorias, array_keys($encabezados));

        if ($faltantes !== []) {
            throw ValidationException::withMessages([
                'archivo' => 'La lista no contiene las columnas obligatorias: '.implode(', ', $faltantes).'.',
            ]);
        }

        $resumen = [
            'creados' => 0,
            'actualizados' => 0,
            'grupos' => 0,
            'equipos' => 0,
            'omitidos' => 0,
            'correos_fallidos' => 0,
        ];
        $errores = [];
        $correosPendientes = [];

        DB::transaction(function () use ($filas, $filaInicio, $encabezados, $obligatorias, $periodo, $rolEstudiante, $request, &$resumen, &$errores, &$correosPendientes): void {
            foreach (array_slice($filas, $filaInicio) as $indice => $fila) {
                $numeroFila = $filaInicio + $indice + 1;
                $datosFila = $this->mapearFilaCarga($fila, $encabezados);

                if ($this->filaVacia($datosFila)) {
                    continue;
                }

                $filaConErrores = false;

                foreach ($obligatorias as $columna) {
                    if (($datosFila[$columna] ?? '') === '') {
                        $errores[] = "Fila {$numeroFila}: falta {$columna}.";
                        $filaConErrores = true;
                    }
                }

                if (count($errores) >= 8) {
                    break;
                }

                if ($filaConErrores) {
                    continue;
                }

                $claveCarrera = mb_strtoupper((string) ($datosFila['clave_carrera'] ?? ''));
                $carrera = Carrera::query()->where('clave', $claveCarrera)->first();

                if (! $carrera) {
                    $errores[] = "Fila {$numeroFila}: la carrera {$claveCarrera} no existe.";

                    continue;
                }

                $matricula = (string) $datosFila['matricula'];
                $correo = (string) $datosFila['correo_electronico'];
                $grado = (int) $datosFila['grado'];
                $grupoLetra = mb_strtoupper((string) $datosFila['grupo']);

                $correoOcupado = User::query()
                    ->where('correo', $correo)
                    ->where('matricula', '!=', $matricula)
                    ->exists();

                if ($correoOcupado) {
                    $errores[] = "Fila {$numeroFila}: el correo {$correo} ya pertenece a otro usuario.";

                    continue;
                }

                $claveGrupo = ['periodo_id' => $periodo->id, 'carrera_id' => $carrera->id, 'grado' => $grado, 'grupo' => $grupoLetra];
                $grupoAcademico = $request->user()->hasRole('docente_lider')
                    ? GrupoAcademico::query()->where($claveGrupo)->conMateriaLiderDelDocente((int) $request->user()->id)->first()
                    : GrupoAcademico::query()->firstOrCreate($claveGrupo, ['nombre' => $grado.$grupoLetra]);

                if (! $grupoAcademico) {
                    $errores[] = "Fila {$numeroFila}: el grupo {$grado}{$grupoLetra} no está asignado a este líder.";

                    continue;
                }

                if ($grupoAcademico->wasRecentlyCreated) {
                    $resumen['grupos']++;
                }

                $alumnoExistente = User::query()->where('matricula', $matricula)->first();
                $contrasenaTemporal = $alumnoExistente ? null : $this->generarContrasenaTemporal();

                $alumno = User::query()->updateOrCreate(
                    ['matricula' => $matricula],
                    [
                        'nombre' => (string) $datosFila['nombre_completo'],
                        'correo' => $correo,
                        'rol_id' => $rolEstudiante->id,
                        'carrera_id' => $carrera->id,
                        'grupo_academico_id' => $grupoAcademico->id,
                        'estado' => 'activo',
                        ...($contrasenaTemporal ? [
                            'contrasena' => $contrasenaTemporal,
                            'debe_cambiar_contrasena' => true,
                        ] : []),
                    ],
                );

                $alumnoExistente ? $resumen['actualizados']++ : $resumen['creados']++;

                if ($contrasenaTemporal) {
                    $correosPendientes[] = [
                        'correo' => $alumno->correo,
                        'nombre' => $alumno->nombre,
                        'matricula' => $alumno->matricula,
                        'contrasena_temporal' => $contrasenaTemporal,
                    ];
                }

                if (($datosFila['equipo'] ?? '') !== '') {
                    $equipo = Equipo::query()->firstOrCreate(
                        ['grupo_academico_id' => $grupoAcademico->id, 'nombre' => (string) $datosFila['equipo']],
                        ['estado' => 'activo'],
                    );

                    if ($equipo->wasRecentlyCreated) {
                        $resumen['equipos']++;
                    }

                    $equiposDelGrupo = Equipo::query()
                        ->where('grupo_academico_id', $grupoAcademico->id)
                        ->pluck('id');

                    DB::table('integrantes_equipo')
                        ->whereIn('equipo_id', $equiposDelGrupo)
                        ->where('estudiante_id', $alumno->id)
                        ->update(['activo' => false, 'actualizado_en' => now()]);

                    $equipo->integrantes()->syncWithoutDetaching([
                        $alumno->id => ['activo' => true, 'creado_en' => now(), 'actualizado_en' => now()],
                    ]);

                    if (mb_strtolower((string) ($datosFila['rol_en_equipo'] ?? '')) === 'lider') {
                        $equipo->update(['lider_id' => $alumno->id]);
                    }
                }
            }

            if ($errores !== []) {
                throw ValidationException::withMessages(['archivo' => implode(' ', array_slice($errores, 0, 8))]);
            }

        });

        foreach ($correosPendientes as $correoPendiente) {
            try {
                Mail::to($correoPendiente['correo'])->send(new ContrasenaInicial(
                    nombre: $correoPendiente['nombre'],
                    matricula: $correoPendiente['matricula'],
                    contrasenaTemporal: $correoPendiente['contrasena_temporal'],
                ));
            } catch (Throwable) {
                $resumen['correos_fallidos']++;
            }
        }

        return redirect()->route('modulos.show', 'usuarios')->with(
            'estado',
            "Lista cargada para {$periodo->nombre}. Alumnos creados: {$resumen['creados']}. Actualizados: {$resumen['actualizados']}. Grupos nuevos: {$resumen['grupos']}. Equipos nuevos: {$resumen['equipos']}. Correos fallidos: {$resumen['correos_fallidos']}."
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function datos(Request $request): array
    {
        $periodoSeleccionado = $request->query('periodo_alumnos')
            ?: Periodo::query()->where('estado', 'activo')->orderByDesc('fecha_inicio')->value('id')
            ?: Periodo::query()->orderByDesc('fecha_inicio')->value('id');
        $carreras = Carrera::query()->orderBy('nombre')->get(['id', 'nombre', 'clave']);
        $carreraSeleccionada = $request->query('carrera_alumnos')
            ?: GrupoAcademico::query()->where('periodo_id', $periodoSeleccionado)->orderBy('carrera_id')->value('carrera_id')
            ?: $carreras->first()?->id;
        $gruposPaginacion = GrupoAcademico::query()
            ->with('carrera:id,clave')
            ->where('periodo_id', $periodoSeleccionado)
            ->where('carrera_id', $carreraSeleccionada)
            ->when($request->user()->hasRole('docente_lider'), fn ($query) => $query->conMateriaLiderDelDocente((int) $request->user()->id))
            ->orderBy('grado')
            ->orderBy('grupo')
            ->get(['id', 'carrera_id', 'nombre', 'grado', 'grupo']);
        $grupoSeleccionado = $gruposPaginacion->contains('id', (int) $request->query('grupo_alumnos'))
            ? (int) $request->query('grupo_alumnos')
            : $gruposPaginacion->first()?->id;

        $filtrosAlumnos = [
            'busqueda' => trim((string) $request->query('busqueda_alumnos', '')),
            'carrera_id' => $carreraSeleccionada,
            'grupo_academico_id' => $grupoSeleccionado,
            'estado' => $request->query('estado_alumnos'),
            'periodo_id' => $periodoSeleccionado,
        ];

        $carreraDocenteSeleccionada = $request->query('carrera_docentes') ?: $carreras->first()?->id;
        $filtrosDocentes = [
            'busqueda' => trim((string) $request->query('busqueda_docentes', '')),
            'carrera_id' => $carreraDocenteSeleccionada,
            'estado' => $request->query('estado_docentes'),
        ];

        $alumnos = User::query()
            ->with(['role:id,nombre,nombre_visible', 'carrera:id,clave,nombre', 'grupoAcademico:id,nombre,grado,grupo'])
            ->whereHas('role', fn ($query) => $query->where('nombre', 'estudiante'))
            ->when($grupoSeleccionado, fn ($query) => $query->where('grupo_academico_id', $grupoSeleccionado))
            ->when(! $grupoSeleccionado, fn ($query) => $query->whereRaw('1 = 0'))
            ->when($request->user()->hasRole('docente_lider'), fn ($query) => $query->whereHas('grupoAcademico', fn ($grupo) => $grupo->conMateriaLiderDelDocente((int) $request->user()->id)));

        if ($filtrosAlumnos['busqueda'] !== '') {
            $busqueda = $filtrosAlumnos['busqueda'];
            $alumnos->where(function ($query) use ($busqueda) {
                $query
                    ->where('nombre', 'like', "%{$busqueda}%")
                    ->orWhere('matricula', 'like', "%{$busqueda}%")
                    ->orWhere('correo', 'like', "%{$busqueda}%")
                    ->orWhereHas('carrera', fn ($carrera) => $carrera
                        ->where('nombre', 'like', "%{$busqueda}%")
                        ->orWhere('clave', 'like', "%{$busqueda}%"))
                    ->orWhereHas('grupoAcademico', fn ($grupo) => $grupo
                        ->where('nombre', 'like', "%{$busqueda}%")
                        ->orWhere('grado', 'like', "%{$busqueda}%")
                        ->orWhere('grupo', 'like', "%{$busqueda}%"));
            });
        }

        if ($filtrosAlumnos['estado']) {
            $alumnos->where('estado', $filtrosAlumnos['estado']);
        }

        $docentes = User::query()
            ->with(['role:id,nombre,nombre_visible', 'carrera:id,clave,nombre'])
            ->whereHas('role', fn ($query) => $query->whereIn('nombre', ['docente_lider', 'docente_materia']))
            ->when($request->user()->hasRole('docente_lider'), fn ($query) => $query->whereRaw('1 = 0'));

        if ($filtrosDocentes['busqueda'] !== '') {
            $busqueda = $filtrosDocentes['busqueda'];
            $docentes->where(function ($query) use ($busqueda) {
                $query
                    ->where('nombre', 'like', "%{$busqueda}%")
                    ->orWhere('matricula', 'like', "%{$busqueda}%")
                    ->orWhere('correo', 'like', "%{$busqueda}%")
                    ->orWhereHas('carrera', fn ($carrera) => $carrera
                        ->where('nombre', 'like', "%{$busqueda}%")
                        ->orWhere('clave', 'like', "%{$busqueda}%"));
            });
        }

        if ($filtrosDocentes['carrera_id']) {
            $docentes->where('carrera_id', $filtrosDocentes['carrera_id']);
        }

        if ($filtrosDocentes['estado']) {
            $docentes->where('estado', $filtrosDocentes['estado']);
        }

        $alumnosDelGrupo = $alumnos
            ->orderBy('nombre')
            ->get();

        $docentesPorCarrera = $docentes
            ->orderBy('nombre')
            ->get();

        $usuariosDireccion = $request->user()->hasRole('coordinacion')
            ? User::query()
                ->whereHas('role', fn ($query) => $query->where('nombre', 'coordinacion'))
                ->orderBy('nombre')
                ->get()
            : collect();

        $totalEstudiantes = $alumnosDelGrupo->count();
        $totalDocentes = $docentesPorCarrera->count();
        $totalUsuarios = $totalEstudiantes + $totalDocentes + $usuariosDireccion->count();

        return [
            'alumnos' => $alumnosDelGrupo,
            'docentes' => $docentesPorCarrera,
            'usuariosDireccion' => $usuariosDireccion,
            'filtrosAlumnos' => $filtrosAlumnos,
            'filtrosDocentes' => $filtrosDocentes,
            'roles' => Role::query()->orderBy('nombre_visible')->get(['id', 'nombre', 'nombre_visible']),
            'rolEstudiante' => Role::query()->where('nombre', 'estudiante')->first(['id', 'nombre_visible']),
            'rolDocente' => Role::query()->where('nombre', 'docente_materia')->first(['id', 'nombre_visible']),
            'rolesDocentes' => Role::query()->whereIn('nombre', ['docente_lider', 'docente_materia'])->orderBy('nombre_visible')->get(['id', 'nombre', 'nombre_visible']),
            'carreras' => $carreras,
            'grupos' => GrupoAcademico::query()
                ->with('carrera:id,clave')
                ->when($periodoSeleccionado, fn ($query) => $query->where('periodo_id', $periodoSeleccionado))
                ->when($request->user()->hasRole('docente_lider'), fn ($query) => $query->conMateriaLiderDelDocente((int) $request->user()->id))
                ->orderBy('grado')
                ->orderBy('grupo')
                ->get(['id', 'carrera_id', 'nombre', 'grado', 'grupo']),
            'periodos' => Periodo::query()->orderByDesc('fecha_inicio')->get(['id', 'nombre', 'estado']),
            'gruposPaginacion' => $gruposPaginacion,
            'grupoSeleccionado' => $grupoSeleccionado,
            'seccionActiva' => in_array($request->query('seccion'), ['alumnos', 'docentes'], true)
                && $request->user()->hasRole('coordinacion')
                ? $request->query('seccion')
                : 'alumnos',
            'esCoordinacion' => $request->user()->hasRole('coordinacion'),
            'vistaPrevia' => $request->query('preview')
                ? $request->session()->get('preview_alumnos.'.$request->query('preview'))
                : null,
            'tokenVistaPrevia' => $request->query('preview'),
            'metricasUsuarios' => [
                ['label' => 'Usuarios', 'value' => (string) $totalUsuarios],
                ['label' => 'Estudiantes', 'value' => (string) $totalEstudiantes],
                ['label' => 'Docentes / asesores', 'value' => (string) $totalDocentes],
                ['label' => 'Direccion', 'value' => (string) $usuariosDireccion->count()],
            ],
        ];
    }

    private function generarContrasenaTemporal(): string
    {
        return 'Tmp-'.Str::upper(Str::random(4)).'-'.random_int(1000, 9999);
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    private function leerFilasCargaAlumnos(string $ruta): array
    {
        $documento = IOFactory::load($ruta);
        $hoja = $documento->getSheetByName('Carga alumnos') ?? $documento->getActiveSheet();

        return $hoja->toArray(null, true, true, false);
    }

    /**
     * @param  array<int, array<int, mixed>>  $filas
     * @return array{0: array<string, int>, 1: int}
     */
    private function detectarEncabezados(array $filas): array
    {
        foreach ($filas as $indice => $fila) {
            $encabezados = [];

            foreach ($fila as $columna => $valor) {
                $normalizado = $this->normalizarColumna((string) $valor);

                if ($normalizado !== '') {
                    $encabezados[$normalizado] = $columna;
                }
            }

            if (array_key_exists('matricula', $encabezados)) {
                return [$encabezados, $indice + 1];
            }
        }

        throw ValidationException::withMessages([
            'archivo' => 'No se encontro la fila de encabezados. Verifica que exista la columna matricula.',
        ]);
    }

    /**
     * @param  array<int, mixed>  $fila
     * @param  array<string, int>  $encabezados
     * @return array<string, string>
     */
    private function mapearFilaCarga(array $fila, array $encabezados): array
    {
        return collect($encabezados)
            ->mapWithKeys(fn (int $columna, string $nombre) => [$nombre => trim((string) ($fila[$columna] ?? ''))])
            ->all();
    }

    /**
     * @param  array<string, string>  $fila
     */
    private function filaVacia(array $fila): bool
    {
        return collect($fila)->filter(fn (string $valor) => $valor !== '')->isEmpty();
    }

    private function normalizarColumna(string $valor): string
    {
        $valor = Str::of($valor)
            ->ascii()
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', '_')
            ->trim('_')
            ->toString();

        return match ($valor) {
            'cedula', 'cedula_alumno', 'numero_cedula' => 'matricula',
            'nombre', 'nombre_completo', 'alumno', 'nomina' => 'nombre_completo',
            'correo', 'correo_electronico', 'email' => 'correo_electronico',
            'carrera', 'clave_carrera' => 'clave_carrera',
            default => $valor,
        };
    }
}
