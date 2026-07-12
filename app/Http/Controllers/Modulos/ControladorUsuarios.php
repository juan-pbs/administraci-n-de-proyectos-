<?php

namespace App\Http\Controllers\Modulos;

use App\Correos\ContrasenaInicial;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Modulos\Soporte\AutorizaDireccion;
use App\Models\Carrera;
use App\Models\GrupoAcademico;
use App\Models\Role;
use App\Models\User;
use App\Soporte\SistemaInterfaz;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
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
}
