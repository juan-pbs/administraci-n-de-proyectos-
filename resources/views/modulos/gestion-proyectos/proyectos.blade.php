<x-contenedor-aplicacion title="Proyectos | Administración de proyectos" active="proyectos" :navegacion="$navegacion" :role-name="$roleName">
    <section class="border-b border-slate-200 pb-5">
        <p class="text-sm font-semibold uppercase tracking-[0.18em] text-[#21A366]">Proyecto integrador</p>
        <h2 class="mt-2 text-2xl font-bold text-[#0D376D]">Proyectos asignados</h2>
        <p class="mt-2 max-w-4xl text-sm leading-6 text-slate-600">Consulta los proyectos de tus equipos y despliega cada registro para incorporar docentes y asignaturas participantes.</p>
    </section>

    <x-mensajes-formulario />

    <section data-async-region="metricas-proyectos" class="mt-5 grid gap-3 md:grid-cols-3">
        @foreach ($metricasProyectos as $metrica)
            <article class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">{{ $metrica['label'] }}</p>
                <p class="mt-2 text-2xl font-bold text-[#0D376D]">{{ $metrica['value'] }}</p>
            </article>
        @endforeach
    </section>

    <section data-async-region="listado-proyectos" class="mt-5 overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col gap-3 border-b border-slate-200 px-5 py-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h3 class="font-bold text-slate-900">Proyectos por grupo</h3>
                <p class="mt-1 text-sm text-slate-500">Cada botón muestra todos los proyectos registrados para ese grupo.</p>
            </div>
            <form method="GET" action="{{ route('modulos.show', 'proyectos') }}" data-async-form data-async-targets="listado-proyectos metricas-proyectos" class="flex items-end gap-2">
                <label class="text-xs font-bold uppercase tracking-wide text-slate-500">Periodo
                    <select name="periodo_proyectos" class="mt-1 block rounded-md border-slate-300 text-sm" onchange="this.form.requestSubmit()">
                        @foreach($periodos as $periodo)
                            <option value="{{ $periodo->id }}" @selected((int) $periodoSeleccionado === (int) $periodo->id)>{{ $periodo->nombre }}</option>
                        @endforeach
                    </select>
                </label>
            </form>
        </div>

        <nav class="flex flex-wrap gap-2 border-b border-slate-200 bg-slate-50 px-5 py-3" aria-label="Proyectos por grupo">
            @forelse($grupos as $grupo)
                <a href="{{ route('modulos.show', ['modulo' => 'proyectos', 'periodo_proyectos' => $periodoSeleccionado, 'grupo_proyectos' => $grupo->id]) }}"
                   data-async-link data-async-targets="listado-proyectos metricas-proyectos"
                   class="rounded-md px-4 py-2 text-sm font-bold transition {{ (int) $grupoSeleccionado === (int) $grupo->id ? 'bg-[#0D376D] text-white shadow-sm' : 'border border-slate-300 bg-white text-[#15529A] hover:bg-[#EAF2FB]' }}">
                    {{ $grupo->carrera->clave }} · {{ $grupo->grado }}{{ $grupo->grupo }}
                </a>
            @empty
                <span class="text-sm text-slate-500">No tienes grupos asignados en este periodo.</span>
            @endforelse
        </nav>

        <div class="hidden grid-cols-[minmax(220px,1fr)_180px_180px_120px_170px] gap-4 border-b border-slate-200 bg-slate-50 px-5 py-3 text-xs font-bold uppercase tracking-wide text-slate-500 lg:grid">
            <span>Proyecto</span><span>Equipo</span><span>Guía</span><span>Estado</span><span class="text-right">Detalle</span>
        </div>
        <div class="divide-y divide-slate-200">
            @forelse($proyectos as $proyecto)
                @php
                    $carreraId = $proyecto->equipo->grupoAcademico->carrera_id;
                    $grado = $proyecto->equipo->grupoAcademico->grado;
                    $docentesDisponibles = $docentes->where('carrera_id', $carreraId)->whereNotIn('id', $proyecto->docentes->pluck('id'));
                    $asignaturasDisponibles = $asignaturas->where('carrera_id', $carreraId)->where('grado', $grado)->whereNotIn('id', $proyecto->asignaturas->pluck('id'));
                @endphp
                <details class="group">
                    <summary class="grid cursor-pointer list-none gap-4 px-5 py-4 transition hover:bg-slate-50 lg:grid-cols-[minmax(220px,1fr)_180px_180px_120px_170px] lg:items-center">
                        <div>
                            <p class="font-bold text-[#0D376D]">{{ $proyecto->titulo }}</p>
                            <p class="mt-1 line-clamp-1 text-sm text-slate-500">{{ $proyecto->descripcion ?: 'Sin descripción.' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-slate-400 lg:hidden">Equipo</p>
                            <p class="text-sm font-semibold text-slate-800">{{ $proyecto->equipo->nombre }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ $proyecto->equipo->grupoAcademico->carrera->clave }} · {{ $proyecto->equipo->grupoAcademico->grado }}{{ $proyecto->equipo->grupoAcademico->grupo }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-slate-400 lg:hidden">Guía</p>
                            <p class="text-sm text-slate-700">{{ $proyecto->guiaIntegradora->nombre }}</p>
                            <p class="mt-1 text-xs text-slate-500">Versión {{ $proyecto->guiaIntegradora->version }}</p>
                        </div>
                        <div><span class="rounded-full bg-[#EAF7EF] px-3 py-1 text-xs font-bold text-[#0F7D47]">{{ ucfirst(str_replace('_', ' ', $proyecto->estado)) }}</span></div>
                        <div class="flex items-center justify-end gap-2 text-sm font-bold text-[#15529A]">
                            <span class="group-open:hidden">Administrar</span><span class="hidden group-open:inline">Cerrar</span><span class="transition group-open:rotate-180">⌄</span>
                        </div>
                    </summary>

                    <div class="border-t border-slate-200 bg-slate-50/60 p-5">
                        <a href="{{ route('documentos.mostrar', ['proyecto' => $proyecto, 'origen' => 'modulos.proyectos', 'contexto' => request()->only('periodo_proyectos', 'grupo_proyectos')]) }}" class="mb-4 inline-block text-sm font-bold text-[#155AA3] underline">Formato final y firmas</a>
                        <div class="grid gap-5 xl:grid-cols-2">
                            <article class="rounded-lg border border-slate-200 bg-white">
                                <div class="border-b border-slate-200 px-4 py-3">
                                    <p class="text-xs font-bold uppercase tracking-[0.15em] text-[#21A366]">Docentes participantes</p>
                                </div>
                                <div class="divide-y divide-slate-100">
                                    @forelse($proyecto->docentes as $docente)
                                        <div class="flex items-center justify-between gap-3 px-4 py-3">
                                            <div><p class="text-sm font-semibold text-slate-900">{{ $docente->nombre }}</p><p class="mt-1 text-xs text-slate-500">{{ ucfirst(str_replace('_', ' ', $docente->pivot->tipo_participacion)) }}</p></div>
                                            <form method="POST" action="{{ route('proyectos.docentes.quitar') }}" data-async-form data-async-targets="mensajes metricas-proyectos listado-proyectos">
                                                @csrf @method('DELETE')
                                                <input type="hidden" name="proyecto_id" value="{{ $proyecto->id }}"><input type="hidden" name="docente_id" value="{{ $docente->id }}">
                                                <input type="hidden" name="periodo_proyectos" value="{{ $periodoSeleccionado }}"><input type="hidden" name="grupo_proyectos" value="{{ $grupoSeleccionado }}">
                                                <button class="rounded-md border border-red-200 px-3 py-1.5 text-xs font-bold text-red-700 hover:bg-red-50">Retirar</button>
                                            </form>
                                        </div>
                                    @empty
                                        <p class="px-4 py-4 text-sm text-slate-500">Sin docentes participantes.</p>
                                    @endforelse
                                </div>
                                @if($docentesDisponibles->isNotEmpty())
                                    <form method="POST" action="{{ route('proyectos.docentes.guardar') }}" class="grid gap-2 border-t border-slate-200 p-4 sm:grid-cols-[1fr_170px_auto]" data-async-form data-async-targets="mensajes metricas-proyectos listado-proyectos">
                                        @csrf
                                        <input type="hidden" name="proyecto_id" value="{{ $proyecto->id }}">
                                        <input type="hidden" name="periodo_proyectos" value="{{ $periodoSeleccionado }}"><input type="hidden" name="grupo_proyectos" value="{{ $grupoSeleccionado }}">
                                        <select name="docente_id" required class="rounded-md border-slate-300 text-sm"><option value="">Seleccionar docente</option>@foreach($docentesDisponibles as $docente)<option value="{{ $docente->id }}">{{ $docente->matricula }} - {{ $docente->nombre }}</option>@endforeach</select>
                                        <select name="tipo_participacion" class="rounded-md border-slate-300 text-sm"><option value="asesor">Asesor</option><option value="evaluador">Evaluador</option><option value="asesor_evaluador">Asesor y evaluador</option></select>
                                        <button class="rounded-md bg-[#15529A] px-3 py-2 text-sm font-bold text-white">Agregar</button>
                                    </form>
                                @endif
                            </article>

                            <article class="rounded-lg border border-slate-200 bg-white">
                                <div class="border-b border-slate-200 px-4 py-3">
                                    <p class="text-xs font-bold uppercase tracking-[0.15em] text-[#21A366]">Asignaturas participantes</p>
                                </div>
                                <div class="divide-y divide-slate-100">
                                    @forelse($proyecto->asignaturas as $asignatura)
                                        @php($responsable = $docentes->firstWhere('id', $asignatura->pivot->docente_id))
                                        <div class="px-4 py-3"><p class="text-sm font-semibold text-slate-900">{{ $asignatura->clave }} · {{ $asignatura->nombre }}</p><p class="mt-1 text-xs text-slate-500">{{ $responsable?->nombre ?? 'Sin docente responsable' }}</p></div>
                                    @empty
                                        <p class="px-4 py-4 text-sm text-slate-500">Sin asignaturas adicionales.</p>
                                    @endforelse
                                </div>
                                @if($asignaturasDisponibles->isNotEmpty())
                                    <form method="POST" action="{{ route('proyectos.asignaturas.guardar') }}" class="grid gap-2 border-t border-slate-200 p-4 sm:grid-cols-2" data-async-form data-async-targets="mensajes metricas-proyectos listado-proyectos">
                                        @csrf
                                        <input type="hidden" name="proyecto_id" value="{{ $proyecto->id }}">
                                        <input type="hidden" name="periodo_proyectos" value="{{ $periodoSeleccionado }}"><input type="hidden" name="grupo_proyectos" value="{{ $grupoSeleccionado }}">
                                        <select name="asignatura_id" required class="rounded-md border-slate-300 text-sm"><option value="">Seleccionar asignatura</option>@foreach($asignaturasDisponibles as $asignatura)<option value="{{ $asignatura->id }}">{{ $asignatura->clave }} - {{ $asignatura->nombre }}</option>@endforeach</select>
                                        <select name="docente_id" class="rounded-md border-slate-300 text-sm"><option value="">Sin docente responsable</option>@foreach($docentes->where('carrera_id', $carreraId) as $docente)<option value="{{ $docente->id }}">{{ $docente->nombre }}</option>@endforeach</select>
                                        <label class="flex items-center gap-2 text-sm font-semibold text-slate-700"><input name="participa_evaluacion" value="1" type="checkbox" checked class="rounded border-slate-300 text-[#15529A]"> Participa en evaluación</label>
                                        <button class="w-fit rounded-md bg-[#15529A] px-3 py-2 text-sm font-bold text-white">Vincular</button>
                                    </form>
                                @endif
                            </article>
                        </div>
                    </div>
                </details>
            @empty
                <div class="px-5 py-12 text-center"><p class="font-semibold text-slate-700">Todavía no hay proyectos asignados.</p><p class="mt-1 text-sm text-slate-500">Crea el proyecto desde el detalle del equipo correspondiente.</p></div>
            @endforelse
        </div>
    </section>
</x-contenedor-aplicacion>
