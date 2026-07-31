<x-contenedor-aplicacion
    title="Revisiones | Administración de proyectos"
    active="revisiones-docente"
    :navegacion="$navegacion"
    :role-name="$roleName"
>
    <section class="border-b border-slate-200 pb-5">
        <p class="text-sm font-semibold uppercase tracking-[0.18em] text-[#21A366]">Bandeja de trabajo</p>
        <h2 class="mt-3 text-2xl font-bold text-[#0D376D]">Revisión de entregas</h2>
        <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">Califica únicamente los apartados que te fueron asignados. Se muestra la versión más reciente de cada equipo.</p>
    </section>

    @if(session('status'))
        <div class="mt-5 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">{{ session('status') }}</div>
    @endif

    @php($apartadosPaginacion = $entregas->map(fn($entrega) => $entrega->apartado)->filter()->unique('id')->sortBy('orden')->values())
    @if($apartadosPaginacion->isNotEmpty())
        <section class="mt-5 overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4">
                <h3 class="font-bold text-slate-900">Apartados asignados</h3>
                <p class="mt-1 text-sm text-slate-500">Selecciona un apartado para consultar todas las entregas correspondientes.</p>
            </div>
            <div class="flex flex-wrap gap-2 px-4 py-3" id="paginacion-apartados">
                @foreach($apartadosPaginacion as $apartado)
                    <button
                        type="button"
                        data-apartado="{{ $apartado->id }}"
                        data-titulo="{{ $apartado->titulo }}"
                        class="boton-apartado min-w-fit rounded-md border px-4 py-2 text-sm font-bold transition hover:bg-blue-50"
                        style="border-color: {{ $loop->first ? '#155AA3' : '#CBD5E1' }}; background-color: {{ $loop->first ? '#155AA3' : '#FFFFFF' }}; color: {{ $loop->first ? '#FFFFFF' : '#155AA3' }};"
                    >
                        {{ $apartado->titulo }}
                        <span class="ml-1 opacity-75">({{ $entregas->where('apartado_guia_id', $apartado->id)->count() }})</span>
                    </button>
                @endforeach
            </div>
        </section>
    @endif

    <section class="mt-5 rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
        <div class="grid gap-3 md:grid-cols-[minmax(0,1fr)_220px_220px]">
            <label><span class="text-xs font-bold uppercase text-slate-500">Buscar</span><input id="buscar-entrega" type="search" placeholder="Equipo, proyecto o apartado" class="mt-2 w-full rounded-md border-slate-300 text-sm"></label>
            <label><span class="text-xs font-bold uppercase text-slate-500">Grupo</span><select id="filtro-grupo" class="mt-2 w-full rounded-md border-slate-300 text-sm"><option value="">Todos los grupos</option>@foreach($entregas->map(fn($e) => $e->equipo?->grupoAcademico)->filter()->unique('id')->sortBy(fn($g) => $g->grado.$g->grupo) as $grupo)<option value="{{ $grupo->id }}">{{ $grupo->carrera?->clave }} · {{ $grupo->grado }}{{ $grupo->grupo }}</option>@endforeach</select></label>
            <label><span class="text-xs font-bold uppercase text-slate-500">Estado</span><select id="filtro-estado" class="mt-2 w-full rounded-md border-slate-300 text-sm"><option value="">Todos</option><option value="pendiente">Pendientes</option><option value="aprobada">Aprobadas</option><option value="correccion">Con corrección</option><option value="rechazada">Rechazadas</option></select></label>
        </div>
    </section>

    <section class="mt-5 overflow-hidden rounded-lg border border-slate-300 bg-slate-100 shadow-sm">
        <div class="border-b-4 border-[#155AA3] bg-white px-5 py-4">
            <p class="text-xs font-bold uppercase tracking-[0.16em] text-[#21A366]">Apartado seleccionado</p>
            <div class="mt-1 flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                <h3 id="titulo-apartado-activo" class="text-lg font-bold text-[#0D376D]">{{ $apartadosPaginacion->first()?->titulo ?? 'Entregas disponibles' }}</h3>
                <p id="contador-entregas" class="text-sm font-semibold text-slate-500">{{ $entregas->count() }} resultados</p>
            </div>
        </div>
        <div id="lista-entregas" class="space-y-3 p-3">
            @forelse($entregas as $entrega)
                @php($revision = $entrega->revisiones->first())
                @php($grupo = $entrega->equipo?->grupoAcademico)
                <article class="entrega overflow-hidden rounded-md border border-slate-300 border-l-4 border-l-[#155AA3] bg-white shadow-sm" data-apartado="{{ $entrega->apartado_guia_id }}" data-grupo="{{ $grupo?->id }}" data-estado="{{ $revision?->resultado ?? 'pendiente' }}" data-busqueda="{{ Str::lower(($entrega->equipo?->nombre ?? 'equipo '.$entrega->equipo?->numero).' '.$entrega->proyecto?->titulo.' '.$entrega->apartado?->titulo) }}">
                    <button type="button" class="desplegar-entrega grid w-full gap-3 px-5 py-4 text-left hover:bg-slate-50 md:grid-cols-[190px_minmax(0,1fr)_180px_130px] md:items-center">
                        <div class="border-b border-slate-200 pb-3 md:border-b-0 md:border-r md:pb-0 md:pr-4">
                            <p class="text-[11px] font-bold uppercase tracking-wider text-[#21A366]">{{ $grupo?->carrera?->clave }} · Grupo {{ $grupo?->grado }}{{ $grupo?->grupo }}</p>
                            <p class="mt-1 text-base font-extrabold text-[#0D376D]">{{ $entrega->equipo?->nombre ?? 'Equipo '.$entrega->equipo?->numero }}</p>
                        </div>
                        <div><p class="text-xs font-bold uppercase tracking-wider text-slate-400">Proyecto</p><p class="mt-1 font-semibold text-slate-900">{{ $entrega->proyecto?->titulo }}</p><p class="mt-1 text-xs font-semibold text-[#155AA3]">{{ $entrega->apartado?->titulo }} · Versión {{ $entrega->version }}</p></div>
                        <div><p class="text-xs text-slate-500">Entrega</p><p class="mt-1 text-sm font-semibold text-slate-700">{{ $entrega->entregado_en?->format('d/m/Y H:i') ?? 'Sin fecha' }}</p><p class="mt-1 text-xs text-[#155AA3]">Por {{ $entrega->entregadoPor?->nombre ?? 'Integrante del equipo' }}</p></div>
                        <div class="flex items-center justify-between gap-2"><span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $revision ? 'bg-blue-50 text-blue-700' : 'bg-amber-50 text-amber-700' }}">{{ $revision ? ucfirst($revision->resultado) : 'Pendiente' }}</span><span class="text-lg text-slate-400">⌄</span></div>
                    </button>
                    <div class="detalle-entrega hidden border-t border-slate-100 bg-slate-50 px-5 py-5">
                        <div class="mb-5 grid gap-4 lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)]">
                            <div class="rounded-lg border border-slate-200 bg-white p-4">
                                <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Evidencias entregadas</p>
                                <div class="mt-3 space-y-2">
                                    @forelse($entrega->archivos as $archivo)
                                        <a href="{{ route('docente-materia.archivos.descargar', $archivo) }}" class="flex items-center justify-between gap-3 rounded-md border border-slate-200 px-3 py-2 text-sm hover:border-blue-300 hover:bg-blue-50">
                                            <span class="min-w-0 truncate font-semibold text-[#155AA3]">{{ $archivo->nombre_original }}</span>
                                            <span class="shrink-0 text-xs text-slate-500">{{ $archivo->tamano ? number_format($archivo->tamano / 1024, 1).' KB' : 'Descargar' }}</span>
                                        </a>
                                    @empty
                                        <p class="text-sm text-slate-500">Esta entrega no contiene archivos adjuntos.</p>
                                    @endforelse
                                </div>
                            </div>
                            <div class="rounded-lg border border-slate-200 bg-white p-4">
                                <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Criterio asignado</p>
                                <p class="mt-3 font-semibold text-slate-800">{{ $entrega->apartado?->titulo }}</p>
                                <p class="mt-1 text-sm text-slate-500">Ponderación: {{ number_format($entrega->apartado?->ponderacion ?? 0, 0) }}% · Versión recibida: {{ $entrega->version }}</p>
                            </div>
                        </div>
                        <form method="POST" action="{{ route('docente-materia.revisiones.guardar', $entrega) }}" class="grid gap-4 lg:grid-cols-[180px_160px_minmax(0,1fr)_140px] lg:items-end">
                            @csrf @method('PUT')
                            <label><span class="text-xs font-bold uppercase text-slate-500">Resultado</span><select name="resultado" required class="mt-2 w-full rounded-md border-slate-300 text-sm"><option value="aprobada" @selected($revision?->resultado === 'aprobada')>Aprobada</option><option value="correccion" @selected($revision?->resultado === 'correccion')>Requiere corrección</option><option value="rechazada" @selected($revision?->resultado === 'rechazada')>Rechazada</option></select></label>
                            <label><span class="text-xs font-bold uppercase text-slate-500">Calificación (0–10)</span><input name="calificacion" type="number" min="0" max="10" step="0.1" required value="{{ $revision?->calificacion }}" class="mt-2 w-full rounded-md border-slate-300 text-sm"></label>
                            <label><span class="text-xs font-bold uppercase text-slate-500">Observaciones</span><textarea name="observaciones" rows="2" maxlength="2000" class="mt-2 w-full rounded-md border-slate-300 text-sm" placeholder="Indica aciertos o correcciones necesarias">{{ $revision?->observaciones }}</textarea></label>
                            <button class="rounded-md bg-[#155AA3] px-4 py-2.5 text-sm font-bold text-white hover:bg-[#0D376D]">Guardar revisión</button>
                        </form>
                        <div class="mt-5 border-t border-slate-200 pt-5">
                            <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Comentarios de seguimiento</p>
                            <div class="mt-3 space-y-2">
                                @forelse($revision?->comentarios ?? collect() as $comentario)
                                    <div class="rounded-md border border-slate-200 bg-white px-4 py-3">
                                        <div class="flex flex-wrap items-center justify-between gap-2"><p class="text-sm font-bold text-slate-800">{{ $comentario->autor?->nombre }}</p><p class="text-xs text-slate-400">{{ $comentario->creado_en?->format('d/m/Y H:i') }}</p></div>
                                        <p class="mt-2 text-sm leading-5 text-slate-600">{{ $comentario->comentario }}</p>
                                    </div>
                                @empty
                                    <p class="text-sm text-slate-500">Aún no hay comentarios de seguimiento.</p>
                                @endforelse
                            </div>
                            <form method="POST" action="{{ route('docente-materia.comentarios.guardar', $entrega) }}" class="mt-3 flex flex-col gap-3 md:flex-row md:items-end">
                                @csrf
                                <label class="min-w-0 flex-1"><span class="sr-only">Nuevo comentario</span><textarea name="comentario" rows="2" maxlength="1500" required class="w-full rounded-md border-slate-300 text-sm" placeholder="Escribe una aclaración o indicación para el equipo"></textarea></label>
                                <button class="rounded-md border border-[#155AA3] px-4 py-2.5 text-sm font-bold text-[#155AA3] hover:bg-blue-50">Agregar comentario</button>
                            </form>
                            @unless($revision)
                                <p class="mt-2 text-xs text-amber-700">Guarda primero la evaluación para iniciar el seguimiento.</p>
                            @endunless
                        </div>
                    </div>
                </article>
            @empty
                <div class="px-5 py-12 text-center"><p class="font-semibold text-slate-700">No hay entregas pendientes para tus apartados.</p><p class="mt-1 text-sm text-slate-500">Las entregas aparecerán aquí cuando los equipos las envíen.</p></div>
            @endforelse
        </div>
        <p id="sin-resultados" class="hidden px-5 py-10 text-center text-sm text-slate-500">No hay entregas que coincidan con los filtros.</p>
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const items = [...document.querySelectorAll('.entrega')];
            const search = document.querySelector('#buscar-entrega');
            const group = document.querySelector('#filtro-grupo');
            const status = document.querySelector('#filtro-estado');
            const counter = document.querySelector('#contador-entregas');
            const empty = document.querySelector('#sin-resultados');
            const activeTitle = document.querySelector('#titulo-apartado-activo');
            const pageButtons = [...document.querySelectorAll('.boton-apartado')];
            let currentSection = pageButtons[0]?.dataset.apartado ?? '';
            const filter = () => {
                const term = search.value.trim().toLocaleLowerCase();
                let visible = 0;
                items.forEach(item => {
                    const show = (!currentSection || item.dataset.apartado === currentSection)
                        && (!term || item.dataset.busqueda.includes(term))
                        && (!group.value || item.dataset.grupo === group.value)
                        && (!status.value || item.dataset.estado === status.value);
                    item.classList.toggle('hidden', !show);
                    if (show) visible++;
                });
                counter.textContent = `${visible} resultado${visible === 1 ? '' : 's'}`;
                empty.classList.toggle('hidden', visible !== 0 || items.length === 0);
            };
            [search, group, status].forEach(control => control.addEventListener(control === search ? 'input' : 'change', filter));
            pageButtons.forEach(button => button.addEventListener('click', () => {
                currentSection = button.dataset.apartado;
                activeTitle.textContent = button.dataset.titulo;
                pageButtons.forEach(item => {
                    const active = item === button;
                    item.style.borderColor = active ? '#155AA3' : '#CBD5E1';
                    item.style.backgroundColor = active ? '#155AA3' : '#FFFFFF';
                    item.style.color = active ? '#FFFFFF' : '#155AA3';
                });
                items.forEach(item => item.querySelector('.detalle-entrega')?.classList.add('hidden'));
                filter();
            }));
            document.querySelectorAll('.desplegar-entrega').forEach(button => button.addEventListener('click', () => button.nextElementSibling.classList.toggle('hidden')));
            filter();
        });
    </script>
</x-contenedor-aplicacion>
