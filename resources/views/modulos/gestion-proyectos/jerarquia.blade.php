<x-contenedor-aplicacion title="Docentes líderes | Administración de proyectos" active="jerarquia-proyectos" :navegacion="$navegacion" :role-name="$roleName">
    <section class="flex flex-col gap-4 border-b border-slate-200 pb-5 xl:flex-row xl:items-end xl:justify-between">
        <div>
            <p class="text-sm font-semibold uppercase tracking-[0.18em] text-[#21A366]">Organización académica</p>
            <h2 class="mt-2 text-2xl font-bold text-[#0D376D]">Docentes líderes por grupo</h2>
            <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">Coordinación asigna el docente líder y la asignatura integradora. El docente líder será quien forme equipos y asigne sus proyectos.</p>
        </div>

        <form method="GET" action="{{ route('modulos.jerarquia') }}" class="grid gap-3 sm:grid-cols-2" data-async-form data-async-targets="mensajes panel-jerarquia">
            <label class="text-xs font-bold uppercase tracking-wide text-slate-500">
                Periodo
                <select name="periodo_id" class="mt-1 block min-w-56 rounded-md border-slate-300 text-sm" onchange="this.form.requestSubmit()">
                    @foreach($periodos as $periodo)
                        <option value="{{ $periodo->id }}" @selected($periodoId === $periodo->id)>{{ $periodo->nombre }}</option>
                    @endforeach
                </select>
            </label>
            <label class="text-xs font-bold uppercase tracking-wide text-slate-500">
                Carrera
                <select name="carrera_id" class="mt-1 block min-w-56 rounded-md border-slate-300 text-sm" onchange="this.form.requestSubmit()">
                    @foreach($carreras as $carrera)
                        <option value="{{ $carrera->id }}" @selected($carreraId === $carrera->id)>{{ $carrera->clave }} - {{ $carrera->nombre }}</option>
                    @endforeach
                </select>
            </label>
        </form>
    </section>

    <x-mensajes-formulario />

    <section data-async-region="panel-jerarquia" class="mt-5">
        <article class="rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-col gap-2 border-b border-slate-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h3 class="font-bold text-slate-900">Asignación vigente</h3>
                    <p class="mt-1 text-sm text-slate-500">{{ $grupos->count() }} grupos en la selección actual</p>
                </div>
                <span class="w-fit rounded-full bg-[#EAF7EF] px-3 py-1 text-xs font-bold text-[#0F7D47]">Periodo independiente</span>
            </div>

            <div class="divide-y divide-slate-200">
                @forelse($grupos as $grupo)
                    <form method="POST" action="{{ route('jerarquia.lideres.guardar') }}" class="grid gap-4 p-5 lg:grid-cols-[150px_minmax(220px,1fr)_minmax(220px,1fr)_auto]" data-async-form data-async-targets="mensajes panel-jerarquia">
                        @csrf
                        <input type="hidden" name="grupo_academico_id" value="{{ $grupo->id }}">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-[0.14em] text-[#21A366]">{{ $grupo->carrera->clave }}</p>
                            <p class="mt-1 text-2xl font-bold text-[#0D376D]">{{ $grupo->grado }}{{ $grupo->grupo }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ $grupo->periodo->nombre }}</p>
                        </div>
                        <label class="text-sm font-semibold text-slate-700">
                            Docente líder
                            <select name="lider_proyecto_id" required class="mt-2 block w-full rounded-md border-slate-300 text-sm">
                                <option value="">Seleccionar docente</option>
                                @foreach($lideres as $lider)
                                    <option value="{{ $lider->id }}" @selected($grupo->lider_proyecto_id === $lider->id)>{{ $lider->matricula }} - {{ $lider->nombre }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="text-sm font-semibold text-slate-700">
                            Asignatura líder
                            <select name="asignatura_lider_id" required class="mt-2 block w-full rounded-md border-slate-300 text-sm">
                                <option value="">Seleccionar asignatura</option>
                                @foreach($asignaturas->where('grado', $grupo->grado) as $asignatura)
                                    <option value="{{ $asignatura->id }}" @selected($grupo->asignatura_lider_id === $asignatura->id)>{{ $asignatura->clave }} - {{ $asignatura->nombre }}</option>
                                @endforeach
                            </select>
                        </label>
                        <div class="flex items-end">
                            <button class="w-full rounded-md bg-[#15529A] px-4 py-2.5 text-sm font-bold text-white transition hover:bg-[#0D376D] lg:w-auto">Guardar asignación</button>
                        </div>
                    </form>
                @empty
                    <div class="px-5 py-12 text-center">
                        <p class="font-semibold text-slate-700">No hay grupos en esta carrera y periodo.</p>
                        <p class="mt-1 text-sm text-slate-500">Créelos primero desde Carreras y grupos.</p>
                    </div>
                @endforelse
            </div>
        </article>
    </section>
</x-contenedor-aplicacion>
