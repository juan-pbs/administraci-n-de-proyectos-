<x-contenedor-aplicacion
    title="Revisión de código | Administración de proyectos"
    active="revision-codigo"
    :navegacion="$navegacion"
    :role-name="$roleName"
>
    <section class="border-b border-slate-200 pb-5">
        <p class="text-sm font-semibold uppercase tracking-[0.18em] text-[#21A366]">Seguimiento técnico</p>
        <div class="mt-3 flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
            <div><h2 class="text-2xl font-bold text-[#0D376D]">Revisión de código</h2><p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">Repositorios, archivos fuente y evidencias técnicas de los equipos pertenecientes a tus grupos.</p></div>
            <span class="w-fit rounded-md bg-[#EAF7EF] px-3 py-2 text-sm font-semibold text-[#0F7D47]">{{ $periodo?->nombre ?? 'Sin periodo activo' }}</span>
        </div>
    </section>

    @if(session('status'))<div class="mt-5 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">{{ session('status') }}</div>@endif

    @php($grupos = $entregas->map(fn($entrega) => $entrega->equipo?->grupoAcademico)->filter()->unique('id')->sortBy(fn($grupo) => $grupo->grado.$grupo->grupo)->values())
    @if($grupos->isNotEmpty())
        <section class="mt-5 rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4"><h3 class="font-bold text-slate-900">Grupos asignados</h3><p class="mt-1 text-sm text-slate-500">Selecciona un grupo para revisar todos sus productos de código.</p></div>
            <div class="flex flex-wrap gap-2 p-4">
                @foreach($grupos as $grupo)
                    <button type="button" data-grupo="{{ $grupo->id }}" class="pagina-grupo rounded-md border px-4 py-2 text-sm font-bold" style="border-color:{{ $loop->first ? '#155AA3' : '#CBD5E1' }};background:{{ $loop->first ? '#155AA3' : '#FFF' }};color:{{ $loop->first ? '#FFF' : '#155AA3' }}">{{ $grupo->carrera?->clave }} · {{ $grupo->grado }}{{ $grupo->grupo }} ({{ $entregas->filter(fn($entrega) => $entrega->equipo?->grupo_academico_id === $grupo->id)->count() }})</button>
                @endforeach
            </div>
        </section>
    @endif

    <section class="mt-5 space-y-3 rounded-lg border border-slate-300 bg-slate-100 p-3">
        @forelse($entregas as $entrega)
            @php($revision = $entrega->revisiones->first())
            @php($producto = $entrega->productosCodigo->first())
            <article class="entrega-codigo overflow-hidden rounded-md border border-slate-300 border-l-4 border-l-[#155AA3] bg-white shadow-sm" data-grupo="{{ $entrega->equipo?->grupo_academico_id }}">
                <div class="grid gap-4 px-5 py-4 lg:grid-cols-[180px_minmax(0,1fr)_160px] lg:items-center">
                    <div class="lg:border-r lg:border-slate-200 lg:pr-4"><p class="text-xs font-bold uppercase text-[#21A366]">{{ $entrega->equipo?->grupoAcademico?->carrera?->clave }} · {{ $entrega->equipo?->grupoAcademico?->grado }}{{ $entrega->equipo?->grupoAcademico?->grupo }}</p><p class="mt-1 font-extrabold text-[#0D376D]">{{ $entrega->equipo?->nombre }}</p></div>
                    <div><p class="font-bold text-slate-900">{{ $entrega->proyecto?->titulo }}</p><p class="mt-1 text-sm text-slate-500">{{ $entrega->apartado?->titulo }} · Versión {{ $producto?->version ?? $entrega->version }}</p><p class="mt-1 text-xs font-semibold text-[#155AA3]">Subida por {{ $entrega->entregadoPor?->nombre ?? 'Integrante del equipo' }}</p></div>
                    <span class="w-fit rounded-full px-3 py-1 text-xs font-bold {{ $revision ? 'bg-blue-50 text-blue-700' : 'bg-amber-50 text-amber-700' }}">{{ $revision ? ucfirst($revision->resultado) : 'Pendiente' }}</span>
                </div>
                <div class="border-t border-slate-200 bg-slate-50 px-5 py-5">
                    <div class="grid gap-4 lg:grid-cols-2">
                        <div class="rounded-md border border-slate-200 bg-white p-4"><p class="text-xs font-bold uppercase text-slate-500">Repositorio</p>@if($producto?->repositorio_url)<a href="{{ $producto->repositorio_url }}" target="_blank" rel="noopener" class="mt-2 block break-all text-sm font-bold text-[#155AA3] underline">{{ $producto->repositorio_url }}</a>@else<p class="mt-2 text-sm text-slate-500">No se registró un repositorio.</p>@endif @if($producto?->archivo_fuente)<p class="mt-3 text-sm font-semibold text-slate-700">Fuente: {{ basename($producto->archivo_fuente) }}</p>@endif</div>
                        <div class="rounded-md border border-slate-200 bg-white p-4">
                            <p class="text-xs font-bold uppercase text-slate-500">Archivos subidos</p>
                            <div class="mt-3 space-y-2">
                                @forelse($entrega->archivos as $archivo)
                                    <div class="flex items-center gap-3 rounded-md border px-3 py-2 {{ $archivo->esComprimido() ? 'border-violet-200 bg-violet-50' : 'border-slate-200' }}">
                                        <span class="flex h-9 w-11 shrink-0 items-center justify-center rounded bg-white text-[10px] font-extrabold {{ $archivo->esComprimido() ? 'text-violet-700' : 'text-slate-600' }}">{{ $archivo->extension() }}</span>
                                        <div class="min-w-0 flex-1">
                                            <p class="truncate text-sm font-bold text-slate-800">{{ $archivo->nombre_original }}</p>
                                            <p class="mt-0.5 text-xs text-slate-500">{{ $archivo->esComprimido() ? 'Archivo comprimido' : 'Evidencia técnica' }} · {{ $archivo->tamano ? number_format($archivo->tamano / 1024, 1).' KB' : 'Tamaño no disponible' }}</p>
                                        </div>
                                        <a href="{{ route('docente-materia.principal.archivo', $archivo) }}" class="shrink-0 rounded-md border border-[#155AA3] px-3 py-1.5 text-xs font-bold text-[#155AA3] hover:bg-blue-50">Descargar</a>
                                    </div>
                                @empty
                                    <p class="text-sm text-slate-500">Sin archivos adjuntos.</p>
                                @endforelse
                            </div>
                        </div>
                    </div>
                    <form method="POST" action="{{ route('docente-materia.principal.revisar', $entrega) }}" class="mt-4 grid gap-3 lg:grid-cols-[180px_150px_minmax(0,1fr)_150px] lg:items-end">
                        @csrf @method('PUT')
                        <label><span class="text-xs font-bold uppercase text-slate-500">Resultado</span><select name="resultado" class="mt-2 w-full rounded-md border-slate-300 text-sm"><option value="aprobada" @selected($revision?->resultado === 'aprobada')>Aprobada</option><option value="correccion" @selected($revision?->resultado === 'correccion')>Solicitar corrección</option><option value="rechazada" @selected($revision?->resultado === 'rechazada')>Rechazada</option></select></label>
                        <label><span class="text-xs font-bold uppercase text-slate-500">Calificación</span><input required name="calificacion" type="number" min="0" max="10" step=".1" value="{{ $revision?->calificacion }}" class="mt-2 w-full rounded-md border-slate-300 text-sm"></label>
                        <label><span class="text-xs font-bold uppercase text-slate-500">Observaciones técnicas</span><textarea name="observaciones" rows="2" class="mt-2 w-full rounded-md border-slate-300 text-sm">{{ $revision?->observaciones }}</textarea></label>
                        <button class="rounded-md bg-[#155AA3] px-4 py-2.5 text-sm font-bold text-white">Guardar revisión</button>
                    </form>
                    @if($revision)
                        <div class="mt-4 border-t border-slate-200 pt-4">
                            @foreach($revision->comentarios as $comentario)<div class="mb-2 rounded-md bg-white px-4 py-3 text-sm"><span class="font-bold">{{ $comentario->autor?->nombre }}:</span> {{ $comentario->comentario }}</div>@endforeach
                            <form method="POST" action="{{ route('docente-materia.principal.comentar', $entrega) }}" class="flex flex-col gap-2 md:flex-row">@csrf<input required maxlength="1500" name="comentario" placeholder="Agregar comentario técnico" class="min-w-0 flex-1 rounded-md border-slate-300 text-sm"><button class="rounded-md border border-[#155AA3] px-4 py-2 text-sm font-bold text-[#155AA3]">Comentar</button></form>
                        </div>
                    @endif
                </div>
            </article>
        @empty
            <div class="bg-white px-5 py-12 text-center"><p class="font-semibold text-slate-700">No hay entregas de código en tus grupos.</p><p class="mt-1 text-sm text-slate-500">Aparecerán cuando los equipos envíen un apartado que requiera código.</p></div>
        @endforelse
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const buttons = [...document.querySelectorAll('.pagina-grupo')];
            const items = [...document.querySelectorAll('.entrega-codigo')];
            const activate = button => {
                buttons.forEach(item => {
                    const active = item === button;
                    item.style.borderColor = active ? '#155AA3' : '#CBD5E1';
                    item.style.backgroundColor = active ? '#155AA3' : '#FFF';
                    item.style.color = active ? '#FFF' : '#155AA3';
                });
                items.forEach(item => item.classList.toggle('hidden', item.dataset.grupo !== button.dataset.grupo));
            };
            buttons.forEach(button => button.addEventListener('click', () => activate(button)));
            if (buttons[0]) activate(buttons[0]);
        });
    </script>
</x-contenedor-aplicacion>
