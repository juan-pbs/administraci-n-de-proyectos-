<?php

namespace App\Http\Controllers\Modulos;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Modulos\Soporte\AutorizaDireccion;
use App\Models\ApartadoGuia;
use App\Models\Asignatura;
use App\Models\FirmaApartadoGuia;
use App\Models\GuiaIntegradora;
use App\Models\Periodo;
use App\Models\User;
use App\Soporte\SistemaInterfaz;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

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
            ...$this->datos(),
        ]);
    }

    public function guardar(Request $request): RedirectResponse
    {
        $this->autorizarDireccion($request);

        $datos = $request->validate([
            'periodo_id' => ['required', 'exists:periodos,id'],
            'periodo_fin_id' => ['nullable', 'exists:periodos,id'],
            'asignatura_id' => ['nullable', 'exists:asignaturas,id'],
            'nombre' => ['required', 'string', 'max:255'],
            'cuatrimestre' => ['nullable', 'string', 'max:80'],
            'competencias_evaluar' => ['nullable', 'string'],
            'objetivo_aprendizaje' => ['nullable', 'string'],
            'version' => ['required', 'string', 'max:30'],
            'estado' => ['required', 'string', 'max:30'],
        ]);

        GuiaIntegradora::query()->create([...$datos, 'creado_por' => $request->user()->id]);

        return redirect()->route('modulos.show', 'guias')->with('estado', 'Guía integradora guardada correctamente.');
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

    /**
     * @return array<string, mixed>
     */
    private function datos(): array
    {
        $guias = GuiaIntegradora::query()
            ->with([
                'periodo:id,nombre',
                'periodoFin:id,nombre',
                'asignatura:id,nombre,clave',
                'apartados.asignaturasContribuyentes:id,nombre,clave',
                'apartados.firmas.asignatura:id,nombre,clave',
                'apartados.firmas.docente:id,nombre,matricula',
            ])
            ->withCount(['apartados as apartados_count'])
            ->orderByDesc('creado_en')
            ->get();

        $apartados = ApartadoGuia::query()
            ->with('guiaIntegradora:id,nombre,version')
            ->orderBy('guia_integradora_id')
            ->orderBy('orden')
            ->get(['id', 'guia_integradora_id', 'orden', 'titulo']);

        return [
            'guias' => $guias,
            'periodos' => Periodo::query()->orderByDesc('fecha_inicio')->get(['id', 'nombre', 'estado']),
            'asignaturas' => Asignatura::query()->with('carrera:id,clave')->orderBy('nombre')->get(['id', 'carrera_id', 'nombre', 'clave']),
            'apartados' => $apartados,
            'docentes' => User::query()->whereHas('role', fn ($query) => $query->where('nombre', 'docente_asesor'))->orderBy('nombre')->get(['id', 'nombre', 'matricula']),
            'metricasGuias' => [
                ['label' => 'Guías', 'value' => (string) $guias->count()],
                ['label' => 'Publicadas', 'value' => (string) $guias->where('estado', 'publicada')->count()],
                ['label' => 'Apartados', 'value' => (string) $guias->sum('apartados_count')],
            ],
        ];
    }
}
