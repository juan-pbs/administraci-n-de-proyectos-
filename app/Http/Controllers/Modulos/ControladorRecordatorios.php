<?php

namespace App\Http\Controllers\Modulos;

use App\Http\Controllers\Controller;
use App\Models\AvisoAsignacion;
use App\Models\AvisoRevision;
use App\Models\PlantillaRecordatorio;
use App\Models\Proyecto;
use App\Servicios\AvisosAsignaciones;
use App\Servicios\AvisosRevisiones;
use App\Servicios\PlazosProyecto;
use App\Servicios\TextoRecordatorios;
use App\Soporte\SistemaInterfaz;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ControladorRecordatorios extends Controller
{
    public function __construct(private PlazosProyecto $plazos) {}

    public function mostrar(Request $request, TextoRecordatorios $textos)
    {
        $docente = $this->docente($request);
        $consulta = $this->proyectosDelLider($docente->id);
        $proyectos = (clone $consulta)->orderBy('titulo')->paginate(15);
        $bloqueos = $proyectos->getCollection()->mapWithKeys(fn (Proyecto $proyecto) => [$proyecto->id => $this->motivoBloqueo($proyecto)]);
        $puedeEnviarATodos = $bloqueos->containsStrict(null)
            || ($proyectos->total() > $proyectos->count() && $consulta->lazyById(50)->contains(fn (Proyecto $proyecto) => $this->motivoBloqueo($proyecto) === null));
        $ultimoEnvio = $request->session()->get('recordatorios.lote_'.$docente->id);
        $plantillas = [];
        foreach (TextoRecordatorios::TIPOS as $tipo => $nombre) {
            $plantillas[$tipo] = $textos->plantilla($docente->id, $tipo);
        }

        return response()->view('modulos.docente-lider.recordatorios', [
            'proyectos' => $proyectos, 'plantillas' => $plantillas, 'tipos' => TextoRecordatorios::TIPOS,
            'bloqueos' => $bloqueos, 'puedeEnviarATodos' => $puedeEnviarATodos,
            'resultadoEnvio' => $this->resultadoEnvio($ultimoEnvio['lote'] ?? null),
            'proyectoDelEnvio' => $ultimoEnvio['proyecto'] ?? null,
            'navegacion' => SistemaInterfaz::navegacionPara('docente_lider'), 'roleName' => $docente->role->nombre_visible,
        ])->header('Cache-Control', 'no-store, private');
    }

    public function guardar(Request $request)
    {
        $docente = $this->docente($request);
        $datos = $request->validate([
            'tipo' => ['required', Rule::in(array_keys(TextoRecordatorios::TIPOS))],
            'texto' => ['required', 'string', 'max:3000'],
        ], [
            'tipo.in' => 'Selecciona un tipo de recordatorio válido.',
            'texto.required' => 'Escribe el texto del correo.',
            'texto.max' => 'El texto del correo admite hasta 3000 caracteres.',
        ]);
        PlantillaRecordatorio::query()->updateOrCreate(
            ['docente_id' => $docente->id, 'tipo' => $datos['tipo']],
            ['texto' => trim($datos['texto'])],
        );

        return redirect()->route('docente-lider.recordatorios')->with('status', 'Formato de recordatorio guardado.');
    }

    public function enviar(Request $request, Proyecto $proyecto, AvisosAsignaciones $avisos, AvisosRevisiones $revisiones)
    {
        $docente = $this->docente($request);
        abort_unless($proyecto->estado === 'en_proceso' && $proyecto->equipo?->grupoAcademico
            && $proyecto->equipo->grupoAcademico()->conMateriaLiderDelDocente($docente->id)->exists(), 403);
        if ($motivo = $this->motivoBloqueo($proyecto)) {
            return redirect()->route('docente-lider.recordatorios')->withErrors(['recordatorios' => $motivo]);
        }

        $lote = (string) Str::uuid();
        $estudiantes = $avisos->enviarManualmente($proyecto, $lote);
        $docentes = $revisiones->paraProyecto($proyecto, true, $lote);
        $request->session()->put('recordatorios.lote_'.$docente->id, ['lote' => $lote, 'proyecto' => $proyecto->id]);

        return redirect()->route('docente-lider.recordatorios', $request->integer('page') > 1 ? ['page' => $request->integer('page')] : [])->with('status', $estudiantes + $docentes
            ? "Se programaron {$estudiantes} recordatorios de entrega para estudiantes y {$docentes} avisos de revisión para docentes."
            : 'No hay recordatorios nuevos para enviar. Se omiten entregas completas, revisiones realizadas y avisos ya solicitados hoy.');
    }

    public function enviarTodos(Request $request, AvisosAsignaciones $avisos, AvisosRevisiones $revisiones)
    {
        $docente = $this->docente($request);
        $lote = (string) Str::uuid();
        $estudiantes = 0;
        $docentes = 0;
        $this->proyectosDelLider($docente->id)
            ->chunkById(50, function ($proyectos) use ($avisos, $revisiones, $lote, &$estudiantes, &$docentes): void {
                foreach ($proyectos as $proyecto) {
                    if ($this->motivoBloqueo($proyecto) !== null) {
                        continue;
                    }

                    $estudiantes += $avisos->enviarManualmente($proyecto, $lote);
                    $docentes += $revisiones->paraProyecto($proyecto, true, $lote);
                }
            });
        $request->session()->put('recordatorios.lote_'.$docente->id, ['lote' => $lote]);

        return redirect()->route('docente-lider.recordatorios')->with('status', $estudiantes + $docentes
            ? "Se programaron {$estudiantes} recordatorios de entrega para estudiantes y {$docentes} avisos de revisión para docentes de tus proyectos."
            : 'No hay recordatorios nuevos para enviar en tus proyectos.');
    }

    private function proyectosDelLider(int $docenteId): Builder
    {
        return Proyecto::query()->where('estado', 'en_proceso')
            ->whereHas('equipo.grupoAcademico', fn ($q) => $q->conMateriaLiderDelDocente($docenteId))
            ->with(['equipo.grupoAcademico.carrera', 'guiaIntegradora.apartados', 'guiaIntegradora.periodo', 'guiaIntegradora.periodoFin', 'cierre.prorrogas']);
    }

    private function motivoBloqueo(Proyecto $proyecto): ?string
    {
        $proyecto->loadMissing(['equipo.grupoAcademico.carrera', 'guiaIntegradora.apartados', 'guiaIntegradora.periodo', 'guiaIntegradora.periodoFin', 'cierre.prorrogas']);
        $guia = $proyecto->guiaIntegradora;

        if ($proyecto->equipo->estado !== 'activo') {
            return 'El equipo está inactivo.';
        }
        if (! $proyecto->equipo->grupoAcademico->disponible()) {
            return 'El grupo o la carrera está inactivo.';
        }
        if ($guia->apartados->contains(fn ($apartado) => $this->plazos->habilitado($proyecto, $apartado))) {
            return null;
        }
        if ($cierre = $this->plazos->cierre($proyecto)) {
            return $cierre->estado === 'prorroga' ? 'La prórroga terminó o no tiene actividades abiertas.' : 'El proyecto está cerrado y no tiene una prórroga vigente.';
        }
        if ($guia->estado === 'cerrada') {
            return 'La guía está cerrada.';
        }
        if ($guia->periodo->estado === 'cerrado' || $guia->periodoFin?->estado === 'cerrado') {
            return 'El periodo académico está cerrado.';
        }
        if ($guia->estado !== 'publicada') {
            return 'La guía aún no se ha publicado.';
        }

        return 'La guía no tiene actividades habilitadas.';
    }

    private function resultadoEnvio(?string $lote): ?array
    {
        if ($lote === null) {
            return null;
        }

        $conteos = [];
        foreach (['Estudiantes' => AvisoAsignacion::class, 'Docentes' => AvisoRevision::class] as $destinatarios => $modelo) {
            $estados = $modelo::query()->where('lote_manual', $lote)
                ->selectRaw('estado, count(*) as cantidad')->groupBy('estado')->pluck('cantidad', 'estado');
            $conteos[$destinatarios] = [
                'enviados' => (int) ($estados['enviado'] ?? 0),
                'no_enviados' => (int) ($estados['fallido'] ?? 0) + (int) ($estados['cancelado'] ?? 0),
                'pendientes' => (int) ($estados['pendiente'] ?? 0),
            ];
        }

        return $conteos;
    }

    private function docente(Request $request)
    {
        $docente = $request->user()->loadMissing('role');
        abort_unless($docente->hasRole('docente_lider') && $docente->estado === 'activo', 403);

        return $docente;
    }
}
