<?php

namespace App\Http\Controllers\Modulos;

use App\Http\Controllers\Controller;
use App\Models\ApartadoGuia;
use App\Models\ArchivoEntrega;
use App\Models\Entrega;
use App\Models\Equipo;
use App\Models\ProductoCodigo;
use App\Models\Proyecto;
use App\Soporte\SistemaInterfaz;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\File;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ControladorEstudiante extends Controller
{
    public function proyecto(Request $request): View
    {
        [$estudiante, $equipo, $proyecto] = $this->contexto($request);
        $equipo?->loadMissing(['integrantes:id,nombre,matricula', 'grupoAcademico.carrera:id,clave,nombre', 'grupoAcademico.liderProyecto:id,nombre,correo']);
        $proyecto?->loadMissing(['guiaIntegradora.apartados', 'docentes:id,nombre,correo', 'asignaturas:id,nombre,clave']);

        return view('modulos.estudiante.proyecto', $this->base($estudiante, compact('equipo', 'proyecto')));
    }

    public function entregas(Request $request): View
    {
        [$estudiante, $equipo, $proyecto] = $this->contexto($request);
        $apartados = $proyecto?->guiaIntegradora?->apartados()
            ->with(['firmas.docente:id,nombre'])
            ->orderBy('orden')->get() ?? collect();
        $entregas = $proyecto
            ? Entrega::query()->with(['archivos', 'entregadoPor:id,nombre,matricula', 'revisiones' => fn ($query) => $query->with('comentarios.autor:id,nombre')->latest('revisado_en')])
                ->where('proyecto_id', $proyecto->id)->orderByDesc('version')->get()->groupBy('apartado_guia_id')
            : collect();

        return view('modulos.estudiante.entregas', $this->base($estudiante, compact('equipo', 'proyecto', 'apartados', 'entregas')));
    }

    public function guardarEntrega(Request $request, ApartadoGuia $apartado): RedirectResponse
    {
        [$estudiante, $equipo, $proyecto] = $this->contexto($request);
        abort_unless($equipo && $proyecto && $apartado->guia_integradora_id === $proyecto->guia_integradora_id, 403);
        $this->validarPlazo($apartado);
        $datos = $request->validate([
            'archivos' => ['required', 'array', 'min:1', 'max:10'],
            'archivos.*' => ['required', File::types(['pdf', 'doc', 'docx', 'xlsx', 'png', 'jpg', 'jpeg', 'zip', 'rar', '7z', 'tar', 'gz', 'sql', 'txt', 'md'])->max('150mb')],
        ]);

        DB::transaction(function () use ($datos, $estudiante, $equipo, $proyecto, $apartado): void {
            $version = (int) Entrega::query()->where('proyecto_id', $proyecto->id)->where('apartado_guia_id', $apartado->id)->max('version') + 1;
            $entrega = Entrega::query()->create([
                'proyecto_id' => $proyecto->id, 'apartado_guia_id' => $apartado->id,
                'equipo_id' => $equipo->id, 'entregado_por_id' => $estudiante->id,
                'version' => $version, 'estado' => 'enviada', 'entregado_en' => now(),
            ]);
            foreach ($datos['archivos'] as $archivo) {
                $nombre = $archivo->getClientOriginalName();
                $ruta = $archivo->storeAs("entregas/{$entrega->id}", uniqid().'_'.$nombre);
                ArchivoEntrega::query()->create([
                    'entrega_id' => $entrega->id, 'nombre_original' => $nombre, 'ruta' => $ruta,
                    'tipo_archivo' => $archivo->getMimeType(), 'tamano' => $archivo->getSize(),
                ]);
            }
        });

        return back()->with('status', 'El avance se envió correctamente y quedó pendiente de revisión.');
    }

    public function codigo(Request $request): View
    {
        [$estudiante, $equipo, $proyecto] = $this->contexto($request);
        $apartadoCodigo = $proyecto?->guiaIntegradora?->apartados()->where('requiere_codigo', true)->orderBy('orden')->first();
        $producto = $proyecto ? ProductoCodigo::query()->with(['entrega.archivos', 'entrega.entregadoPor:id,nombre,matricula'])->where('proyecto_id', $proyecto->id)->latest('id')->first() : null;

        return view('modulos.estudiante.codigo', $this->base($estudiante, compact('equipo', 'proyecto', 'apartadoCodigo', 'producto')));
    }

    public function guardarCodigo(Request $request): RedirectResponse
    {
        [$estudiante, $equipo, $proyecto] = $this->contexto($request);
        abort_unless($equipo && $proyecto, 403);
        $apartado = $proyecto->guiaIntegradora->apartados()->where('requiere_codigo', true)->orderBy('orden')->firstOrFail();
        $this->validarPlazo($apartado);
        $datos = $request->validate([
            'repositorio_url' => ['nullable', 'url:http,https', 'max:500', 'required_without:archivos'],
            'version' => ['required', 'string', 'max:40'],
            'archivos' => ['nullable', 'array', 'max:10', 'required_without:repositorio_url'],
            'archivos.*' => ['required', File::types(['zip', 'rar', '7z', 'tar', 'gz', 'sql', 'md', 'txt'])->max('150mb')],
        ]);

        DB::transaction(function () use ($datos, $request, $estudiante, $equipo, $proyecto, $apartado): void {
            $versionEntrega = (int) Entrega::query()->where('proyecto_id', $proyecto->id)->where('apartado_guia_id', $apartado->id)->max('version') + 1;
            $entrega = Entrega::query()->create([
                'proyecto_id' => $proyecto->id, 'apartado_guia_id' => $apartado->id,
                'equipo_id' => $equipo->id, 'entregado_por_id' => $estudiante->id,
                'version' => $versionEntrega, 'estado' => 'enviada', 'entregado_en' => now(),
            ]);
            $primeraRuta = null;
            foreach ($request->file('archivos', []) as $archivo) {
                $nombre = $archivo->getClientOriginalName();
                $ruta = $archivo->storeAs("entregas/{$entrega->id}/codigo", uniqid().'_'.$nombre);
                $primeraRuta ??= $ruta;
                ArchivoEntrega::query()->create(['entrega_id' => $entrega->id, 'nombre_original' => $nombre, 'ruta' => $ruta, 'tipo_archivo' => $archivo->getMimeType(), 'tamano' => $archivo->getSize()]);
            }
            ProductoCodigo::query()->create([
                'proyecto_id' => $proyecto->id, 'entrega_id' => $entrega->id,
                'repositorio_url' => $datos['repositorio_url'] ?? null, 'archivo_fuente' => $primeraRuta, 'version' => $datos['version'],
            ]);
        });

        return back()->with('status', 'La entrega de código se registró correctamente.');
    }

    public function descargar(Request $request, ArchivoEntrega $archivo): BinaryFileResponse
    {
        [, $equipo] = $this->contexto($request);
        $archivo->loadMissing('entrega');
        abort_unless($equipo && $archivo->entrega->equipo_id === $equipo->id, 403);
        $ruta = Storage::path($archivo->ruta);
        if (! is_file($ruta)) {
            $ruta = storage_path('app/'.$archivo->ruta);
        }
        abort_unless(is_file($ruta), 404);
        return response()->download($ruta, $archivo->nombre_original);
    }

    private function contexto(Request $request): array
    {
        $estudiante = $request->user()->loadMissing('role');
        abort_unless($estudiante->hasRole('estudiante'), 403);
        $equipo = Equipo::query()->whereHas('integrantes', fn ($query) => $query->where('usuarios.id', $estudiante->id))->where('estado', 'activo')->latest('id')->first();
        $proyecto = $equipo?->proyectos()->with('guiaIntegradora')->latest('id')->first();
        return [$estudiante, $equipo, $proyecto];
    }

    private function base($estudiante, array $datos): array
    {
        return [...$datos, 'navegacion' => SistemaInterfaz::navegacionPara('estudiante'), 'roleName' => $estudiante->role->nombre_visible];
    }

    private function validarPlazo(ApartadoGuia $apartado): void
    {
        if ($apartado->fecha_limite?->isPast()) {
            abort(422, 'El plazo de esta actividad terminó y ya no admite cambios.');
        }
    }
}
