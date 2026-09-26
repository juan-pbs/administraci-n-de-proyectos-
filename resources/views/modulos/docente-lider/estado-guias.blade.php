<x-contenedor-aplicacion title="Estado de las guías" active="estado-guias" :navegacion="$navegacion" :role-name="$roleName">
    <h2 class="text-2xl font-bold text-[#0D376D]">Estado de las guías</h2>
    <p class="mt-2 max-w-4xl text-sm text-slate-600">Consulta las guías de tus equipos, sus entregas, aprobaciones firmadas y PDF finales. Los pendientes se calculan con la última versión de cada entrega. Una guía puede tener equipos con distintos avances.</p>
    <x-mensajes-formulario />
    @if(session('status'))<p class="mt-4 rounded border border-blue-200 bg-blue-50 p-4 text-sm">{{ session('status') }}</p>@endif
    <form method="GET" action="{{ route('docente-lider.estado-guias') }}" class="mt-5 grid gap-4 rounded-lg border bg-white p-5 md:grid-cols-2 xl:grid-cols-5">
        <label class="text-sm font-bold">Periodo<select name="periodo" class="mt-2 block w-full rounded border-slate-300"><option value="">Todos mis periodos</option>@foreach($periodos as $periodo)<option value="{{ $periodo->id }}" @selected((int)($filtros['periodo'] ?? 0) === $periodo->id)>{{ $periodo->nombre }}</option>@endforeach</select></label>
        <label class="text-sm font-bold">Grupo<select name="grupo" class="mt-2 block w-full rounded border-slate-300"><option value="">Todos mis grupos</option>@foreach($grupos as $grupo)<option value="{{ $grupo->id }}" @selected((int)($filtros['grupo'] ?? 0) === $grupo->id)>{{ $grupo->carrera?->clave }} · {{ $grupo->nombre }}</option>@endforeach</select></label>
        <label class="text-sm font-bold">Estado del equipo<select name="estado" class="mt-2 block w-full rounded border-slate-300"><option value="">Todos los estados</option>@foreach($estados as $valor => $etiqueta)<option value="{{ $valor }}" @selected(($filtros['estado'] ?? '') === $valor)>{{ $etiqueta }}</option>@endforeach</select></label>
        <label class="text-sm font-bold">Buscar<input type="search" name="buscar" maxlength="160" value="{{ $filtros['buscar'] ?? '' }}" placeholder="Guía, proyecto o equipo" class="mt-2 block w-full rounded border-slate-300"></label>
        <label class="text-sm font-bold">Equipos por página<select name="por_pagina" class="mt-2 block w-full rounded border-slate-300">@foreach([10, 20, 40] as $cantidad)<option value="{{ $cantidad }}" @selected($filtros['por_pagina'] === $cantidad)>{{ $cantidad }}</option>@endforeach</select></label>
        <div class="flex items-end gap-3"><button class="rounded bg-[#155AA3] px-4 py-2 font-bold text-white">Consultar</button><a class="py-2 text-sm text-[#155AA3] underline" href="{{ route('docente-lider.estado-guias') }}">Limpiar</a></div>
    </form>
    <p class="mt-4 text-xs text-slate-500">Resumen de tus proyectos para el periodo, grupo y búsqueda seleccionados. El filtro de estado limita el detalle de abajo.</p>
    <div class="mt-3 grid gap-3 sm:grid-cols-2 xl:grid-cols-6">
        @foreach([['Equipos / proyectos', $resumen['total']], ['Finalizados con PDF', $resumen['finalizadas']], ['Requieren decisión', $resumen['decision']], ['Prórrogas activas', $resumen['prorrogas']], ['Listos para PDF', $resumen['listas_pdf']], ['Cerrados con pendientes', $resumen['cerradas_pendientes']]] as [$titulo, $cantidad])
        <div class="rounded-lg border bg-white p-4"><p class="text-xs font-semibold text-slate-500">{{ $titulo }}</p><p class="mt-2 text-2xl font-bold text-[#0D376D]">{{ $cantidad }}</p></div>
        @endforeach
    </div>
    <div class="mt-5 rounded-lg border bg-white p-4">
        <p class="mb-3 text-sm text-slate-600">Mostrando {{ $paginacion->firstItem() ?? 0 }}–{{ $paginacion->lastItem() ?? 0 }} de {{ $paginacion->total() }} equipos / proyectos · Página {{ $paginacion->currentPage() }} de {{ $paginacion->lastPage() }}. Los totales del resumen incluyen todas las páginas.</p>
        {{ $paginacion->onEachSide(1)->links() }}
    </div>
    <div class="mt-6 space-y-6">
        @forelse($guias as $filas)
        @php($guia = $filas->first()['guia'])
        <section class="overflow-hidden rounded-lg border border-slate-300 bg-white">
            <header class="border-b-4 border-[#155AA3] bg-slate-50 p-5"><div class="flex flex-wrap items-start justify-between gap-3"><div><h3 class="text-lg font-bold text-[#0D376D]">{{ $guia->nombre }}</h3><p class="mt-1 text-sm text-slate-600">{{ $guia->periodo->nombre }} · Versión {{ $guia->version }} · Configuración: {{ ucfirst($guia->estado) }}</p></div><span class="rounded bg-blue-50 px-3 py-2 text-sm font-bold text-[#155AA3]">{{ $filas->where('estado', 'finalizada')->count() }} / {{ $filas->count() }} equipos visibles finalizados</span></div></header>
            <div class="divide-y divide-slate-200">
                @foreach($filas as $fila)
                @php($proyecto = $fila['proyecto'])
                @php($colores = match($fila['estado']) { 'finalizada' => 'bg-emerald-50 text-emerald-800', 'prorroga_activa', 'lista_pdf' => 'bg-blue-50 text-blue-800', 'prorroga_vencida', 'necesita_prorroga' => 'bg-red-50 text-red-800', 'cerrada_pendientes' => 'bg-amber-50 text-amber-800', default => 'bg-slate-100 text-slate-700' })
                <article class="p-5">
                    <div class="flex flex-wrap justify-between gap-3"><div><h4 class="font-bold text-slate-900">{{ $proyecto->titulo }}</h4><p class="mt-1 text-sm text-slate-500">{{ $proyecto->equipo->nombre }} · {{ $proyecto->equipo->grupoAcademico->nombre }} · {{ $proyecto->equipo->grupoAcademico->periodo->nombre }}</p></div><span class="h-fit rounded-full px-3 py-1 text-sm font-bold {{ $colores }}">{{ $fila['etiqueta'] }}</span></div>
                    <div class="mt-4 grid gap-3 text-sm md:grid-cols-3"><p><strong>{{ $fila['entregados'] }}/{{ $fila['total'] }}</strong> apartados con entrega</p><p><strong>{{ $fila['firmadas'] }}/{{ $fila['requeridas'] }}</strong> aprobaciones firmadas vigentes</p><p><strong>{{ $fila['completos'] }}/{{ $fila['total'] }}</strong> apartados completos</p></div>
                    <div class="mt-3 h-2 overflow-hidden rounded bg-slate-100" role="progressbar" aria-label="Apartados completos" aria-valuemin="0" aria-valuemax="{{ max(1, $fila['total']) }}" aria-valuenow="{{ $fila['completos'] }}"><div class="h-full bg-[#21A366]" style="width: {{ $fila['total'] ? round($fila['completos'] / $fila['total'] * 100) : 0 }}%"></div></div>
                    @if($fila['fecha_prorroga'])<p class="mt-3 text-sm text-slate-600">Última prórroga: hasta {{ $fila['fecha_prorroga']->format('d/m/Y H:i') }} ({{ config('app.timezone') }}).</p>@endif
                    @if($fila['estado'] === 'lista_pdf')<p class="mt-3 text-sm text-blue-800">Las entregas y aprobaciones están completas; falta emitir y guardar el PDF final.</p>@endif
                    @if($fila['cambios_despues_cierre'])<p class="mt-3 text-sm text-amber-800">Existe un PDF final archivado del cierre. Los datos actuales tienen cambios posteriores; consulta el PDF para revisar las entregas y firmas con las que se finalizó.</p>@endif
                    @if($fila['requiere_decision'])<p class="mt-3 text-sm font-semibold text-red-800">El plazo terminó y hay pendientes. Revisa qué apartados necesitan prórroga o registra el cierre en su estado actual.</p>@endif
                    @if($fila['estado'] === 'cerrada_pendientes')<p class="mt-3 text-sm text-amber-800">El cierre conserva su constancia de pendientes. Las entregas y revisiones están bloqueadas.</p>@endif
                    <div class="mt-4 flex flex-wrap gap-3">
                        <a href="{{ route('documentos.mostrar', ['proyecto' => $proyecto, 'origen' => 'docente-lider.estado-guias', 'contexto' => $filtros]) }}" class="rounded border border-[#155AA3] px-3 py-2 text-sm font-bold text-[#155AA3]">Ver formato y firmas</a>
                        @if($fila['documento'])<a href="{{ route('documentos.descargar', ['proyecto' => $proyecto, 'documento' => $fila['documento']]) }}" class="rounded bg-emerald-700 px-3 py-2 text-sm font-bold text-white">Descargar PDF final #{{ $fila['documento']->id }}</a>@endif
                        @if($fila['cierre'])<a href="{{ route('docente-lider.cierres.mostrar', $proyecto) }}" class="rounded bg-[#155AA3] px-3 py-2 text-sm font-bold text-white">{{ in_array($fila['estado'], ['finalizada', 'cerrada_pendientes'], true) ? 'Ver historial de cierre' : 'Revisar cierre y prórroga' }}</a>
                        @elseif($fila['requiere_decision'] || $fila['estado'] === 'lista_pdf')<form method="POST" action="{{ route('docente-lider.estado-guias.preparar-cierre', $proyecto) }}">@csrf<button class="rounded bg-[#155AA3] px-3 py-2 text-sm font-bold text-white">Revisar cierre y prórroga</button></form>@endif
                    </div>
                    <details class="mt-4 rounded border bg-slate-50 p-4"><summary class="cursor-pointer text-sm font-bold text-[#0D376D]">Ver estado de cada apartado y motivos pendientes</summary>
                        <div class="mt-3 space-y-3">@forelse($fila['apartados'] as $apartado)<div class="rounded border bg-white p-3 text-sm"><div class="flex flex-wrap justify-between gap-2"><strong>{{ $apartado['orden'] }}. {{ $apartado['titulo'] }}</strong><span>{{ $apartado['completo'] ? 'Completo' : 'Pendiente' }}{{ $apartado['en_prorroga'] ? ' · Abierto por prórroga' : '' }}</span></div><p class="mt-2 text-xs text-slate-500">{{ $apartado['entregada'] ? 'Entrega versión '.$apartado['version'] : 'Sin entrega' }} · Firmas {{ $apartado['firmadas'] }}/{{ $apartado['requeridas'] }} · Plazo: {{ $apartado['fecha']?->format('d/m/Y H:i') ?? 'Sin fecha' }}</p>@if($apartado['motivos'])<ul class="mt-2 list-disc pl-5 text-amber-800">@foreach($apartado['motivos'] as $motivo)<li>{{ $motivo }}</li>@endforeach</ul>@endif</div>@empty<p class="text-sm text-amber-800">La guía no tiene apartados configurados.</p>@endforelse</div>
                        @if($fila['estado'] === 'cerrada_pendientes')<p class="mt-3 text-xs text-slate-500">Los motivos registrados al cerrar se conservan en el historial de cierre.</p>@endif
                    </details>
                </article>
                @endforeach
            </div>
        </section>
        @empty<p class="rounded-lg border bg-white p-8 text-center text-sm text-slate-500">No hay guías de tus equipos que coincidan con estos filtros.</p>@endforelse
    </div>
    @if($paginacion->hasPages())<div class="mt-6 rounded-lg border bg-white p-4">{{ $paginacion->links() }}</div>@endif
</x-contenedor-aplicacion>
