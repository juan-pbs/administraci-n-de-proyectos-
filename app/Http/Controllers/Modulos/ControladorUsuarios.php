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
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Throwable;
use Illuminate\Support\Str;

class ControladorUsuarios extends Controller
{
    use AutorizaDireccion;

    public function mostrar(Request $request): View
    {
        $usuario = $request->user()->loadMissing('role');
        $rol = $usuario->role?->nombre ?? 'estudiante';

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
        $this->autorizarDireccion($request);

        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'matricula' => ['required', 'string', 'max:50', 'unique:usuarios,matricula'],
            'correo' => ['required', 'email', 'max:255', 'unique:usuarios,correo'],
            'rol_id' => ['required', 'exists:roles,id'],
            'carrera_id' => ['nullable', 'exists:carreras,id'],
            'grupo_academico_id' => ['nullable', 'exists:grupos_academicos,id'],
        ]);

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

    public function actualizarCarreraDocente(Request $request): RedirectResponse
    {
        $this->autorizarDireccion($request);

        $datos = $request->validate([
            'docente_id' => ['required', 'exists:usuarios,id'],
            'carrera_id' => ['required', 'exists:carreras,id'],
        ]);

        $docente = User::query()
            ->whereKey($datos['docente_id'])
            ->whereHas('role', fn ($query) => $query->where('nombre', 'docente_asesor'))
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
        $this->autorizarDireccion($request);

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

        foreach (array_slice($filas, $filaInicio) as $indice => $fila) {
            $numeroFila = $filaInicio + $indice + 1;
            $datosFila = $this->mapearFilaCarga($fila, $encabezados);

            if ($this->filaVacia($datosFila)) {
                continue;
            }

            foreach ($obligatorias as $columna) {
                if (($datosFila[$columna] ?? '') === '') {
                    $errores[] = "Fila {$numeroFila}: falta {$columna}.";
                }
            }

            if (count($errores) >= 8) {
                break;
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

            $grupoAcademico = GrupoAcademico::query()->firstOrCreate(
                [
                    'periodo_id' => $periodo->id,
                    'carrera_id' => $carrera->id,
                    'grado' => $grado,
                    'grupo' => $grupoLetra,
                ],
                ['nombre' => $grado.$grupoLetra],
            );

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
                try {
                    Mail::to($alumno->correo)->send(new ContrasenaInicial(
                        nombre: $alumno->nombre,
                        matricula: $alumno->matricula,
                        contrasenaTemporal: $contrasenaTemporal,
                    ));
                } catch (Throwable) {
                    $resumen['correos_fallidos']++;
                }
            }

            if (($datosFila['equipo'] ?? '') !== '') {
                $equipo = Equipo::query()->firstOrCreate(
                    ['grupo_academico_id' => $grupoAcademico->id, 'nombre' => (string) $datosFila['equipo']],
                    ['estado' => 'activo'],
                );

                if ($equipo->wasRecentlyCreated) {
                    $resumen['equipos']++;
                }

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
        $filtrosAlumnos = [
            'busqueda' => trim((string) $request->query('busqueda_alumnos', '')),
            'carrera_id' => $request->query('carrera_alumnos'),
            'grupo_academico_id' => $request->query('grupo_alumnos'),
            'estado' => $request->query('estado_alumnos'),
        ];

        $filtrosDocentes = [
            'busqueda' => trim((string) $request->query('busqueda_docentes', '')),
            'carrera_id' => $request->query('carrera_docentes'),
            'estado' => $request->query('estado_docentes'),
        ];

        $alumnos = User::query()
            ->with(['role:id,nombre,nombre_visible', 'carrera:id,clave,nombre', 'grupoAcademico:id,nombre,grado,grupo'])
            ->whereHas('role', fn ($query) => $query->where('nombre', 'estudiante'));

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

        if ($filtrosAlumnos['carrera_id']) {
            $alumnos->where('carrera_id', $filtrosAlumnos['carrera_id']);
        }

        if ($filtrosAlumnos['grupo_academico_id']) {
            $alumnos->where('grupo_academico_id', $filtrosAlumnos['grupo_academico_id']);
        }

        if ($filtrosAlumnos['estado']) {
            $alumnos->where('estado', $filtrosAlumnos['estado']);
        }

        $docentes = User::query()
            ->with(['role:id,nombre,nombre_visible', 'carrera:id,clave,nombre'])
            ->whereHas('role', fn ($query) => $query->where('nombre', 'docente_asesor'));

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

        $alumnosPaginados = $alumnos
            ->orderBy('nombre')
            ->paginate(12, ['*'], 'pagina_alumnos')
            ->withQueryString();

        $docentesPaginados = $docentes
            ->orderBy('nombre')
            ->paginate(8, ['*'], 'pagina_docentes')
            ->withQueryString();

        $usuariosDireccion = User::query()
            ->whereHas('role', fn ($query) => $query->where('nombre', 'direccion_coordinacion'))
            ->orderBy('nombre')
            ->get();

        $totalUsuarios = User::query()->count();
        $totalEstudiantes = User::query()->whereHas('role', fn ($query) => $query->where('nombre', 'estudiante'))->count();
        $totalDocentes = User::query()->whereHas('role', fn ($query) => $query->where('nombre', 'docente_asesor'))->count();

        return [
            'alumnos' => $alumnosPaginados,
            'docentes' => $docentesPaginados,
            'usuariosDireccion' => $usuariosDireccion,
            'filtrosAlumnos' => $filtrosAlumnos,
            'filtrosDocentes' => $filtrosDocentes,
            'roles' => Role::query()->orderBy('nombre_visible')->get(['id', 'nombre', 'nombre_visible']),
            'rolEstudiante' => Role::query()->where('nombre', 'estudiante')->first(['id', 'nombre_visible']),
            'rolDocente' => Role::query()->where('nombre', 'docente_asesor')->first(['id', 'nombre_visible']),
            'carreras' => Carrera::query()->orderBy('nombre')->get(['id', 'nombre', 'clave']),
            'grupos' => GrupoAcademico::query()->with('carrera:id,clave')->orderBy('grado')->orderBy('grupo')->get(['id', 'carrera_id', 'nombre', 'grado', 'grupo']),
            'periodos' => Periodo::query()->orderByDesc('fecha_inicio')->get(['id', 'nombre', 'estado']),
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
     * @param array<int, array<int, mixed>> $filas
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
     * @param array<int, mixed> $fila
     * @param array<string, int> $encabezados
     * @return array<string, string>
     */
    private function mapearFilaCarga(array $fila, array $encabezados): array
    {
        return collect($encabezados)
            ->mapWithKeys(fn (int $columna, string $nombre) => [$nombre => trim((string) ($fila[$columna] ?? ''))])
            ->all();
    }

    /**
     * @param array<string, string> $fila
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
            'nombre', 'nombre_completo', 'alumno' => 'nombre_completo',
            'correo', 'correo_electronico', 'email' => 'correo_electronico',
            'carrera', 'clave_carrera' => 'clave_carrera',
            default => $valor,
        };
    }
}
