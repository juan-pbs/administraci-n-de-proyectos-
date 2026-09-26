<x-contenedor-aplicacion title="Entregas | Administración de proyectos" active="mis-entregas" :navegacion="$navegacion" :role-name="$roleName">
    <section class="border-b border-slate-200 pb-5"><p class="text-sm font-semibold uppercase tracking-[.18em] text-[#21A366]">Avances de la guía</p><h2 class="mt-3 text-2xl font-bold text-[#0D376D]">Entregas del proyecto</h2><p class="mt-2 text-sm text-slate-600">Envía evidencias y consulta calificaciones, correcciones y comentarios por apartado.</p></section>
    @if(session('status'))<div class="mt-5 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-700">{{ session('status') }}</div>@endif
    @if($errors->any())<div class="mt-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $errors->first() }}</div>@endif
    <section class="mt-5 space-y-3">
        @include('modulos.estudiante.selector-proyecto')
        @forelse($apartados as $apartado)
            @php($historial = $entregas->get($apartado->id, collect()))
            @php($ultima = $historial->first())
            @php($revision = $ultima?->revisiones?->first())
            @php($enPlazo = $apartado->habilitado_para_entrega && (!$apartado->fecha_limite || $apartado->fecha_limite->isFuture()))
            <article class="overflow-hidden rounded-lg border border-slate-300 border-l-4 border-l-[#155AA3] bg-white shadow-sm">
                <button type="button" class="abrir-apartado grid w-full gap-3 px-5 py-4 text-left md:grid-cols-[80px_minmax(0,1fr)_210px_140px] md:items-center">
                    <p class="text-xl font-extrabold text-[#0D376D]">#{{ $apartado->orden }}</p><div><p class="font-bold text-slate-900">{{ $apartado->titulo }}</p><p class="mt-1 text-sm text-slate-500">{{ number_format($apartado->ponderacion, 0) }}% · {{ $apartado->fecha_limite?->format('d/m/Y H:i') ?? 'Sin fecha límite' }}</p></div><div><p class="text-xs text-slate-500">Última entrega</p><p class="mt-1 font-bold">{{ $ultima ? 'Versión '.$ultima->version : 'Sin entrega' }}</p>@if($ultima)<p class="mt-1 text-xs font-semibold text-[#155AA3]">Por {{ $ultima->entregadoPor?->nombre ?? 'Integrante del equipo' }}</p>@endif</div><span class="w-fit rounded-full px-3 py-1 text-xs font-bold {{ $revision ? 'bg-blue-50 text-blue-700' : ($ultima ? 'bg-amber-50 text-amber-700' : 'bg-slate-100 text-slate-600') }}">{{ $revision ? ucfirst($revision->resultado) : ($ultima ? 'Pendiente de revisión' : 'Pendiente') }}</span>
                </button>
                <div class="detalle-apartado hidden border-t border-slate-200 bg-slate-50 p-5">
                    @if($revision)<div class="mb-4 rounded-md border {{ $revision->resultado === 'correccion' ? 'border-amber-200 bg-amber-50' : 'border-blue-200 bg-blue-50' }} p-4"><div class="flex flex-wrap justify-between gap-2"><p class="font-bold text-slate-800">Calificación: {{ $revision->calificacion }}/10</p><p class="text-sm font-semibold text-slate-600">{{ ucfirst($revision->resultado) }}</p></div>@if($revision->observaciones)<p class="mt-2 text-sm text-slate-700">{{ $revision->observaciones }}</p>@endif @foreach($revision->comentarios as $comentario)<p class="mt-2 border-t border-slate-200 pt-2 text-sm"><strong>{{ $comentario->autor?->nombre }}:</strong> {{ $comentario->comentario }}</p>@endforeach</div>@endif
                    @if(!$apartado->requiere_codigo && $enPlazo)
                        <form method="POST" enctype="multipart/form-data" action="{{ route('estudiante.entregas.guardar', $apartado) }}" class="flex flex-col gap-3 md:flex-row md:items-end">@csrf<input type="hidden" name="proyecto_contexto" value="{{ $proyecto->id }}"><label class="min-w-0 flex-1"><span class="text-xs font-bold uppercase text-slate-500">Archivos de evidencia</span><input required multiple name="archivos[]" type="file" class="mt-2 block w-full rounded-md border border-slate-300 bg-white p-2 text-sm"><p class="mt-1 text-xs text-slate-500">Puedes seleccionar hasta 10 archivos en una misma asignación. Máximo 150 MB por archivo.</p></label><button class="rounded-md bg-[#155AA3] px-5 py-2.5 text-sm font-bold text-white">{{ $ultima ? 'Enviar nueva versión' : 'Enviar avance' }}</button></form>
                    @else
                        <p class="text-sm font-semibold {{ $apartado->requiere_codigo ? 'text-violet-700' : 'text-red-700' }}">{{ $apartado->requiere_codigo ? 'Este apartado se entrega desde “Código y repositorio”.' : 'El plazo terminó. La entrega queda disponible para consulta, pero ningún integrante puede reemplazarla.' }}</p>
                    @endif
                    @if($historial->isNotEmpty())<div class="mt-4 border-t border-slate-200 pt-4"><p class="text-xs font-bold uppercase text-slate-500">Historial del equipo</p>@foreach($historial as $entrega)<div class="mt-2 flex flex-wrap items-center justify-between gap-2 text-sm"><span class="font-semibold">Versión {{ $entrega->version }} · {{ $entrega->entregado_en?->format('d/m/Y H:i') }}</span><span class="text-slate-500">Subida por {{ $entrega->entregadoPor?->nombre ?? 'Integrante del equipo' }} · {{ $entrega->archivos->count() }} archivos</span></div>@endforeach</div>@endif
                </div>
            </article>
        @empty
            <div class="rounded-lg border border-slate-200 bg-white px-5 py-12 text-center text-sm text-slate-500">No hay una guía disponible para tu proyecto.</div>
        @endforelse
    </section>
    <script>document.addEventListener('DOMContentLoaded',()=>document.querySelectorAll('.abrir-apartado').forEach(button=>button.addEventListener('click',()=>button.nextElementSibling.classList.toggle('hidden'))));</script>
</x-contenedor-aplicacion>
