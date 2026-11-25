<x-contenedor-aplicacion title="Entregas | Administración de proyectos" active="mis-entregas" :navegacion="$navegacion" :role-name="$roleName">
    <section class="border-b border-slate-200 pb-5"><p class="text-sm font-semibold uppercase tracking-[.18em] text-[#005227]">Avances de la guía</p><h2 class="mt-3 text-2xl font-bold text-[#0D376D]">Entregas del proyecto</h2><p class="mt-2 text-sm text-slate-600">Envía evidencias y consulta los archivos de tu equipo, sus autores y las revisiones por apartado.</p></section>
    <x-mensajes-formulario />
    <section class="mt-5 space-y-3">
        @include('modulos.estudiante.selector-proyecto')
        @forelse($apartados as $apartado)
            @php($historial = $entregas->get($apartado->id, collect()))
            @php($ultima = $historial->first(fn ($entrega) => $entrega->estado !== 'retirada'))
            @php($revision = $ultima?->revisiones?->first())
            @php($enPlazo = $apartado->habilitado_para_entrega && (!$apartado->fecha_limite || $apartado->fecha_limite->isFuture()))
            <article id="entrega-{{ $apartado->id }}" class="overflow-hidden rounded-lg border border-slate-300 border-l-4 border-l-[#155AA3] bg-white shadow-sm">
                <button id="abrir-entrega-{{ $apartado->id }}" type="button" class="abrir-apartado grid w-full gap-3 px-5 py-4 text-left transition hover:bg-slate-50 md:grid-cols-[80px_minmax(0,1fr)_210px_140px] md:items-center" aria-expanded="false" aria-controls="detalle-entrega-{{ $apartado->id }}">
                    <p class="text-xl font-extrabold text-[#0D376D]">#{{ $apartado->orden }}</p><div><p class="font-bold text-slate-900">{{ $apartado->titulo }}</p><p class="mt-1 text-sm text-slate-500">{{ number_format($apartado->ponderacion, 0) }}% · {{ $apartado->fecha_limite?->format('d/m/Y H:i') ?? 'Sin fecha límite' }}</p></div><div><p class="text-xs text-slate-500">Última entrega</p><p class="mt-1 font-bold">{{ $ultima ? 'Versión '.$ultima->version : 'Sin entrega' }}</p>@if($ultima)<p class="mt-1 text-xs font-semibold text-[#155AA3]">{{ $ultima->entregadoPor ? 'Subida por '.$ultima->entregadoPor->nombre : 'Autor sin registrar' }}</p>@endif</div><div><span class="w-fit rounded-full px-3 py-1 text-xs font-bold {{ $revision ? 'bg-blue-50 text-blue-700' : ($ultima ? 'bg-amber-50 text-amber-700' : 'bg-slate-100 text-slate-600') }}">{{ $revision ? ucfirst($revision->resultado) : ($ultima ? 'Pendiente de revisión' : 'Pendiente') }}</span><span class="mt-2 block text-xs font-bold text-[#155AA3]">Ver apartado <span aria-hidden="true">⌄</span></span></div>
                </button>
                <div id="detalle-entrega-{{ $apartado->id }}" data-abrir-con="#abrir-entrega-{{ $apartado->id }}" class="detalle-apartado hidden border-t border-slate-200 bg-slate-50 p-5">
                    @if($revision)<div class="mb-4 rounded-md border {{ $revision->resultado === 'correccion' ? 'border-amber-200 bg-amber-50' : 'border-blue-200 bg-blue-50' }} p-4"><div class="flex flex-wrap justify-between gap-2"><p class="font-bold text-slate-800">Calificación: {{ $revision->calificacion }}/10</p><p class="text-sm font-semibold text-slate-600">{{ ucfirst($revision->resultado) }}</p></div>@if($revision->observaciones)<p class="mt-2 text-sm text-slate-700">{{ $revision->observaciones }}</p>@endif @foreach($revision->comentarios as $comentario)<p class="mt-2 border-t border-slate-200 pt-2 text-sm"><strong>{{ $comentario->autor?->nombre }}:</strong> {{ $comentario->comentario }}</p>@endforeach</div>@endif
                    @if(!$apartado->requiere_codigo && $enPlazo)
                        <form method="POST" enctype="multipart/form-data" action="{{ route('estudiante.entregas.guardar', $apartado) }}" class="flex flex-col gap-3 md:flex-row md:items-end">@csrf<input type="hidden" name="proyecto_contexto" value="{{ $proyecto->id }}"><label class="min-w-0 flex-1"><span class="text-xs font-bold uppercase text-slate-500">Archivos de evidencia</span><input required multiple name="archivos[]" type="file" accept=".pdf,.doc,.docx,.xlsx,.png,.jpg,.jpeg,.zip,.rar,.7z,.tar,.gz,.sql,.txt,.md" data-max-files="10" data-max-mb="150" class="mt-2 block w-full rounded-md border border-slate-300 bg-white p-2 text-sm"><p class="mt-1 text-xs text-slate-500">Puedes seleccionar hasta 10 archivos en una misma asignación. Máximo 150 MB por archivo.</p></label><button class="rounded-md bg-[#155AA3] px-5 py-2.5 text-sm font-bold text-white">{{ $ultima ? 'Enviar nueva versión' : 'Enviar avance' }}</button></form>
                    @else
                        <p class="text-sm font-semibold {{ $apartado->requiere_codigo ? 'text-violet-700' : 'text-red-700' }}">{{ $apartado->requiere_codigo ? 'Este apartado se entrega desde “Código y repositorio”.' : 'El plazo terminó. La entrega queda disponible para consulta, pero ningún integrante puede reemplazarla.' }}</p>
                    @endif
                    @if($enPlazo && $ultima && !$revision && ($ultima->entregado_por_id === null || (int) $ultima->entregado_por_id === (int) auth()->id()))
                        <x-confirmar-accion class="mt-4" :accion="route('estudiante.entregas.retirar', $ultima)" etiqueta="Retirar última versión"
                            mensaje="Confirmo que quiero retirar esta versión. La versión anterior volverá a estar vigente si existe.">
                            <input type="hidden" name="proyecto_contexto" value="{{ $proyecto->id }}">
                        </x-confirmar-accion>
                    @endif
                    @if($historial->isNotEmpty())
                        <section class="mt-4 border-t border-slate-200 pt-4">
                            <h3 class="text-xs font-bold uppercase text-slate-500">Historial del equipo</h3>
                            <div class="mt-2 space-y-2">
                                @foreach($historial as $entrega)
                                    <div class="rounded-md border border-slate-200 bg-white p-3 text-sm">
                                        <div class="flex flex-wrap items-center justify-between gap-x-3 gap-y-1">
                                            <p class="font-semibold text-slate-900">
                                                Versión {{ $entrega->version }} · {{ $entrega->entregado_en?->format('d/m/Y H:i') }}
                                                @if($entrega->estado === 'retirada')<span class="text-amber-800">· Retirada</span>@endif
                                            </p>
                                            <p class="text-slate-600">
                                                {{ $entrega->entregadoPor ? 'Subida por '.$entrega->entregadoPor->nombre : 'Autor sin registrar' }}
                                            </p>
                                        </div>
                                        @if($entrega->estado !== 'retirada' && $entrega->archivos->isNotEmpty())
                                            <ul class="mt-2 space-y-1">
                                                @foreach($entrega->archivos as $archivo)
                                                    <li>
                                                        <a href="{{ route('estudiante.archivos.descargar', ['archivo' => $archivo, 'proyecto_contexto' => $proyecto->id]) }}" class="boton-secundario">
                                                            Descargar {{ $archivo->nombre_original }}
                                                        </a>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </section>
                    @endif
                </div>
            </article>
        @empty
            <div class="rounded-lg border border-slate-200 bg-white px-5 py-12 text-center text-sm text-slate-500">No hay una guía disponible para tu proyecto.</div>
        @endforelse
    </section>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('.abrir-apartado').forEach(button => {
                button.addEventListener('click', () => {
                    const hidden = button.nextElementSibling.classList.toggle('hidden');
                    button.setAttribute('aria-expanded', String(!hidden));
                });
                if (`#${button.parentElement.id}` === window.location.hash) button.click();
            });
        });
    </script>
</x-contenedor-aplicacion>
