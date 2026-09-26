<!doctype html>
<html lang="es"><head><meta charset="UTF-8"><title>Guía integradora</title>
<style>
    @page { margin: 28px 30px 42px; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #24344b; }
    h1 { font-size: 17px; color: #0D376D; margin: 5px 0; }
    .header { border-bottom: 3px solid #0D376D; padding-bottom: 10px; margin-bottom: 12px; }
    .logo { width: 125px; float: left; margin-right: 20px; }
    .meta { line-height: 1.6; }
    .mode { color: #8d4c35; font-weight: bold; margin: 10px 0; }
    table { border-collapse: collapse; width: 100%; table-layout: fixed; }
    th, td { border: 1px solid #9eaaa8; padding: 7px; vertical-align: top; overflow-wrap: break-word; }
    th, .section { background: #805442; color: white; }
    .section { font-weight: bold; }
    .signature { width: 170px; max-height: 50px; }
    .slot { border-bottom: 1px solid #b6b6b6; padding: 5px 0; }
    .empty { height: 30px; }
    .small { font-size: 7px; color: #536273; }
    .footer { position: fixed; bottom: -28px; font-size: 7px; color: #536273; }
</style></head><body>
<div class="header"><img class="logo" src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('assets/utvm/utvm-logo.png'))) }}" alt="UTVM"><h1>Guía de proyecto integrador</h1><div class="meta">{{ $guia->asignatura?->carrera?->nombre }}<br>{{ $guia->nombre }} · v{{ $guia->version }}<br>{{ $guia->periodo?->nombre }} · {{ is_numeric($guia->cuatrimestre) ? 'Cuatrimestre '.$guia->cuatrimestre : $guia->cuatrimestre }}</div></div>
<div class="mode">{{ $modo }} @if($modo !== 'FINAL') · Documento de consulta, sin emisión definitiva @endif</div>
@if($proyecto)
<div class="meta"><strong>Proyecto:</strong> {{ $proyecto->titulo }}<br><strong>Equipo:</strong> {{ $proyecto->equipo->nombre }} · Grupo {{ $proyecto->equipo->grupoAcademico->grado }}{{ $proyecto->equipo->grupoAcademico->grupo }}<br><strong>Integrantes:</strong> {{ $proyecto->equipo->integrantes->map(fn($u) => $u->nombre.' ('.$u->matricula.')')->implode(', ') }}</div>
@endif
<p><strong>Competencias:</strong> {{ $guia->competencias_evaluar ?: 'Sin especificar' }}<br><strong>Objetivo de aprendizaje:</strong> {{ $guia->objetivo_aprendizaje ?: 'Sin especificar' }}</p>
<table><thead><tr><th style="width:42%">Contenido</th><th style="width:26%">Asignatura(s) que contribuyen</th><th style="width:32%">Firmas</th></tr></thead><tbody>
@foreach($guia->apartados->sortBy('orden') as $apartado)
@php
    $contenidos = app(\App\Servicios\DocumentosGuias::class)->renglones($apartado->descripcion);
    $firmasPagina = array_chunk($proyecto ? $filas[$apartado->id]['firmantes'] : ($apartado->requiere_codigo ? [] : $apartado->firmas->sortBy('orden')->all()), 3);
    $segmentos = max(count($contenidos), count($firmasPagina), 1);
@endphp
@for($segmento = 0; $segmento < $segmentos; $segmento++)
<tr><td colspan="3" style="padding:0; border:none"><table><colgroup><col style="width:42%"><col style="width:26%"><col style="width:32%"></colgroup>
<tr style="height:0;line-height:0;font-size:0"><td style="width:42%;height:0;padding:0;border:none"></td><td style="width:26%;height:0;padding:0;border:none"></td><td style="width:32%;height:0;padding:0;border:none"></td></tr>
<tr><td colspan="3" class="section">{{ $apartado->orden }}. {{ $apartado->titulo }}{{ $segmento > 0 ? ' (continuación)' : '' }}</td></tr>
<tr>
    <td style="width:42%">@foreach($contenidos[$segmento] ?? [] as $linea)<div>{{ $linea }}</div>@endforeach
        <p class="small">Ponderación: {{ $apartado->ponderacion }}%<br>Fecha límite: {{ ($proyecto ? $filas[$apartado->id]['fecha_limite'] : $apartado->fecha_limite)?->format('d/m/Y H:i') ?? 'Sin fecha' }} ({{ config('app.timezone') }})
        @if($proyecto)<br>Entrega: {{ $filas[$apartado->id]['entrega'] ? 'Versión '.$filas[$apartado->id]['entrega']->version : 'Pendiente' }}@endif</p>
    </td>
    <td style="width:26%">@forelse($apartado->asignaturasContribuyentes as $materia)<div>• {{ $materia->nombre }}</div>@empty<div>{{ $guia->asignatura?->nombre ?? 'Por definir' }}</div>@endforelse</td>
    <td style="width:32%">
    @if($proyecto)
        @forelse($firmasPagina[$segmento] ?? [] as $firmante)
        <div class="slot">@if($firmante['imagen'])<img class="signature" src="{{ $firmante['imagen'] }}" alt="Firma autorizada">@else<div class="empty"></div>@endif
            <div>{{ $firmante['docente']?->nombre ?? 'Docente por asignar' }}</div><div class="small">{{ $firmante['etiqueta'] }}<br>{{ $firmante['revision'] ? 'Aprobada · '.$firmante['revision']->firmado_en->format('d/m/Y H:i') : 'Pendiente de aprobación' }}</div>
        </div>
        @empty<div class="empty"></div><span class="small">Docente por configurar</span>@endforelse
    @else
        @forelse($firmasPagina[$segmento] ?? [] as $firma)<div class="slot"><div class="empty"></div>{{ $firma->docente?->nombre ?? 'Docente por asignar' }}<div class="small">{{ $firma->etiqueta }}</div></div>@empty<div class="empty"></div><span class="small">{{ $segmento > 0 ? 'Continuación del contenido' : ($apartado->requiere_codigo ? 'Docente de materia líder' : 'Docente por configurar') }}</span>@endforelse
    @endif
    </td>
</tr>
</table></td></tr>
@endfor
@endforeach
</tbody></table>
@if($modo === 'FINAL')<p class="small">Emitido por {{ $emitido_por }} el {{ $emitido_en->format('d/m/Y H:i') }}. Las firmas corresponden a las versiones aprobadas indicadas.</p>@endif
<div class="footer">UTVM · Guía integradora · {{ $modo }} · Uso académico · No autoriza el uso de firmas en otros documentos.</div>
</body></html>
