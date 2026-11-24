@use('App\Soporte\FormatosCapturaAcademica')

<x-contenedor-aplicacion title="Carreras y grupos | Administración de proyectos" active="carreras-grupos" :navegacion="$navegacion" :role-name="$roleName">
    <section class="border-b border-slate-200 pb-5">
        <p class="text-sm font-semibold uppercase tracking-[0.18em] text-[#005227]">Estructura por periodo</p>
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
            <div class="border-b border-slate-200 p-5"><p class="text-xs font-bold uppercase tracking-[0.15em] text-[#005227]">Catálogo permanente</p><h3 class="mt-1 font-bold text-slate-900">Nueva carrera</h3></div>
            <form method="POST" action="{{ route('carreras.guardar') }}" class="space-y-4 p-5">
                @csrf
                <div><label class="text-sm font-semibold text-slate-700">Nombre de la carrera (máximo 120 caracteres)</label><input name="nombre" pattern="{{ FormatosCapturaAcademica::NOMBRE_CARRERA }}" title="{{ FormatosCapturaAcademica::MENSAJE_NOMBRE_CARRERA }}" maxlength="120" value="{{ old('nombre') }}" required placeholder="Tecnologías de la Información" class="mt-2 block w-full rounded-md border-slate-300 text-sm"></div>
                <div><label class="text-sm font-semibold text-slate-700">Clave (máximo 20 caracteres)</label><input name="clave" pattern="{{ FormatosCapturaAcademica::CLAVE_CARRERA }}" title="{{ FormatosCapturaAcademica::MENSAJE_CLAVE_CARRERA }}" maxlength="20" value="{{ old('clave') }}" required placeholder="TI" class="mt-2 block w-full rounded-md border-slate-300 text-sm uppercase"></div>
                <button class="rounded-md bg-[#15529A] px-4 py-2.5 text-sm font-bold text-white">Guardar carrera</button>
            </form>
        </article>

        <article class="rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 p-5"><p class="text-xs font-bold uppercase tracking-[0.15em] text-[#005227]">Configuración periódica</p><h3 class="mt-1 font-bold text-slate-900">Nuevo grupo</h3></div>
            <form method="POST" action="{{ route('grupos-academicos.guardar') }}" class="space-y-4 p-5">
                @csrf
                <div><label class="text-sm font-semibold text-slate-700">Periodo</label><select name="periodo_id" required class="mt-2 block w-full rounded-md border-slate-300 text-sm">@foreach($periodos as $periodo)<option value="{{ $periodo->id }}" @selected((string) old('periodo_id', $filtrosGrupos['periodo_id']) === (string) $periodo->id)>{{ $periodo->nombre }}{{ $periodo->estado === 'activo' ? ' · Activo' : '' }}</option>@endforeach</select></div>
                <div><label class="text-sm font-semibold text-slate-700">Carrera</label><select name="carrera_id" required class="mt-2 block w-full rounded-md border-slate-300 text-sm">@foreach($carreras as $carrera)<option value="{{ $carrera->id }}" @selected((string) old('carrera_id', $filtrosGrupos['carrera_id']) === (string) $carrera->id)>{{ $carrera->clave }} - {{ $carrera->nombre }}</option>@endforeach</select></div>
                <div class="grid grid-cols-2 gap-3">
                    <div><label class="text-sm font-semibold text-slate-700">Cuatrimestre</label><input name="grado" type="number" min="1" max="12" value="{{ old('grado') }}" required class="mt-2 block w-full rounded-md border-slate-300 text-sm"></div>
                    <div><label class="text-sm font-semibold text-slate-700">Grupo (A–M)</label><select name="grupo" required class="mt-2 block w-full rounded-md border-slate-300 text-sm">@foreach(range('A', 'M') as $letra)<option value="{{ $letra }}" @selected(mb_strtoupper(old('grupo', 'A')) === $letra)>{{ $letra }}</option>@endforeach</select></div>
                </div>
                <button class="rounded-md bg-[#15529A] px-4 py-2.5 text-sm font-bold text-white">Guardar grupo</button>
            </form>
        </article>

        <article class="rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 p-5"><p class="text-xs font-bold uppercase tracking-[0.15em] text-[#005227]">Plantilla docente</p><h3 class="mt-1 font-bold text-slate-900">Docente por carrera</h3><p class="mt-1 text-sm text-slate-500">Este vínculo no asigna al docente líder de un grupo. Haz esa asignación en Jerarquía.</p></div>
            <form method="POST" action="{{ route('docentes-carrera.guardar') }}" class="space-y-4 p-5">
                @csrf
                <div><label class="text-sm font-semibold text-slate-700">Carrera</label><select name="carrera_id" required class="mt-2 block w-full rounded-md border-slate-300 text-sm">@foreach($carreras as $carrera)<option value="{{ $carrera->id }}">{{ $carrera->clave }} - {{ $carrera->nombre }}</option>@endforeach</select></div>
                <div><label class="text-sm font-semibold text-slate-700">Docente</label><select name="docente_id" required class="mt-2 block w-full rounded-md border-slate-300 text-sm">@foreach($docentes as $docente)<option value="{{ $docente->id }}">{{ $docente->matricula }} - {{ $docente->nombre }}</option>@endforeach</select></div>
                <button class="rounded-md bg-[#15529A] px-4 py-2.5 text-sm font-bold text-white">Vincular docente a carrera</button>
            </form>
        </article>
    </section>

    <article data-async-region="career-groups" class="mt-5 overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col gap-4 border-b border-slate-200 p-5 xl:flex-row xl:items-end xl:justify-between">
            <div><p class="text-xs font-bold uppercase tracking-[0.15em] text-[#005227]">Vista operativa</p><h3 class="mt-1 text-lg font-bold text-[#0D376D]">Grupos de la carrera</h3></div>
            <form method="GET" action="{{ route('modulos.show', 'carreras-grupos') }}" data-async-form data-async-targets="career-groups career-metrics" class="flex flex-wrap items-end gap-2">
                <div><label class="block text-xs font-bold uppercase text-slate-500">Periodo</label><select name="periodo_grupos" onchange="this.form.requestSubmit()" class="mt-1 rounded-md border-slate-300 text-sm">@foreach($periodos as $periodo)<option value="{{ $periodo->id }}" @selected((string) $filtrosGrupos['periodo_id'] === (string) $periodo->id)>{{ $periodo->nombre }}</option>@endforeach</select></div>
                <div><label class="block text-xs font-bold uppercase text-slate-500">Carrera</label><select name="carrera_grupos" onchange="this.form.requestSubmit()" class="mt-1 rounded-md border-slate-300 text-sm">@foreach($carreras as $carrera)<option value="{{ $carrera->id }}" @selected((string) $filtrosGrupos['carrera_id'] === (string) $carrera->id)>{{ $carrera->clave }} - {{ $carrera->nombre }}</option>@endforeach</select></div>
                <div><label class="block text-xs font-bold uppercase text-slate-500">Buscar grupo</label><input name="busqueda_grupos" value="{{ $filtrosGrupos['busqueda'] }}" placeholder="Cuatrimestre, grupo o periodo" class="mt-1 rounded-md border-slate-300 text-sm"></div>
                <button class="rounded-md bg-[#15529A] px-4 py-2 text-sm font-bold text-white">Buscar grupo</button>
            </form>
        </div>

        <nav class="flex flex-wrap gap-2 border-b border-slate-200 bg-slate-50 px-5 py-3" aria-label="Carreras">
            @foreach ($carreras as $carrera)
                <a href="{{ route('modulos.show', ['modulo' => 'carreras-grupos', 'periodo_grupos' => $filtrosGrupos['periodo_id'], 'carrera_grupos' => $carrera->id, 'busqueda_grupos' => $filtrosGrupos['busqueda']]) }}"
                   data-async-link data-async-targets="career-groups career-metrics"
                       title="{{ $carrera->clave }} - {{ $carrera->nombre }}"
                       class="max-w-40 truncate rounded-md px-4 py-2 text-sm font-bold transition {{ (int) $filtrosGrupos['carrera_id'] === (int) $carrera->id ? 'bg-[#0D376D] text-white shadow-sm' : 'border border-slate-300 bg-white text-[#15529A] hover:bg-[#EAF2FB]' }}">
                    {{ $carrera->clave }}
                </a>
            @endforeach
        </nav>
        @if($carreraActual = $carreras->firstWhere('id', (int) $filtrosGrupos['carrera_id']))
            <div class="space-y-2 border-b border-slate-200 p-5">
                <details class="rounded-md border border-slate-200 p-3">
                    <summary class="cursor-pointer text-sm font-bold text-[#15529A]">Editar carrera · {{ $carreraActual->clave }} · {{ ucfirst($carreraActual->estado) }}</summary>
                    <form method="POST" action="{{ route('carreras.actualizar', $carreraActual) }}" class="mt-3 grid gap-3 md:grid-cols-3">
                        @csrf @method('PATCH')
                        <label class="text-sm font-semibold">Nombre<input name="nombre" pattern="{{ FormatosCapturaAcademica::NOMBRE_CARRERA }}" title="{{ FormatosCapturaAcademica::MENSAJE_NOMBRE_CARRERA }}" required maxlength="120" value="{{ $carreraActual->nombre }}" class="mt-1 block w-full rounded-md border-slate-300"></label>
                        <label class="text-sm font-semibold">Clave<input name="clave" pattern="{{ FormatosCapturaAcademica::CLAVE_CARRERA }}" title="{{ FormatosCapturaAcademica::MENSAJE_CLAVE_CARRERA }}" required maxlength="20" value="{{ $carreraActual->clave }}" class="mt-1 block w-full rounded-md border-slate-300"></label>
                        <label class="text-sm font-semibold">Estado<select name="estado" class="mt-1 block w-full rounded-md border-slate-300"><option value="activa" @selected($carreraActual->estado === 'activa')>Activa</option><option value="inactiva" @selected($carreraActual->estado === 'inactiva')>Inactiva</option></select></label>
                        <button class="w-fit rounded-md bg-[#15529A] px-3 py-2 text-sm font-bold text-white">Guardar carrera</button>
                    </form>
                </details>
                <x-confirmar-accion :accion="route('carreras.eliminar', $carreraActual)" etiqueta="Eliminar carrera"
                    mensaje="Confirmo que deseo eliminar la carrera seleccionada si no tiene vínculos académicos." />
            </div>
        @endif

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
                        <p class="break-all text-xs font-bold uppercase tracking-[0.14em] text-[#005227]">{{ $grupoAcademico->carrera->clave }}</p>
                        <h4 class="mt-1 text-xl font-bold text-[#0D376D]">{{ $grupoAcademico->grado }}{{ $grupoAcademico->grupo }}</h4>
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase text-slate-400 lg:hidden">Periodo</p>
                        <p class="mt-1 break-words text-sm text-slate-600 lg:mt-0">{{ $grupoAcademico->periodo->nombre }}</p>
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
                        <span class="mt-1 inline-flex rounded-full bg-[#EAF2FB] px-3 py-1 text-xs font-bold text-[#15529A] lg:mt-0">{{ ucfirst($grupoAcademico->estado) }}</span>
                    </div>
                    <div class="flex flex-wrap gap-2 lg:justify-end">
                        @if($grupoAcademico->periodo->estado !== 'cerrado' && $grupoAcademico->estado === 'activo' && $carreraActual?->estado === 'activa')
                            <a href="{{ route('modulos.jerarquia', ['periodo_id' => $grupoAcademico->periodo_id, 'carrera_id' => $grupoAcademico->carrera_id, 'origen' => 'carreras-grupos']) }}#grupo-{{ $grupoAcademico->id }}" class="inline-flex rounded-md bg-[#15529A] px-3 py-2 text-sm font-bold text-white transition hover:bg-[#0D376D]">Asignar líder</a>
                        @endif
                        <a href="{{ route('modulos.show', ['modulo' => 'usuarios', 'seccion' => 'alumnos', 'periodo_alumnos' => $grupoAcademico->periodo_id, 'carrera_alumnos' => $grupoAcademico->carrera_id, 'grupo_alumnos' => $grupoAcademico->id, 'origen' => 'carreras-grupos']) }}" class="inline-flex rounded-md border border-[#15529A] px-3 py-2 text-sm font-bold text-[#15529A] transition hover:bg-[#EAF2FB]">Ver alumnos</a>
                    </div>
                    @if($grupoAcademico->periodo->estado !== 'cerrado')
                        <div class="space-y-2 lg:col-span-full">
                            <details class="rounded-md border border-slate-200 p-3">
                                <summary class="cursor-pointer text-sm font-bold text-[#15529A]">Editar grupo</summary>
                                <form method="POST" action="{{ route('grupos-academicos.actualizar', $grupoAcademico) }}" class="mt-3 grid gap-3 md:grid-cols-5">
                                    @csrf @method('PATCH')
                                    <label class="text-sm font-semibold">Periodo<select name="periodo_id" class="mt-1 block w-full rounded-md border-slate-300">@foreach($periodos->where('estado', '!=', 'cerrado') as $opcion)<option value="{{ $opcion->id }}" @selected($opcion->id === $grupoAcademico->periodo_id)>{{ $opcion->nombre }}</option>@endforeach</select></label>
                                    <label class="text-sm font-semibold">Carrera<select name="carrera_id" class="mt-1 block w-full rounded-md border-slate-300">@foreach($carreras as $opcion)<option value="{{ $opcion->id }}" @selected($opcion->id === $grupoAcademico->carrera_id)>{{ $opcion->clave }}</option>@endforeach</select></label>
                                    <label class="text-sm font-semibold">Cuatrimestre<input name="grado" type="number" min="1" max="12" required value="{{ $grupoAcademico->grado }}" class="mt-1 block w-full rounded-md border-slate-300"></label>
                                    <label class="text-sm font-semibold">Grupo<select name="grupo" class="mt-1 block w-full rounded-md border-slate-300">@foreach(range('A', 'M') as $letra)<option @selected($letra === $grupoAcademico->grupo)>{{ $letra }}</option>@endforeach</select></label>
                                    <label class="text-sm font-semibold">Estado<select name="estado" class="mt-1 block w-full rounded-md border-slate-300"><option value="activo" @selected($grupoAcademico->estado === 'activo')>Activo</option><option value="inactivo" @selected($grupoAcademico->estado === 'inactivo')>Inactivo</option></select></label>
                                    <button class="w-fit rounded-md bg-[#15529A] px-3 py-2 text-sm font-bold text-white">Guardar grupo</button>
                                </form>
                            </details>
                            <x-confirmar-accion :accion="route('grupos-academicos.eliminar', $grupoAcademico)" etiqueta="Eliminar grupo"
                                mensaje="Confirmo la eliminación del grupo si no tiene alumnos ni equipos." />
                        </div>
                    @endif
                </section>
            @empty
                <div class="px-5 py-10 text-center text-sm text-slate-500">No hay grupos para esta carrera y periodo.</div>
            @endforelse
        </div>
        <div class="border-t border-slate-200 px-5 py-3 text-sm text-slate-500">Vista completa: <strong class="text-slate-800">{{ $gruposTabla->count() }} {{ $gruposTabla->count() === 1 ? 'grupo' : 'grupos' }}</strong></div>
    </article>
</x-contenedor-aplicacion>
