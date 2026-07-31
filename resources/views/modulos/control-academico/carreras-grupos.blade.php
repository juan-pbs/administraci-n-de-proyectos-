<x-contenedor-aplicacion title="Carreras y grupos | Administración de proyectos" active="carreras-grupos" :navegacion="$navegacion" :role-name="$roleName">
    <section class="border-b border-slate-200 pb-5">
        <p class="text-sm font-semibold uppercase tracking-[0.18em] text-[#21A366]">Estructura por periodo</p>
        <h2 class="mt-2 text-2xl font-bold text-[#0D376D]">Carreras y grupos</h2>
        <p class="mt-2 max-w-4xl text-sm leading-6 text-slate-600">
            Cada periodo tiene sus propios grupos y alumnos. Las carreras y los docentes permanecen; los grupos, equipos, guías y proyectos se organizan nuevamente en cada ciclo.
        </p>
    </section>

    <x-mensajes-formulario />

    <section data-async-region="career-metrics" class="mt-5 grid gap-3 md:grid-cols-4">
        @foreach ($metricasCarreras as $metrica)
            <article class="rounded-md border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">{{ $metrica['label'] }}</p>
                <p class="mt-2 text-2xl font-bold text-[#0D376D]">{{ $metrica['value'] }}</p>
            </article>
        @endforeach
    </section>

    <section class="mt-5 grid gap-5 xl:grid-cols-3">
        <article class="rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 p-5"><p class="text-xs font-bold uppercase tracking-[0.15em] text-[#21A366]">Catálogo permanente</p><h3 class="mt-1 font-bold text-slate-900">Nueva carrera</h3></div>
            <form method="POST" action="{{ route('carreras.guardar') }}" class="space-y-4 p-5">
                @csrf
                <div><label class="text-sm font-semibold text-slate-700">Nombre de la carrera</label><input name="nombre" value="{{ old('nombre') }}" required placeholder="Tecnologías de la Información" class="mt-2 block w-full rounded-md border-slate-300 text-sm"></div>
                <div><label class="text-sm font-semibold text-slate-700">Clave</label><input name="clave" value="{{ old('clave') }}" required placeholder="TI" class="mt-2 block w-full rounded-md border-slate-300 text-sm uppercase"></div>
                <button class="rounded-md bg-[#15529A] px-4 py-2.5 text-sm font-bold text-white">Guardar carrera</button>
            </form>
        </article>

        <article class="rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 p-5"><p class="text-xs font-bold uppercase tracking-[0.15em] text-[#21A366]">Configuración periódica</p><h3 class="mt-1 font-bold text-slate-900">Nuevo grupo</h3></div>
            <form method="POST" action="{{ route('grupos-academicos.guardar') }}" class="space-y-4 p-5">
                @csrf
                <div><label class="text-sm font-semibold text-slate-700">Periodo</label><select name="periodo_id" required class="mt-2 block w-full rounded-md border-slate-300 text-sm">@foreach($periodos as $periodo)<option value="{{ $periodo->id }}" @selected((string) old('periodo_id', $filtrosGrupos['periodo_id']) === (string) $periodo->id)>{{ $periodo->nombre }}{{ $periodo->estado === 'activo' ? ' · Activo' : '' }}</option>@endforeach</select></div>
                <div><label class="text-sm font-semibold text-slate-700">Carrera</label><select name="carrera_id" required class="mt-2 block w-full rounded-md border-slate-300 text-sm">@foreach($carreras as $carrera)<option value="{{ $carrera->id }}" @selected((string) old('carrera_id', $filtrosGrupos['carrera_id']) === (string) $carrera->id)>{{ $carrera->clave }} - {{ $carrera->nombre }}</option>@endforeach</select></div>
                <div class="grid grid-cols-2 gap-3">
                    <div><label class="text-sm font-semibold text-slate-700">Grado</label><input name="grado" type="number" min="1" max="12" value="{{ old('grado') }}" required class="mt-2 block w-full rounded-md border-slate-300 text-sm"></div>
                    <div><label class="text-sm font-semibold text-slate-700">Grupo</label><input name="grupo" maxlength="10" value="{{ old('grupo') }}" placeholder="A" required class="mt-2 block w-full rounded-md border-slate-300 text-sm uppercase"></div>
                </div>
                <button class="rounded-md bg-[#15529A] px-4 py-2.5 text-sm font-bold text-white">Guardar grupo</button>
            </form>
        </article>

        <article class="rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 p-5"><p class="text-xs font-bold uppercase tracking-[0.15em] text-[#21A366]">Plantilla docente</p><h3 class="mt-1 font-bold text-slate-900">Docente por carrera</h3></div>
            <form method="POST" action="{{ route('docentes-carrera.guardar') }}" class="space-y-4 p-5">
                @csrf
                <div><label class="text-sm font-semibold text-slate-700">Carrera</label><select name="carrera_id" required class="mt-2 block w-full rounded-md border-slate-300 text-sm">@foreach($carreras as $carrera)<option value="{{ $carrera->id }}">{{ $carrera->clave }} - {{ $carrera->nombre }}</option>@endforeach</select></div>
                <div><label class="text-sm font-semibold text-slate-700">Docente</label><select name="docente_id" required class="mt-2 block w-full rounded-md border-slate-300 text-sm">@foreach($docentes as $docente)<option value="{{ $docente->id }}">{{ $docente->matricula }} - {{ $docente->nombre }}</option>@endforeach</select></div>
                <button class="rounded-md bg-[#15529A] px-4 py-2.5 text-sm font-bold text-white">Asignar docente</button>
            </form>
        </article>
    </section>

    <article data-async-region="career-groups" class="mt-5 overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col gap-4 border-b border-slate-200 p-5 xl:flex-row xl:items-end xl:justify-between">
            <div><p class="text-xs font-bold uppercase tracking-[0.15em] text-[#21A366]">Vista operativa</p><h3 class="mt-1 text-lg font-bold text-[#0D376D]">Grupos de la carrera</h3></div>
            <form method="GET" action="{{ route('modulos.show', 'carreras-grupos') }}" data-async-form data-async-targets="career-groups career-metrics" class="flex flex-wrap items-end gap-2">
                <div><label class="block text-xs font-bold uppercase text-slate-500">Periodo</label><select name="periodo_grupos" class="mt-1 rounded-md border-slate-300 text-sm">@foreach($periodos as $periodo)<option value="{{ $periodo->id }}" @selected((string) $filtrosGrupos['periodo_id'] === (string) $periodo->id)>{{ $periodo->nombre }}</option>@endforeach</select></div>
                <div><label class="block text-xs font-bold uppercase text-slate-500">Carrera</label><select name="carrera_grupos" class="mt-1 rounded-md border-slate-300 text-sm">@foreach($carreras as $carrera)<option value="{{ $carrera->id }}" @selected((string) $filtrosGrupos['carrera_id'] === (string) $carrera->id)>{{ $carrera->clave }} - {{ $carrera->nombre }}</option>@endforeach</select></div>
                <div><label class="block text-xs font-bold uppercase text-slate-500">Buscar grupo</label><input name="busqueda_grupos" value="{{ $filtrosGrupos['busqueda'] }}" placeholder="Grado, grupo o periodo" class="mt-1 rounded-md border-slate-300 text-sm"></div>
                <button class="rounded-md bg-[#15529A] px-4 py-2 text-sm font-bold text-white">Cambiar vista</button>
            </form>
        </div>

        <nav class="flex flex-wrap gap-2 border-b border-slate-200 bg-slate-50 px-5 py-3" aria-label="Carreras">
            @foreach ($carreras as $carrera)
                <a href="{{ route('modulos.show', ['modulo' => 'carreras-grupos', 'periodo_grupos' => $filtrosGrupos['periodo_id'], 'carrera_grupos' => $carrera->id, 'busqueda_grupos' => $filtrosGrupos['busqueda']]) }}"
                   data-async-link data-async-targets="career-groups career-metrics"
                   class="rounded-md px-4 py-2 text-sm font-bold transition {{ (int) $filtrosGrupos['carrera_id'] === (int) $carrera->id ? 'bg-[#0D376D] text-white shadow-sm' : 'border border-slate-300 bg-white text-[#15529A] hover:bg-[#EAF2FB]' }}">
                    {{ $carrera->clave }}
                </a>
            @endforeach
        </nav>

        <div class="hidden grid-cols-[110px_minmax(180px,1fr)_100px_100px_minmax(220px,1fr)_100px_170px] gap-4 border-b border-slate-200 bg-slate-50 px-5 py-3 text-xs font-bold uppercase tracking-wide text-slate-500 lg:grid">
            <span>Grupo</span>
            <span>Periodo</span>
            <span>Alumnos</span>
            <span>Equipos</span>
            <span>Docente líder</span>
            <span>Estado</span>
            <span class="text-right">Acción</span>
        </div>
        <div class="divide-y divide-slate-200">
            @forelse ($gruposTabla as $grupoAcademico)
                <section class="grid gap-4 px-5 py-4 transition hover:bg-slate-50 lg:grid-cols-[110px_minmax(180px,1fr)_100px_100px_minmax(220px,1fr)_100px_170px] lg:items-center">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.14em] text-[#21A366]">{{ $grupoAcademico->carrera->clave }}</p>
                        <h4 class="mt-1 text-xl font-bold text-[#0D376D]">{{ $grupoAcademico->grado }}{{ $grupoAcademico->grupo }}</h4>
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase text-slate-400 lg:hidden">Periodo</p>
                        <p class="mt-1 text-sm text-slate-600 lg:mt-0">{{ $grupoAcademico->periodo->nombre }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase text-slate-400 lg:hidden">Alumnos</p>
                        <p class="mt-1 text-lg font-bold text-slate-900 lg:mt-0">{{ $grupoAcademico->alumnos_count }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase text-slate-400 lg:hidden">Equipos</p>
                        <p class="mt-1 text-lg font-bold text-slate-900 lg:mt-0">{{ $grupoAcademico->equipos_count }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase text-slate-400 lg:hidden">Docente líder</p>
                        <p class="mt-1 text-sm font-semibold text-slate-800 lg:mt-0">{{ $grupoAcademico->liderProyecto?->nombre ?? 'Sin asignar' }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase text-slate-400 lg:hidden">Estado</p>
                        <span class="mt-1 inline-flex rounded-full bg-[#EAF2FB] px-3 py-1 text-xs font-bold text-[#15529A] lg:mt-0">{{ ucfirst($grupoAcademico->periodo->estado) }}</span>
                    </div>
                    <div class="lg:text-right">
                        <a href="{{ route('modulos.show', ['modulo' => 'usuarios', 'seccion' => 'alumnos', 'periodo_alumnos' => $grupoAcademico->periodo_id, 'carrera_alumnos' => $grupoAcademico->carrera_id, 'grupo_alumnos' => $grupoAcademico->id]) }}" class="inline-flex rounded-md border border-[#15529A] px-3 py-2 text-sm font-bold text-[#15529A] transition hover:bg-[#EAF2FB]">Ver alumnos</a>
                    </div>
                </section>
            @empty
                <div class="px-5 py-10 text-center text-sm text-slate-500">No hay grupos para esta carrera y periodo.</div>
            @endforelse
        </div>
        <div class="border-t border-slate-200 px-5 py-3 text-sm text-slate-500">Vista completa: <strong class="text-slate-800">{{ $gruposTabla->count() }} grupos</strong></div>
    </article>
</x-contenedor-aplicacion>
