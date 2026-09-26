<x-contenedor-aplicacion
    title="{{ $panel['title'] }} | Administración de proyectos"
    active="dashboard"
    :navegacion="$navegacion"
    :role-name="$roleName"
>
    <section class="border-b border-slate-200 pb-5">
        <p class="text-sm font-semibold uppercase tracking-[0.18em] text-[#21A366]">{{ $panel['eyebrow'] }}</p>
        <div class="mt-3 flex flex-col gap-3 xl:flex-row xl:items-end xl:justify-between">
            <div class="min-w-0">
                <h2 class="text-2xl font-bold text-[#0D376D]">{{ $panel['title'] }}</h2>
                <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">{{ $panel['description'] }}</p>
            </div>
            <div class="text-left xl:text-right">
                <span class="inline-flex w-fit rounded-md bg-[#EAF7EF] px-3 py-2 text-sm font-semibold text-[#0F7D47]">{{ $panel['badge'] }}</span>
                <p class="mt-2 text-xs font-semibold text-slate-500">{{ $periodoActual?->nombre ?? 'Sin periodo configurado' }}</p>
            </div>
        </div>
    </section>

    @if(in_array($role, ['coordinacion', 'docente_lider'], true))
        @include('modulos.busqueda-panel')
    @endif

    @if($role === 'docente_lider' && collect($navegacion)->contains('clave', 'estado-guias'))
        <section class="mt-5 rounded-lg border border-blue-200 bg-blue-50 p-5"><h3 class="font-bold text-[#0D376D]">Estado de las guías de tus equipos</h3><p class="mt-2 text-sm text-slate-600">Consulta cuáles están finalizadas, dónde faltan entregas o firmas y qué equipos necesitan una decisión de prórroga.</p><a href="{{ route('docente-lider.estado-guias') }}" class="mt-3 inline-block rounded bg-[#155AA3] px-4 py-2 text-sm font-bold text-white">Ver estado de las guías</a></section>
    @endif

    @if($role !== 'estudiante')
    <section class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($panel['stats'] as $stat)
            @php
                $colorMetrica = $role === 'estudiante' ? match($stat['label']) {
                    'Validadas' => 'border-emerald-200 border-l-4 border-l-emerald-500',
                    'Rechazadas' => 'border-red-200 border-l-4 border-l-red-500',
                    'Sin entregar' => 'border-slate-300 border-l-4 border-l-slate-500',
                    default => 'border-amber-200 border-l-4 border-l-amber-500',
                } : 'border-slate-200';
            @endphp
            <article class="rounded-lg border bg-white p-4 shadow-sm {{ $colorMetrica }}">
                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">{{ $stat['label'] }}</p>
                <p class="mt-2 text-2xl font-bold text-[#0D376D]">{{ $stat['value'] }}</p>
            </article>
        @endforeach
    </section>
    @endif

    @if($role === 'coordinacion')
        <section class="mt-5">
            <div class="mb-3">
                <h3 class="font-bold text-slate-900">Atención operativa</h3>
                <p class="mt-1 text-sm text-slate-500">Situaciones del periodo que requieren seguimiento de Coordinación.</p>
            </div>
            <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                @foreach($alertasOperativas as $alerta)
                    <article class="rounded-lg border bg-white p-4 shadow-sm {{ $alerta['value'] > 0 ? 'border-amber-200' : 'border-emerald-200' }}">
                        <div class="flex items-start justify-between gap-3">
                            <p class="text-sm font-bold text-slate-800">{{ $alerta['label'] }}</p>
                            <span class="rounded-full px-2.5 py-1 text-sm font-bold {{ $alerta['value'] > 0 ? 'bg-amber-50 text-amber-700' : 'bg-emerald-50 text-emerald-700' }}">{{ $alerta['value'] }}</span>
                        </div>
                        <p class="mt-3 text-xs leading-5 text-slate-500">{{ $alerta['detail'] }}</p>
                    </article>
                @endforeach
            </div>
        </section>
    @endif

    @if($role === 'docente_materia')
        <section class="mt-5">
            <div class="mb-3">
                <h3 class="font-bold text-slate-900">Atención requerida</h3>
                <p class="mt-1 text-sm text-slate-500">Situaciones detectadas en los apartados que tienes asignados.</p>
            </div>
            <div class="grid gap-3 md:grid-cols-2">
                @foreach($alertasOperativas as $alerta)
                    @php
                        $requiereAtencion = $alerta['value'] > 0;
                    @endphp
                    <article class="border-l-4 rounded-lg border bg-white p-4 shadow-sm {{ $requiereAtencion ? ($alerta['tone'] === 'red' ? 'border-red-400' : 'border-amber-400') : 'border-emerald-400' }}">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="font-bold text-slate-800">{{ $alerta['label'] }}</p>
                                <p class="mt-2 text-sm leading-5 text-slate-500">{{ $alerta['detail'] }}</p>
                            </div>
                            <span class="min-w-10 rounded-full px-3 py-1.5 text-center text-lg font-extrabold {{ $requiereAtencion ? ($alerta['tone'] === 'red' ? 'bg-red-50 text-red-700' : 'bg-amber-50 text-amber-700') : 'bg-emerald-50 text-emerald-700' }}">{{ $alerta['value'] }}</span>
                        </div>
                        <p class="mt-3 text-xs font-semibold {{ $requiereAtencion ? 'text-slate-600' : 'text-emerald-700' }}">
                            {{ $requiereAtencion ? 'Requiere seguimiento' : 'Sin incidencias' }}
                        </p>
                    </article>
                @endforeach
            </div>
        </section>
    @endif

    @if($role === 'docente_materia')
        <section class="mt-5 overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4">
                <h3 class="font-bold text-slate-900">Próximos apartados asignados</h3>
                <p class="mt-1 text-sm text-slate-500">Tu alcance de evaluación para el periodo actual.</p>
            </div>
            <div class="divide-y divide-slate-200">
                @forelse($asignacionesDocente as $firma)
                    <article class="grid gap-3 px-5 py-4 sm:grid-cols-[80px_minmax(0,1fr)_180px_120px] sm:items-center">
                        <p class="text-lg font-bold text-[#0D376D]">#{{ $firma->apartadoGuia?->orden }}</p>
                        <div><p class="font-semibold text-slate-900">{{ $firma->apartadoGuia?->titulo }}</p><p class="mt-1 text-sm text-slate-500">{{ $firma->asignatura?->nombre ?? $firma->etiqueta }}</p></div>
                        <div><p class="text-xs text-slate-500">Fecha límite</p><p class="mt-1 text-sm font-semibold">{{ $firma->apartadoGuia?->fecha_limite?->format('d/m/Y H:i') ?? 'Sin fecha' }}</p></div>
                        <div><p class="text-xs text-slate-500">Ponderación</p><p class="mt-1 font-bold">{{ number_format($firma->apartadoGuia?->ponderacion ?? 0, 0) }}%</p></div>
                    </article>
                @empty
                    <p class="px-5 py-10 text-center text-sm text-slate-500">Coordinación todavía no te ha asignado apartados de la guía.</p>
                @endforelse
            </div>
        </section>
    @endif

    @if($role === 'estudiante')
        @php
            $totalApartados = $resumenEntregasEstudiante->count();
            $validadas = $resumenEntregasEstudiante->where('estado', 'validada')->count();
            $rechazadas = $resumenEntregasEstudiante->where('estado', 'rechazada')->count();
            $pendientes = $resumenEntregasEstudiante->where('estado', 'pendiente')->count();
            $sinEntregar = $resumenEntregasEstudiante->where('estado', 'sin_entregar')->count();
            $baseGrafica = max(1, $totalApartados);
            $finValidadas = ($validadas / $baseGrafica) * 100;
            $finRechazadas = $finValidadas + (($rechazadas / $baseGrafica) * 100);
            $finPendientes = $finRechazadas + (($pendientes / $baseGrafica) * 100);
            $porcentajeValidado = round($finValidadas);
        @endphp
        <section class="mt-5 grid gap-4 rounded-lg border border-slate-200 bg-white p-5 shadow-sm lg:grid-cols-[260px_minmax(0,1fr)] lg:items-center">
            <div class="flex justify-center">
                <div
                    class="relative grid h-48 w-48 place-items-center rounded-full"
                    style="background: conic-gradient(#10B981 0% {{ $finValidadas }}%, #EF4444 {{ $finValidadas }}% {{ $finRechazadas }}%, #F59E0B {{ $finRechazadas }}% {{ $finPendientes }}%, #CBD5E1 {{ $finPendientes }}% 100%);"
                    role="img"
                    aria-label="Gráfica del progreso de entregas del equipo"
                >
                    <div class="grid h-32 w-32 place-items-center rounded-full bg-white text-center shadow-inner">
                        <div><p class="text-3xl font-extrabold text-[#0D376D]">{{ $porcentajeValidado }}%</p><p class="text-xs font-bold uppercase tracking-wider text-slate-500">Validado</p></div>
                    </div>
                </div>
            </div>
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.16em] text-[#21A366]">Progreso del equipo</p>
                <h3 class="mt-2 text-xl font-bold text-[#0D376D]">{{ $validadas }} de {{ $totalApartados }} apartados validados</h3>
                <p class="mt-2 text-sm leading-6 text-slate-500">La gráfica resume únicamente las entregas del proyecto correspondiente a tu equipo.</p>
                <div class="mt-5 grid gap-3 sm:grid-cols-2">
                    @foreach([
                        ['Validadas', $validadas, 'bg-emerald-500'],
                        ['Rechazadas / corrección', $rechazadas, 'bg-red-500'],
                        ['Pendientes de revisión', $pendientes, 'bg-amber-500'],
                        ['Sin entregar', $sinEntregar, 'bg-slate-300'],
                    ] as [$etiqueta, $cantidad, $color])
                        <div class="flex items-center justify-between gap-3 rounded-md bg-slate-50 px-3 py-2">
                            <div class="flex items-center gap-2"><span class="h-3 w-3 rounded-full {{ $color }}"></span><span class="text-sm font-semibold text-slate-700">{{ $etiqueta }}</span></div>
                            <span class="font-extrabold text-slate-900">{{ $cantidad }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="mt-5 overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="border-b-4 border-[#155AA3] px-5 py-4">
                <h3 class="font-bold text-slate-900">Resumen de la guía</h3>
                <p class="mt-1 text-sm text-slate-500">Estado de la última versión enviada en cada apartado.</p>
            </div>
            <div class="divide-y divide-slate-200">
                @forelse($resumenEntregasEstudiante as $item)
                    @php
                        $configuracionEstado = match($item['estado']) {
                            'validada' => ['Validada', 'bg-emerald-50 text-emerald-700', 'border-l-emerald-400'],
                            'rechazada' => ['Rechazada / corrección', 'bg-red-50 text-red-700', 'border-l-red-400'],
                            'pendiente' => ['Pendiente de revisión', 'bg-amber-50 text-amber-700', 'border-l-amber-400'],
                            default => ['Sin entregar', 'bg-slate-100 text-slate-600', 'border-l-slate-300'],
                        };
                    @endphp
                    <article class="grid gap-3 border-l-4 px-5 py-4 sm:grid-cols-[70px_minmax(0,1fr)_170px_180px] sm:items-center {{ $configuracionEstado[2] }}">
                        <p class="text-lg font-extrabold text-[#0D376D]">#{{ $item['apartado']->orden }}</p>
                        <div>
                            <p class="font-semibold text-slate-900">{{ $item['apartado']->titulo }}</p>
                            <p class="mt-1 text-xs text-slate-500">Límite: {{ $item['apartado']->fecha_limite?->format('d/m/Y H:i') ?? 'Sin fecha definida' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-slate-500">Última entrega</p>
                            <p class="mt-1 text-sm font-semibold text-slate-700">{{ $item['entrega'] ? 'Versión '.$item['entrega']->version : 'Ninguna' }}</p>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <span class="rounded-full px-3 py-1 text-xs font-bold {{ $configuracionEstado[1] }}">{{ $configuracionEstado[0] }}</span>
                            @if($item['revision']?->calificacion !== null)<span class="text-sm font-extrabold text-[#0D376D]">{{ $item['revision']->calificacion }}/10</span>@endif
                        </div>
                    </article>
                @empty
                    <div class="px-5 py-12 text-center"><p class="font-semibold text-slate-700">Aún no hay una guía asociada a tu proyecto.</p><p class="mt-1 text-sm text-slate-500">El resumen aparecerá cuando el docente líder asigne el proyecto.</p></div>
                @endforelse
            </div>
        </section>
    @endif

    @if(in_array($role, ['coordinacion', 'docente_lider'], true))
    <section class="mt-5 overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-5 py-4">
            <div>
                <h3 class="font-bold text-slate-900">Grupos del periodo</h3>
                <p class="mt-1 text-sm text-slate-500">Resumen correspondiente al alcance de tu rol.</p>
            </div>
        </div>
        <div class="divide-y divide-slate-200">
            @forelse($gruposResumen as $grupo)
                <article class="grid gap-3 px-5 py-4 sm:grid-cols-[100px_minmax(0,1fr)_100px_100px] sm:items-center">
                    <div>
                        <p class="text-xs font-bold uppercase text-[#21A366]">{{ $grupo->carrera->clave }}</p>
                        <p class="mt-1 text-lg font-bold text-[#0D376D]">{{ $grupo->grado }}{{ $grupo->grupo }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-500">Docente líder</p>
                        <p class="mt-1 text-sm font-semibold text-slate-800">{{ $grupo->liderProyecto?->nombre ?? 'Sin asignar' }}</p>
                    </div>
                    <div><p class="text-xs text-slate-500">Alumnos</p><p class="font-bold">{{ $grupo->alumnos_count }}</p></div>
                    <div><p class="text-xs text-slate-500">Equipos</p><p class="font-bold">{{ $grupo->equipos_count }}</p></div>
                </article>
            @empty
                <p class="px-5 py-10 text-center text-sm text-slate-500">No hay grupos disponibles en el periodo actual.</p>
            @endforelse
        </div>
    </section>
    @endif
</x-contenedor-aplicacion>
