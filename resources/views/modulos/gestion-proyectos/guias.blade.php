<x-contenedor-aplicacion title="Guías | Administración de proyectos" active="guias" :navegacion="$navegacion" :role-name="$roleName">
    <section class="border-b border-slate-200 pb-5">
        <p class="text-sm font-semibold uppercase tracking-[0.18em] text-[#21A366]">Estructura por periodo</p>
        <h2 class="mt-2 text-2xl font-bold text-[#0D376D]">Guías integradoras</h2>
        <p class="mt-2 max-w-4xl text-sm leading-6 text-slate-600">Coordinación configura una guía común para todos los equipos de la misma carrera y cuatrimestre. No se crea una guía por equipo.</p>
    </section>

    <x-mensajes-formulario />

    <section data-async-region="guide-metrics" class="mt-5 grid gap-3 md:grid-cols-3">
        @foreach ($metricasGuias as $metrica)
            <article class="rounded-md border border-slate-200 bg-white p-4 shadow-sm"><p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">{{ $metrica['label'] }}</p><p class="mt-2 text-2xl font-bold text-[#0D376D]">{{ $metrica['value'] }}</p></article>
        @endforeach
    </section>

    <section class="mt-5 grid gap-4 xl:grid-cols-2">
        <details class="rounded-lg border border-slate-200 bg-white shadow-sm" @if($errors->any()) open @endif>
            <summary class="cursor-pointer list-none p-5"><p class="text-xs font-bold uppercase tracking-[0.15em] text-[#21A366]">Alta periódica</p><div class="mt-1 flex items-center justify-between"><h3 class="font-bold text-[#0D376D]">Crear nueva guía</h3><span class="text-xl text-[#15529A]">＋</span></div></summary>
            <form method="POST" action="{{ route('guias.guardar') }}" class="grid gap-4 border-t border-slate-200 p-5 md:grid-cols-2">
                @csrf
                <div><label class="text-sm font-semibold text-slate-700">Periodo</label><select name="periodo_id" required class="mt-2 block w-full rounded-md border-slate-300 text-sm">@foreach($periodos as $periodo)<option value="{{ $periodo->id }}" @selected((string) old('periodo_id', $filtrosGuias['periodo_id']) === (string) $periodo->id)>{{ $periodo->nombre }}</option>@endforeach</select></div>
                <div><label class="text-sm font-semibold text-slate-700">Asignatura principal</label><select name="asignatura_id" required class="mt-2 block w-full rounded-md border-slate-300 text-sm">@foreach($asignaturas as $asignatura)<option value="{{ $asignatura->id }}">{{ $asignatura->carrera?->clave }} - {{ $asignatura->nombre }}</option>@endforeach</select></div>
                <div class="md:col-span-2"><label class="text-sm font-semibold text-slate-700">Nombre</label><input name="nombre" value="{{ old('nombre') }}" required class="mt-2 block w-full rounded-md border-slate-300 text-sm"></div>
                <div><label class="text-sm font-semibold text-slate-700">Cuatrimestre</label><input name="cuatrimestre" type="number" min="1" max="12" value="{{ old('cuatrimestre') }}" placeholder="4" required class="mt-2 block w-full rounded-md border-slate-300 text-sm"></div>
                <div class="grid grid-cols-2 gap-3"><div><label class="text-sm font-semibold text-slate-700">Versión</label><input name="version" value="{{ old('version', '1.0') }}" required class="mt-2 block w-full rounded-md border-slate-300 text-sm"></div><div><label class="text-sm font-semibold text-slate-700">Estado</label><select name="estado" class="mt-2 block w-full rounded-md border-slate-300 text-sm"><option value="borrador">Borrador</option><option value="publicada">Publicada</option><option value="cerrada">Cerrada</option></select></div></div>
                <input type="hidden" name="periodo_fin_id" value="">
                <div><label class="text-sm font-semibold text-slate-700">Competencias</label><textarea name="competencias_evaluar" rows="3" class="mt-2 block w-full rounded-md border-slate-300 text-sm">{{ old('competencias_evaluar') }}</textarea></div>
                <div><label class="text-sm font-semibold text-slate-700">Objetivo de aprendizaje</label><textarea name="objetivo_aprendizaje" rows="3" class="mt-2 block w-full rounded-md border-slate-300 text-sm">{{ old('objetivo_aprendizaje') }}</textarea></div>
                <div class="md:col-span-2"><button class="rounded-md bg-[#15529A] px-5 py-2.5 text-sm font-bold text-white">Guardar guía</button></div>
            </form>
        </details>

        <details class="rounded-lg border border-slate-200 bg-white shadow-sm">
            <summary class="cursor-pointer list-none p-5"><p class="text-xs font-bold uppercase tracking-[0.15em] text-[#21A366]">Configuración</p><div class="mt-1 flex items-center justify-between"><h3 class="font-bold text-[#0D376D]">Apartados y materias</h3><span class="text-xl text-[#15529A]">＋</span></div></summary>
            <div class="space-y-5 border-t border-slate-200 p-5">
                <form method="POST" action="{{ route('guias.apartados.guardar') }}" class="grid gap-3 rounded-md bg-slate-50 p-4 md:grid-cols-2">
                    @csrf
                    <h4 class="font-bold text-slate-900 md:col-span-2">Nuevo apartado</h4>
                    <select name="guia_integradora_id" required class="rounded-md border-slate-300 text-sm">@foreach($guias as $guia)<option value="{{ $guia->id }}">{{ $guia->nombre }} v{{ $guia->version }}</option>@endforeach</select>
                    <div class="grid grid-cols-2 gap-2"><input name="orden" type="number" min="1" value="1" required placeholder="Orden" class="rounded-md border-slate-300 text-sm"><input name="ponderacion" type="number" min="0" max="100" value="10" required placeholder="%" class="rounded-md border-slate-300 text-sm"></div>
                    <input name="titulo" required placeholder="Título del apartado" class="rounded-md border-slate-300 text-sm md:col-span-2">
                    <textarea name="descripcion" rows="3" placeholder="Descripción y puntos requeridos" class="rounded-md border-slate-300 text-sm md:col-span-2"></textarea>
                    <input name="fecha_limite" type="datetime-local" class="rounded-md border-slate-300 text-sm">
                    <div class="flex gap-4 text-sm"><label><input name="requiere_documento" value="1" type="checkbox" checked> Documento</label><label><input name="requiere_codigo" value="1" type="checkbox"> Código</label></div>
                    <button class="w-fit rounded-md bg-[#15529A] px-4 py-2 text-sm font-bold text-white">Guardar apartado</button>
                </form>

                <div>
                    <form method="POST" action="{{ route('guias.apartados.asignaturas.guardar') }}" class="space-y-3 rounded-md border border-slate-200 p-4">
                        @csrf
                        <h4 class="font-bold text-slate-900">Materia contribuyente</h4>
                        <select name="apartado_guia_id" required class="block w-full rounded-md border-slate-300 text-sm">@foreach($apartados as $apartado)<option value="{{ $apartado->id }}">{{ $apartado->guiaIntegradora->nombre }} · {{ $apartado->orden }}. {{ $apartado->titulo }}</option>@endforeach</select>
                        <select name="asignatura_id" required class="block w-full rounded-md border-slate-300 text-sm">@foreach($asignaturas as $asignatura)<option value="{{ $asignatura->id }}">{{ $asignatura->carrera?->clave }} - {{ $asignatura->nombre }}</option>@endforeach</select>
                        <input name="rol_contribucion" placeholder="Contribución" class="block w-full rounded-md border-slate-300 text-sm">
                        <label class="text-sm"><input name="requiere_firma" value="1" type="checkbox" checked> Requiere firma</label>
                        <button class="block rounded-md bg-[#15529A] px-4 py-2 text-sm font-bold text-white">Vincular</button>
                    </form>
                </div>
            </div>
        </details>
    </section>

    <article data-async-region="guides-list" class="mt-5 overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col gap-4 border-b border-slate-200 p-5 xl:flex-row xl:items-end xl:justify-between">
            <div><p class="text-xs font-bold uppercase tracking-[0.15em] text-[#21A366]">Vista operativa</p><h3 class="mt-1 text-lg font-bold text-[#0D376D]">Lista de guías</h3></div>
            <form method="GET" action="{{ route('modulos.show', 'guias') }}" data-async-form data-async-targets="guides-list guide-metrics" class="flex flex-wrap items-end gap-2">
                <div><label class="block text-xs font-bold uppercase text-slate-500">Periodo</label><select name="periodo_guias" class="mt-1 rounded-md border-slate-300 text-sm">@foreach($periodos as $periodo)<option value="{{ $periodo->id }}" @selected((string)$filtrosGuias['periodo_id'] === (string)$periodo->id)>{{ $periodo->nombre }}</option>@endforeach</select></div>
                <div><label class="block text-xs font-bold uppercase text-slate-500">Carrera</label><select name="carrera_guias" class="mt-1 rounded-md border-slate-300 text-sm">@foreach($carreras as $carrera)<option value="{{ $carrera->id }}" @selected((string)$filtrosGuias['carrera_id'] === (string)$carrera->id)>{{ $carrera->clave }} - {{ $carrera->nombre }}</option>@endforeach</select></div>
                <div><label class="block text-xs font-bold uppercase text-slate-500">Estado</label><select name="estado_guias" class="mt-1 rounded-md border-slate-300 text-sm"><option value="">Todos</option>@foreach(['borrador','publicada','cerrada'] as $estado)<option value="{{ $estado }}" @selected($filtrosGuias['estado'] === $estado)>{{ ucfirst($estado) }}</option>@endforeach</select></div>
                <input name="busqueda_guias" value="{{ $filtrosGuias['busqueda'] }}" placeholder="Nombre, versión o materia" class="rounded-md border-slate-300 text-sm">
                <button class="rounded-md bg-[#15529A] px-4 py-2 text-sm font-bold text-white">Cambiar vista</button>
            </form>
        </div>

        <nav class="flex flex-wrap gap-2 border-b border-slate-200 bg-slate-50 px-5 py-3">
            @foreach($carreras as $carrera)
                <a href="{{ route('modulos.show', ['modulo'=>'guias','periodo_guias'=>$filtrosGuias['periodo_id'],'carrera_guias'=>$carrera->id,'estado_guias'=>$filtrosGuias['estado'],'busqueda_guias'=>$filtrosGuias['busqueda']]) }}" data-async-link data-async-targets="guides-list guide-metrics" class="rounded-md px-4 py-2 text-sm font-bold {{ (int)$filtrosGuias['carrera_id'] === (int)$carrera->id ? 'bg-[#0D376D] text-white shadow-sm' : 'border border-slate-300 bg-white text-[#15529A]' }}">{{ $carrera->clave }}</a>
            @endforeach
        </nav>

        <div class="divide-y divide-slate-200">
            @forelse($guias as $guia)
                <details class="group">
                    <summary class="grid cursor-pointer list-none gap-3 p-5 transition hover:bg-slate-50 md:grid-cols-[minmax(0,1fr)_150px_110px_100px_auto] md:items-center">
                        <div><p class="text-xs font-bold uppercase tracking-[0.14em] text-[#21A366]">{{ $guia->asignatura?->clave ?? 'Sin materia' }}</p><h4 class="mt-1 font-bold text-[#0D376D]">{{ $guia->nombre }}</h4><p class="mt-1 text-sm text-slate-500">{{ $guia->cuatrimestre ?: 'Sin cuatrimestre' }}</p></div>
                        <div><p class="text-xs text-slate-500">Periodo</p><p class="text-sm font-semibold text-slate-800">{{ $guia->periodo->nombre }}</p></div>
                        <div><p class="text-xs text-slate-500">Versión</p><p class="font-bold text-slate-800">v{{ $guia->version }}</p></div>
                        <span class="w-fit rounded-full px-3 py-1 text-xs font-bold {{ $guia->estado === 'publicada' ? 'bg-[#EAF7EF] text-[#0F7D47]' : 'bg-[#EAF2FB] text-[#15529A]' }}">{{ ucfirst($guia->estado) }}</span>
                        <span class="text-sm font-bold text-[#15529A]">{{ $guia->apartados_count }} apartados ▾</span>
                    </summary>
                    <div class="border-t border-slate-100 bg-slate-50 p-5">
                        <div class="mb-4 grid gap-3 lg:grid-cols-2"><div class="rounded-md bg-white p-3 text-sm"><strong>Competencias:</strong> {{ $guia->competencias_evaluar ?: '-' }}</div><div class="rounded-md bg-white p-3 text-sm"><strong>Objetivo:</strong> {{ $guia->objetivo_aprendizaje ?: '-' }}</div></div>
                        <div class="overflow-x-auto rounded-md border border-slate-200 bg-white">
                            <table class="min-w-full divide-y divide-slate-200 text-sm"><thead class="bg-[#0D376D] text-white"><tr><th class="px-4 py-3 text-left">Orden</th><th class="px-4 py-3 text-left">Apartado</th><th class="px-4 py-3 text-left">Fecha</th><th class="px-4 py-3 text-left">Ponderación</th><th class="px-4 py-3 text-left">Docentes que califican</th></tr></thead><tbody class="divide-y divide-slate-100">
                                @forelse($guia->apartados->sortBy('orden') as $apartado)
                                    @php
                                        $calificadores = $apartado->firmas->whereNotNull('docente_id');
                                        $docentesCarrera = $docentes->filter(fn ($docente) =>
                                            (int) $docente->carrera_id === (int) $guia->asignatura?->carrera_id
                                            || $docente->carrerasComoDocente->contains('id', $guia->asignatura?->carrera_id)
                                        )->whereNotIn('id', $calificadores->pluck('docente_id'));
                                    @endphp
                                    <tr class="align-top">
                                        <td class="px-4 py-3 font-bold">{{ $apartado->orden }}</td>
                                        <td class="px-4 py-3"><p class="font-semibold">{{ $apartado->titulo }}</p><p class="mt-1 text-slate-500">{{ $apartado->descripcion ?: '-' }}</p><p class="mt-2 text-xs font-semibold text-[#15529A]">{{ $apartado->asignaturasContribuyentes->pluck('clave')->join(', ') ?: 'Sin materias vinculadas' }}</p></td>
                                        <td class="px-4 py-3">{{ $apartado->fecha_limite?->format('d/m/Y') ?? '-' }}</td>
                                        <td class="px-4 py-3">{{ $apartado->ponderacion }}%</td>
                                        <td class="min-w-80 px-4 py-3">
                                            <div class="space-y-2">
                                                @forelse($calificadores as $asignacion)
                                                    <div class="flex items-center justify-between gap-3 rounded-md bg-slate-50 px-3 py-2">
                                                        <div><p class="font-semibold text-slate-800">{{ $asignacion->docente->nombre }}</p><p class="text-xs text-slate-500">{{ $asignacion->docente->matricula }}</p></div>
                                                        <form method="POST" action="{{ route('guias.apartados.calificadores.quitar') }}" data-async-form data-async-targets="mensajes guides-list guide-metrics">
                                                            @csrf @method('DELETE')
                                                            <input type="hidden" name="apartado_guia_id" value="{{ $apartado->id }}"><input type="hidden" name="docente_id" value="{{ $asignacion->docente_id }}">
                                                            <button class="text-xs font-bold text-red-700">Quitar</button>
                                                        </form>
                                                    </div>
                                                @empty
                                                    <p class="text-sm font-semibold text-amber-700">Sin docente asignado.</p>
                                                @endforelse
                                                @if($docentesCarrera->isNotEmpty())
                                                    <form method="POST" action="{{ route('guias.apartados.calificadores.guardar') }}" class="flex gap-2" data-async-form data-async-targets="mensajes guides-list guide-metrics">
                                                        @csrf
                                                        <input type="hidden" name="apartado_guia_id" value="{{ $apartado->id }}">
                                                        <select name="docente_id" required class="min-w-0 flex-1 rounded-md border-slate-300 text-xs">
                                                            <option value="">Asignar docente</option>
                                                            @foreach($docentesCarrera as $docente)<option value="{{ $docente->id }}">{{ $docente->nombre }} · {{ $docente->role->nombre_visible }}</option>@endforeach
                                                        </select>
                                                        <button class="rounded-md bg-[#15529A] px-3 py-2 text-xs font-bold text-white">Asignar</button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty<tr><td colspan="5" class="px-4 py-6 text-center text-slate-500">Sin apartados configurados.</td></tr>@endforelse
                            </tbody></table>
                        </div>
                    </div>
                </details>
            @empty
                <div class="px-5 py-12 text-center text-sm text-slate-500">No hay guías para esta carrera, periodo o filtro.</div>
            @endforelse
        </div>
        <div class="border-t border-slate-200 px-5 py-3 text-sm text-slate-500">Lista completa: <strong class="text-slate-800">{{ $guias->count() }} guías</strong></div>
    </article>
</x-contenedor-aplicacion>
