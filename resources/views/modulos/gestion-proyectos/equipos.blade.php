<x-contenedor-aplicacion title="Equipos | Administración de proyectos" active="equipos" :navegacion="$navegacion" :role-name="$roleName">
    <section class="border-b border-slate-200 pb-5">
        <p class="text-sm font-semibold uppercase tracking-[0.18em] text-[#21A366]">Trabajo colaborativo</p>
        <h2 class="mt-2 text-2xl font-bold text-[#0D376D]">Equipos de trabajo</h2>
        <p class="mt-2 max-w-4xl text-sm leading-6 text-slate-600">Selecciona un equipo para consultar sus alumnos, docente líder y proyecto. Los datos faltantes pueden completarse dentro del mismo panel.</p>
    </section>

    <x-mensajes-formulario />

    <section data-async-region="metricas-equipos" class="mt-5 grid gap-3 md:grid-cols-3">
        @foreach ($metricasEquipos as $metrica)
            <article class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">{{ $metrica['label'] }}</p>
                <p class="mt-2 text-2xl font-bold text-[#0D376D]">{{ $metrica['value'] }}</p>
            </article>
        @endforeach
    </section>

    @php
        $hayGruposConAlumnosLibres = $grupos->contains(
            fn ($grupo) => $grupo->alumnos_libres_count > 0
        );
    @endphp
    <details data-async-region="acciones-equipos" class="group mt-5 rounded-lg border border-slate-200 bg-white shadow-sm" @if($errors->any()) open @endif>
        <summary class="flex cursor-pointer list-none items-center justify-between gap-4 px-5 py-4">
            <div>
                <h3 class="font-bold text-slate-900">Crear un equipo</h3>
                <p class="mt-1 text-sm text-slate-500">Registra un equipo dentro de uno de tus grupos.</p>
            </div>
            <span class="rounded-md bg-[#15529A] px-3 py-2 text-sm font-bold text-white">Nuevo equipo</span>
        </summary>
        <form method="POST" action="{{ route('equipos.guardar') }}" class="grid gap-4 border-t border-slate-200 p-5 lg:grid-cols-2" data-async-form data-async-targets="mensajes metricas-equipos acciones-equipos listado-equipos">
            @csrf
            <input type="hidden" name="periodo_equipos" value="{{ $periodoSeleccionado }}">
            <input type="hidden" name="grupo_equipos" value="{{ $grupoSeleccionado }}">
            <label class="text-sm font-semibold text-slate-700">Grupo
                <select name="grupo_academico_id" required class="mt-2 block w-full rounded-md border-slate-300 text-sm">
                    @foreach ($grupos as $grupo)
                        <option value="{{ $grupo->id }}" @disabled($grupo->alumnos_libres_count === 0)>
                            {{ $grupo->carrera->clave }} - {{ $grupo->grado }}{{ $grupo->grupo }} · {{ $grupo->alumnos_libres_count }} alumnos libres
                        </option>
                    @endforeach
                </select>
            </label>
            <label class="text-sm font-semibold text-slate-700">Nombre
                <input name="nombre" value="{{ old('nombre') }}" required class="mt-2 block w-full rounded-md border-slate-300 text-sm" placeholder="Equipo 1">
            </label>
            <label class="text-sm font-semibold text-slate-700">Contexto inicial
                <textarea name="contexto_proyecto" rows="3" class="mt-2 block w-full rounded-md border-slate-300 text-sm" placeholder="Problema o necesidad que atenderá">{{ old('contexto_proyecto') }}</textarea>
            </label>
            <div class="lg:col-span-2">
                @if($hayGruposConAlumnosLibres)
                    <p class="mb-3 text-sm text-slate-500">El número se asignará automáticamente después del último equipo del grupo.</p>
                    <button class="rounded-md bg-[#15529A] px-4 py-2.5 text-sm font-bold text-white hover:bg-[#0D376D]">Guardar equipo</button>
                @else
                    <p class="rounded-md bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-700">No hay alumnos libres en tus grupos para crear otro equipo.</p>
                @endif
            </div>
        </form>
    </details>

    <section data-async-region="listado-equipos" class="mt-5 overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col gap-3 border-b border-slate-200 px-5 py-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h3 class="font-bold text-slate-900">Equipos por grupo</h3>
                <p class="mt-1 text-sm text-slate-500">Cada botón muestra la lista completa de equipos del grupo.</p>
            </div>
            <form method="GET" action="{{ route('modulos.show', 'equipos') }}" data-async-form data-async-targets="listado-equipos metricas-equipos acciones-equipos" class="flex items-end gap-2">
                <label class="text-xs font-bold uppercase tracking-wide text-slate-500">Periodo
                    <select name="periodo_equipos" class="mt-1 block rounded-md border-slate-300 text-sm" onchange="this.form.requestSubmit()">
                        @foreach($periodos as $periodo)
                            <option value="{{ $periodo->id }}" @selected((int) $periodoSeleccionado === (int) $periodo->id)>{{ $periodo->nombre }}</option>
                        @endforeach
                    </select>
                </label>
            </form>
        </div>

        <nav class="flex flex-wrap gap-2 border-b border-slate-200 bg-slate-50 px-5 py-3" aria-label="Equipos por grupo">
            @forelse($grupos as $grupo)
                <a href="{{ route('modulos.show', ['modulo' => 'equipos', 'periodo_equipos' => $periodoSeleccionado, 'grupo_equipos' => $grupo->id]) }}"
                   data-async-link data-async-targets="listado-equipos metricas-equipos acciones-equipos"
                   class="rounded-md px-4 py-2 text-sm font-bold transition {{ (int) $grupoSeleccionado === (int) $grupo->id ? 'bg-[#0D376D] text-white shadow-sm' : 'border border-slate-300 bg-white text-[#15529A] hover:bg-[#EAF2FB]' }}">
                    {{ $grupo->carrera->clave }} · {{ $grupo->grado }}{{ $grupo->grupo }}
                </a>
            @empty
                <span class="text-sm text-slate-500">No tienes grupos asignados en este periodo.</span>
            @endforelse
        </nav>

        <div class="hidden grid-cols-[90px_minmax(180px,1fr)_120px_150px_170px] gap-4 border-b border-slate-200 bg-slate-50 px-5 py-3 text-xs font-bold uppercase tracking-wide text-slate-500 lg:grid">
            <span>Equipo</span><span>Nombre y grupo</span><span>Alumnos</span><span>Proyecto</span><span class="text-right">Detalle</span>
        </div>

        <div class="divide-y divide-slate-200">
            @forelse($equipos as $equipo)
                @php
                    $proyecto = $equipo->proyectos->first();
                    $alumnosDisponibles = $estudiantes
                        ->where('grupo_academico_id', $equipo->grupo_academico_id)
                        ->whereNotIn('id', $equipo->integrantes->pluck('id'));
                    $guiasDisponibles = $guias->filter(fn ($guia) =>
                        (int) $guia->periodo_id === (int) $equipo->grupoAcademico->periodo_id
                        && (int) $guia->asignatura?->carrera_id === (int) $equipo->grupoAcademico->carrera_id
                        && (int) $guia->cuatrimestre === (int) $equipo->grupoAcademico->grado
                    );
                @endphp
                <details class="group">
                    <summary class="grid cursor-pointer list-none gap-4 px-5 py-4 transition hover:bg-slate-50 lg:grid-cols-[90px_minmax(180px,1fr)_120px_150px_170px] lg:items-center">
                        <div>
                            <p class="text-xs font-bold uppercase text-[#21A366]">N.º</p>
                            <p class="mt-1 text-xl font-bold text-[#0D376D]">{{ $equipo->numero ?? '—' }}</p>
                        </div>
                        <div>
                            <p class="font-bold text-slate-900">{{ $equipo->nombre }}</p>
                            <p class="mt-1 text-sm text-slate-500">{{ $equipo->grupoAcademico->carrera->clave }} · {{ $equipo->grupoAcademico->grado }}{{ $equipo->grupoAcademico->grupo }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-slate-500 lg:hidden">Alumnos</p>
                            <p class="font-bold text-slate-900">{{ $equipo->integrantes_count }}</p>
                        </div>
                        <div>
                            <span class="inline-flex rounded-full px-3 py-1 text-xs font-bold {{ $proyecto ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}">{{ $proyecto ? 'Asignado' : 'Pendiente' }}</span>
                        </div>
                        <div class="flex items-center justify-end gap-2 text-sm font-bold text-[#15529A]">
                            <span class="group-open:hidden">Ver información</span>
                            <span class="hidden group-open:inline">Cerrar</span>
                            <span class="transition group-open:rotate-180">⌄</span>
                        </div>
                    </summary>

                    <div class="border-t border-slate-200 bg-slate-50/60 p-5">
                        <div class="grid gap-5 xl:grid-cols-3">
                            <article class="rounded-lg border border-slate-200 bg-white p-4">
                                <p class="text-xs font-bold uppercase tracking-[0.15em] text-[#21A366]">Docente líder</p>
                                @if($equipo->grupoAcademico->liderProyecto)
                                    <p class="mt-3 font-bold text-slate-900">{{ $equipo->grupoAcademico->liderProyecto->nombre }}</p>
                                    <p class="mt-1 text-sm text-slate-500">{{ $equipo->grupoAcademico->liderProyecto->matricula }}</p>
                                @else
                                    <p class="mt-3 text-sm font-semibold text-amber-700">Sin docente líder. Coordinación debe asignarlo al grupo.</p>
                                @endif
                                <p class="mt-4 text-xs text-slate-500">{{ $equipo->grupoAcademico->periodo->nombre }}</p>
                            </article>

                            <article class="rounded-lg border border-slate-200 bg-white p-4 xl:col-span-2">
                                <p class="text-xs font-bold uppercase tracking-[0.15em] text-[#21A366]">Proyecto</p>
                                @if($proyecto)
                                    <div class="mt-3">
                                        <p class="font-bold text-slate-900">{{ $proyecto->titulo }}</p>
                                        <p class="mt-1 text-sm text-slate-600">{{ $proyecto->descripcion ?: 'Sin descripción.' }}</p>
                                        <p class="mt-3 text-xs font-semibold text-[#15529A]">Guía: {{ $proyecto->guiaIntegradora->nombre }} · v{{ $proyecto->guiaIntegradora->version }}</p>
                                    </div>
                                @elseif($guiasDisponibles->isNotEmpty())
                                    <form method="POST" action="{{ route('proyectos.guardar') }}" class="mt-3 grid gap-3 md:grid-cols-2" data-async-form data-async-targets="mensajes metricas-equipos listado-equipos">
                                        @csrf
                                        <input type="hidden" name="equipo_id" value="{{ $equipo->id }}">
                                        <input type="hidden" name="origen" value="equipos">
                                        <input type="hidden" name="periodo_equipos" value="{{ $periodoSeleccionado }}">
                                        <input type="hidden" name="grupo_equipos" value="{{ $grupoSeleccionado }}">
                                        <select name="guia_integradora_id" required class="rounded-md border-slate-300 text-sm">
                                            <option value="">Seleccionar guía</option>
                                            @foreach($guiasDisponibles as $guia)
                                                <option value="{{ $guia->id }}">{{ $guia->nombre }} · v{{ $guia->version }}</option>
                                            @endforeach
                                        </select>
                                        <input name="titulo" required class="rounded-md border-slate-300 text-sm" placeholder="Nombre del proyecto">
                                        <textarea name="descripcion" rows="2" class="rounded-md border-slate-300 text-sm md:col-span-2" placeholder="Descripción breve del proyecto"></textarea>
                                        <button class="w-fit rounded-md bg-[#15529A] px-4 py-2 text-sm font-bold text-white md:col-span-2">Asignar proyecto</button>
                                    </form>
                                @else
                                    <p class="mt-3 text-sm font-semibold text-amber-700">No existe una guía publicada compatible con el periodo, carrera y cuatrimestre de este equipo.</p>
                                @endif
                            </article>
                        </div>

                        <article class="mt-5 rounded-lg border border-slate-200 bg-white">
                            <div class="flex flex-col gap-3 border-b border-slate-200 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <p class="text-xs font-bold uppercase tracking-[0.15em] text-[#21A366]">Alumnos del equipo</p>
                                    <p class="mt-1 text-sm text-slate-500">{{ $equipo->integrantes_count }} integrantes asignados</p>
                                </div>
                                @if($alumnosDisponibles->isNotEmpty())
                                    <form method="POST" action="{{ route('equipos.alumnos.guardar') }}" class="flex flex-col gap-2 sm:flex-row" data-async-form data-async-targets="mensajes metricas-equipos listado-equipos">
                                        @csrf
                                        <input type="hidden" name="equipo_id" value="{{ $equipo->id }}">
                                        <input type="hidden" name="periodo_equipos" value="{{ $periodoSeleccionado }}">
                                        <input type="hidden" name="grupo_equipos" value="{{ $grupoSeleccionado }}">
                                        <select name="estudiante_id" required class="min-w-64 rounded-md border-slate-300 text-sm">
                                            <option value="">Agregar alumno del grupo</option>
                                            @foreach($alumnosDisponibles as $estudiante)
                                                <option value="{{ $estudiante->id }}">{{ $estudiante->matricula }} - {{ $estudiante->nombre }}</option>
                                            @endforeach
                                        </select>
                                        <button class="rounded-md bg-[#15529A] px-3 py-2 text-sm font-bold text-white">Agregar</button>
                                    </form>
                                @endif
                            </div>
                            <div class="divide-y divide-slate-100">
                                @forelse($equipo->integrantes as $integrante)
                                    <div class="flex items-center justify-between gap-4 px-4 py-3">
                                        <div>
                                            <p class="text-sm font-semibold text-slate-900">{{ $integrante->nombre }}</p>
                                            <p class="mt-1 text-xs text-slate-500">{{ $integrante->matricula }}</p>
                                        </div>
                                        <form method="POST" action="{{ route('equipos.alumnos.quitar') }}" data-async-form data-async-targets="mensajes metricas-equipos listado-equipos">
                                            @csrf
                                            @method('DELETE')
                                            <input type="hidden" name="equipo_id" value="{{ $equipo->id }}">
                                            <input type="hidden" name="estudiante_id" value="{{ $integrante->id }}">
                                            <input type="hidden" name="periodo_equipos" value="{{ $periodoSeleccionado }}">
                                            <input type="hidden" name="grupo_equipos" value="{{ $grupoSeleccionado }}">
                                            <button class="rounded-md border border-red-200 px-3 py-1.5 text-xs font-bold text-red-700 hover:bg-red-50">Retirar</button>
                                        </form>
                                    </div>
                                @empty
                                    <p class="px-4 py-6 text-center text-sm text-slate-500">Este equipo todavía no tiene alumnos.</p>
                                @endforelse
                            </div>
                        </article>
                    </div>
                </details>
            @empty
                <div class="px-5 py-12 text-center">
                    <p class="font-semibold text-slate-700">Todavía no hay equipos en tus grupos.</p>
                    <p class="mt-1 text-sm text-slate-500">Usa “Nuevo equipo” para registrar el primero.</p>
                </div>
            @endforelse
        </div>
    </section>
</x-contenedor-aplicacion>
