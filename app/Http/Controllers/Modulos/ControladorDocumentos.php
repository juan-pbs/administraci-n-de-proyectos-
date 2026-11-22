<?php

namespace App\Http\Controllers\Modulos;

use App\Http\Controllers\Controller;
use App\Models\ApartadoGuia;
use App\Models\AvisoCierre;
use App\Models\DocumentoFinal;
use App\Models\GuiaIntegradora;
use App\Models\Proyecto;
use App\Servicios\CierresProyectos;
use App\Servicios\DocumentosGuias;
use App\Soporte\SistemaInterfaz;
use App\Soporte\RegresoDocumentos;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ControladorDocumentos extends Controller
{
    public function previewGuia(Request $request, GuiaIntegradora $guia, DocumentosGuias $documentos)
    {
        abort_unless($request->user()->hasRole('coordinacion'), 403);

        return $this->pdf($documentos->pdf(['guia' => $documentos->cargarGuia($guia), 'proyecto' => null, 'filas' => [], 'pendientes' => []], 'VISTA PREVIA'), 'guia.pdf');
    }

    public function previewApartado(Request $request, DocumentosGuias $documentos)
    {
        abort_unless($request->user()->hasRole('coordinacion'), 403);
        $reglasApartado = [
            'orden' => ['required', 'integer', 'between:1,999'],
            'titulo' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string', 'max:20000'],
            'ponderacion' => ['required', 'numeric', 'decimal:0,2', 'between:0,100'],
            'fecha_limite' => ['nullable', 'date'],
            'requiere_codigo' => ['nullable', 'boolean'],
            'requiere_documento' => ['nullable', 'boolean'],
        ];
        $varios = $request->has('apartados');
        $reglas = ['guia_integradora_id' => ['required', 'exists:guias_integradoras,id']];
        if ($varios) {
            $reglas['apartados'] = ['required', 'array', 'min:1', 'max:20'];
            foreach ($reglasApartado as $campo => $regla) {
                $reglas["apartados.*.{$campo}"] = $campo === 'orden' ? [...$regla, 'distinct'] : $regla;
            }
        } else {
            $reglas = [...$reglas, ...$reglasApartado];
        }
        $datos = $request->validate($reglas);
        $guia = GuiaIntegradora::findOrFail($datos['guia_integradora_id']);
        $apartados = $varios ? $datos['apartados'] : [$datos];
        foreach ($apartados as $indice => $datosApartado) {
            if (! empty($datosApartado['fecha_limite']) && ! $guia->admiteFechaLimite($datosApartado['fecha_limite'])) {
                throw ValidationException::withMessages([
                    $varios ? "apartados.{$indice}.fecha_limite" : 'fecha_limite' => 'La fecha límite debe estar dentro de los periodos de la guía.',
                ]);
            }
        }
        $guia = $documentos->cargarGuia($guia);
        $vistaApartados = $guia->apartados;
        foreach ($apartados as $indice => $datosApartado) {
            $apartado = $vistaApartados->firstWhere('orden', (int) $datosApartado['orden']);
            $apartado = $apartado ? clone $apartado : new ApartadoGuia;
            $ruta = $varios ? "apartados.{$indice}" : '';
            $apartado->fill([
                ...$datosApartado,
                'requiere_codigo' => $request->boolean($ruta ? "{$ruta}.requiere_codigo" : 'requiere_codigo'),
                'requiere_documento' => $request->boolean($ruta ? "{$ruta}.requiere_documento" : 'requiere_documento'),
            ]);
            $apartado->loadMissing(['firmas.docente', 'asignaturasContribuyentes']);
            $vistaApartados = $vistaApartados->reject(fn ($a) => (int) $a->orden === (int) $datosApartado['orden'])->push($apartado);
        }
        $guia->setRelation('apartados', $vistaApartados);

        return $this->pdf($documentos->pdf(['guia' => $guia, 'proyecto' => null, 'filas' => [], 'pendientes' => []], 'CAMBIOS SIN GUARDAR'), 'preview.pdf');
    }

    public function mostrar(Request $request, Proyecto $proyecto, DocumentosGuias $documentos)
    {
        $this->autorizar($request, $proyecto);
        $usuario = $request->user()->loadMissing('role');
        $datos = $documentos->datos($proyecto, false);
        // Las imágenes nunca se entregan al HTML de la vista de estado.
        $datos['filas'] = collect($datos['filas'])->map(fn ($fila) => ['entrega' => $fila['entrega'], 'firmantes' => collect($fila['firmantes'])->map(fn ($f) => ['nombre' => $f['docente']?->nombre ?? $f['etiqueta'], 'firmada' => (bool) $f['revision']])->all()])->all();

        return response()->view('modulos.documentos.mostrar', [...$datos,
            'regreso' => RegresoDocumentos::destino($request, $proyecto),
            'cierre' => $proyecto->cierre,
            'puedeResolverCierre' => $usuario->hasRole('docente_lider') && $proyecto->equipo()->whereHas('grupoAcademico', fn ($q) => $q->conMateriaLiderDelDocente($usuario->id))->exists(),
            'documentos' => DocumentoFinal::query()->where('proyecto_id', $proyecto->id)->select(['id', 'created_at', 'sha256'])->latest('id')->get(),
            'navegacion' => SistemaInterfaz::navegacionPara($usuario->role->nombre), 'roleName' => $usuario->role->nombre_visible,
        ])->header('Cache-Control', 'no-store, private');
    }

    public function previewProyecto(Request $request, Proyecto $proyecto, DocumentosGuias $documentos)
    {
        $this->autorizar($request, $proyecto);

        return $this->pdf($documentos->pdf($documentos->datos($proyecto)), 'proyecto-'.$proyecto->id.'-borrador.pdf');
    }

    public function generar(Request $request, Proyecto $proyecto, DocumentosGuias $documentos)
    {
        $this->autorizar($request, $proyecto);
        $documento = DB::transaction(function () use ($request, $proyecto, $documentos) {
            app(CierresProyectos::class)->bloquearPeriodos($proyecto);
            $proyecto = Proyecto::query()->whereKey($proyecto->id)->lockForUpdate()->firstOrFail();
            $cierre = $proyecto->cierre()->lockForUpdate()->first();
            if ($cierre?->estado === 'cerrado' && $cierre->pendientes) {
                throw ValidationException::withMessages(['documento' => 'El proyecto se cerró con pendientes; conserva su constancia de cierre.']);
            }
            $datos = $documentos->datos($proyecto);
            if ($datos['pendientes']) {
                throw ValidationException::withMessages(['documento' => 'Completa todas las entregas y aprobaciones firmadas antes de generar el PDF final.']);
            }
            $huella = $documentos->huella($datos);
            $existente = DocumentoFinal::query()->where('proyecto_id', $proyecto->id)->where('huella', $huella)->first();
            if ($existente) {
                $this->completarCierre($proyecto);

                return $existente;
            }
            $pdf = $documentos->pdf([...$datos, 'emitido_por' => $request->user()->nombre, 'emitido_en' => now()], 'FINAL');

            $documento = DocumentoFinal::query()->create(['proyecto_id' => $proyecto->id, 'generado_por' => $request->user()->id, 'huella' => $huella, 'sha256' => hash('sha256', $pdf), 'pdf' => base64_encode($pdf)]);
            $this->completarCierre($proyecto);

            return $documento;
        });

        return redirect()->route('documentos.descargar', ['proyecto' => $proyecto, 'documento' => $documento]);
    }

    public function descargar(Request $request, Proyecto $proyecto, DocumentoFinal $documento)
    {
        $this->autorizar($request, $proyecto);
        abort_unless($documento->proyecto_id === $proyecto->id, 404);
        $pdf = base64_decode($documento->pdf, true);
        abort_unless($pdf !== false && hash_equals($documento->sha256, hash('sha256', $pdf)), 500, 'No se pudo verificar el documento.');

        return $this->pdf($pdf, 'proyecto-'.$proyecto->id.'-final-'.$documento->id.'.pdf', true);
    }

    private function autorizar(Request $request, Proyecto $proyecto): void
    {
        $usuario = $request->user()->loadMissing('role');
        $permitido = $usuario->hasRole('coordinacion')
            || ($usuario->hasRole('estudiante') && $proyecto->equipo()->whereHas('integrantes', fn ($q) => $q->where('usuarios.id', $usuario->id))->exists())
            || ($usuario->hasRole('docente_lider') && $proyecto->equipo()->whereHas('grupoAcademico', fn ($q) => $q->conMateriaLiderDelDocente($usuario->id))->exists())
            || ($usuario->hasAnyRole('docente_materia', 'docente_lider') && ($proyecto->docentes()->where('usuarios.id', $usuario->id)->exists()
                || $proyecto->asignaturas()->wherePivot('docente_id', $usuario->id)->exists()));
        abort_unless($permitido, 403);
    }

    private function completarCierre(Proyecto $proyecto): void
    {
        $cierre = $proyecto->cierre()->first();
        if ($cierre) {
            $cierre->update(['estado' => 'completo', 'pendientes' => []]);
            AvisoCierre::where('cierre_proyecto_id', $cierre->id)->where('estado', 'pendiente')->update(['estado' => 'cancelado']);
        }
    }

    private function pdf(string $bytes, string $nombre, bool $descargar = false)
    {
        return response($bytes)->withHeaders(['Content-Type' => 'application/pdf', 'Content-Disposition' => ($descargar ? 'attachment' : 'inline').'; filename="'.$nombre.'"',
            'Cache-Control' => 'no-store, private', 'X-Content-Type-Options' => 'nosniff', 'X-Frame-Options' => 'SAMEORIGIN', 'Referrer-Policy' => 'no-referrer']);
    }
}
