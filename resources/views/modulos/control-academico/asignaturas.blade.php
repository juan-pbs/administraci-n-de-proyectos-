<x-contenedor-aplicacion title="Asignaturas | Administración de proyectos" active="asignaturas" :navegacion="$navegacion" :role-name="$roleName">
    <section class="border-b border-slate-200 pb-5">
        <p class="text-sm font-semibold uppercase tracking-[0.18em] text-[#005227]">Carga académica periódica</p>
        <h2 class="mt-2 text-2xl font-bold text-[#0D376D]">Asignaturas y docentes</h2>
        <p class="mt-2 max-w-4xl text-sm leading-6 text-slate-600">Las asignaturas forman un catálogo por carrera y cuatrimestre. Los docentes se asignan nuevamente en cada periodo; una asignación anterior no se hereda.</p>
    </section>

    <x-mensajes-formulario />

    <section data-async-region="subject-metrics" class="mt-5 grid gap-3 md:grid-cols-3">
        @foreach($metricasAsignaturas as $metrica)
            <article class="rounded-md border border-slate-200 bg-white p-4 shadow-sm"><p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">{{ $metrica['label'] }}</p><p class="mt-2 text-2xl font-bold text-[#0D376D]">{{ $metrica['value'] }}</p></article>
        @endforeach
    </section>

    @php
        $abrirAsignacion = $errors->hasAny(['asignatura_id', 'docente_id', 'periodo_id'])
            || (! $errors->any() && request()->boolean('asignar_docente'));
        $abrirRegistro = $errors->any() && ! $abrirAsignacion;
    @endphp
    <section class="mt-5 space-y-4">
        <x-controles-formularios
            :paneles="['registrar-asignatura' => 'Registrar asignatura', 'asignar-docente' => 'Asignar docente']"
            :activo="$abrirAsignacion ? 'asignar-docente' : ($abrirRegistro ? 'registrar-asignatura' : null)" />

        <div id="registrar-asignatura" class="rounded-lg border border-slate-200 bg-white shadow-sm" @if(! $abrirRegistro) hidden @endif>
            <form method="POST" action="{{ route('asignaturas.guardar') }}" class="grid gap-4 p-5 md:grid-cols-2">
                @csrf
                <input type="hidden" name="periodo_asignaturas" value="{{ $filtrosAsignaturas['periodo_id'] }}">
                <div><label class="text-sm font-semibold text-slate-700">Carrera</label><select name="carrera_id" required class="mt-2 block w-full rounded-md border-slate-300 text-sm">@foreach($carreras as $carrera)<option value="{{ $carrera->id }}" @selected((string)old('carrera_id',$filtrosAsignaturas['carrera_id']) === (string)$carrera->id)>{{ $carrera->clave }} - {{ $carrera->nombre }}</option>@endforeach</select></div>
                <div><label class="text-sm font-semibold text-slate-700">Cuatrimestre</label><input name="grado" type="number" min="1" max="12" value="{{ old('grado',$filtrosAsignaturas['grado']) }}" required class="mt-2 block w-full rounded-md border-slate-300 text-sm"></div>
                <div class="md:col-span-2"><label class="text-sm font-semibold text-slate-700">Nombre (máximo 120 caracteres)</label><input name="nombre" maxlength="120" value="{{ old('nombre') }}" required class="mt-2 block w-full rounded-md border-slate-300 text-sm"><p class="mt-1 text-xs text-slate-500">Usa letras, números, espacios y estos signos: . , : ; ( ) / + &amp; -</p></div>
                <div><label class="text-sm font-semibold text-slate-700">Clave (máximo 30 caracteres)</label><input name="clave" maxlength="30" pattern="[A-Za-z0-9._-]+" title="Usa letras, números, puntos, guiones o guiones bajos" value="{{ old('clave') }}" required class="mt-2 block w-full rounded-md border-slate-300 text-sm uppercase"></div>
                <div class="flex items-end"><button class="rounded-md bg-[#15529A] px-5 py-2.5 text-sm font-bold text-white">Guardar asignatura</button></div>
            </form>
        </div>

        <div id="asignar-docente" class="rounded-lg border border-slate-200 bg-white shadow-sm" @if(! $abrirAsignacion) hidden @endif>
            <form method="POST" action="{{ route('asignaturas.docentes.guardar') }}" class="grid gap-4 p-5 md:grid-cols-3">
                @csrf
                <label class="min-w-0 text-sm font-semibold text-slate-700">Periodo<select name="periodo_id" required class="mt-2 block w-full rounded-md border-slate-300 text-sm">@foreach($periodos as $periodo)<option value="{{ $periodo->id }}" @selected((string) old('periodo_id', $filtrosAsignaturas['periodo_id']) === (string) $periodo->id)>{{ $periodo->nombre }}</option>@endforeach</select></label>
                <label class="min-w-0 text-sm font-semibold text-slate-700">Asignatura<select name="asignatura_id" required class="mt-2 block w-full rounded-md border-slate-300 text-sm">@foreach($asignaturas as $asignatura)<option value="{{ $asignatura->id }}" @selected((int) old('asignatura_id', request()->query('asignatura_id')) === (int) $asignatura->id)>{{ $asignatura->clave }} - {{ $asignatura->nombre }}</option>@endforeach</select></label>
                <label class="min-w-0 text-sm font-semibold text-slate-700">Docente de la carrera<select name="docente_id" required class="mt-2 block w-full rounded-md border-slate-300 text-sm">@foreach($docentes as $docente)<option value="{{ $docente->id }}" @selected((int) old('docente_id') === (int) $docente->id)>{{ $docente->matricula }} - {{ $docente->nombre }} ({{ $docente->role?->nombre_visible }})</option>@endforeach</select></label>
                <button class="w-fit rounded-md bg-[#15529A] px-5 py-2.5 text-sm font-bold text-white md:col-span-3">Asignar en este periodo</button>
            </form>
        </div>
    </section>

    <article data-async-region="subjects-list" class="mt-5 overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col gap-4 border-b border-slate-200 p-5 xl:flex-row xl:items-end xl:justify-between">
            <div><p class="text-xs font-bold uppercase tracking-[0.15em] text-[#005227]">Vista operativa</p><h3 class="mt-1 text-lg font-bold text-[#0D376D]">Carga de asignaturas</h3></div>
            <form method="GET" action="{{ route('modulos.show','asignaturas') }}" data-async-form data-async-targets="subjects-list subject-metrics" class="flex flex-wrap items-end gap-2">
                <div><label class="block text-xs font-bold uppercase text-slate-500">Periodo</label><select name="periodo_asignaturas" class="mt-1 rounded-md border-slate-300 text-sm">@foreach($periodos as $periodo)<option value="{{ $periodo->id }}" @selected((string)$filtrosAsignaturas['periodo_id'] === (string)$periodo->id)>{{ $periodo->nombre }}</option>@endforeach</select></div>
                <div><label class="block text-xs font-bold uppercase text-slate-500">Carrera</label><select name="carrera_asignaturas" class="mt-1 rounded-md border-slate-300 text-sm">@foreach($carreras as $carrera)<option value="{{ $carrera->id }}" @selected((string)$filtrosAsignaturas['carrera_id'] === (string)$carrera->id)>{{ $carrera->clave }}</option>@endforeach</select></div>
                <div><label class="block text-xs font-bold uppercase text-slate-500">Cuatrimestre</label><select name="grado_asignaturas" class="mt-1 rounded-md border-slate-300 text-sm">@foreach($grados as $grado)<option value="{{ $grado }}" @selected((string)$filtrosAsignaturas['grado'] === (string)$grado)>{{ $grado }}</option>@endforeach</select></div>
                <label class="block text-xs font-bold uppercase text-slate-500">Buscar asignatura<input name="busqueda_asignaturas" type="search" value="{{ $filtrosAsignaturas['busqueda'] }}" placeholder="Clave o asignatura" class="mt-1 block rounded-md border-slate-300 text-sm"></label>
                <button class="rounded-md bg-[#15529A] px-4 py-2 text-sm font-bold text-white">Cambiar vista</button>
            </form>
        </div>

        <nav class="flex flex-wrap gap-2 border-b border-slate-200 bg-slate-50 px-5 py-3">
            @foreach($carreras as $carrera)
                <a href="{{ route('modulos.show',['modulo'=>'asignaturas','periodo_asignaturas'=>$filtrosAsignaturas['periodo_id'],'carrera_asignaturas'=>$carrera->id]) }}" data-async-link data-async-targets="subjects-list subject-metrics" class="rounded-md px-4 py-2 text-sm font-bold {{ (int)$filtrosAsignaturas['carrera_id'] === (int)$carrera->id ? 'bg-[#0D376D] text-white shadow-sm' : 'border border-slate-300 bg-white text-[#15529A]' }}">{{ $carrera->clave }}</a>
            @endforeach
        </nav>
        <nav class="flex flex-wrap gap-2 border-b border-slate-200 px-5 py-3">
            @foreach($grados as $grado)
                <a href="{{ route('modulos.show',['modulo'=>'asignaturas','periodo_asignaturas'=>$filtrosAsignaturas['periodo_id'],'carrera_asignaturas'=>$filtrosAsignaturas['carrera_id'],'grado_asignaturas'=>$grado]) }}" data-async-link data-async-targets="subjects-list subject-metrics" class="rounded-full px-4 py-1.5 text-sm font-bold {{ (int)$filtrosAsignaturas['grado'] === (int)$grado ? 'bg-[#21A366] text-white' : 'bg-[#EAF7EF] text-[#005227]' }}">{{ $grado }}°</a>
            @endforeach
        </nav>

        <div class="divide-y divide-slate-200">
            @forelse($asignaturas as $asignatura)
                @php($docentesAsignados = $asignaciones->get($asignatura->id, collect()))
                <section class="grid gap-4 p-5 md:grid-cols-[130px_minmax(0,1fr)_minmax(260px,1fr)_100px] md:items-center">
                    <div class="min-w-0"><p class="break-all text-xs font-bold uppercase text-[#005227]">{{ $asignatura->carrera?->clave }}</p><p class="mt-1 break-all font-bold text-[#0D376D]">{{ $asignatura->clave }}</p></div>
                    <div class="min-w-0"><h4 class="break-words font-semibold text-slate-900">{{ $asignatura->nombre }}</h4><p class="mt-1 text-sm text-slate-500">{{ $asignatura->grado }}° cuatrimestre</p></div>
                    <div>
                        <p class="text-xs font-bold uppercase text-slate-500">Docentes en el periodo</p>
                        <div class="mt-2 flex flex-wrap gap-2">
                            @forelse($docentesAsignados as $docente)
                                <form method="POST" action="{{ route('asignaturas.docentes.quitar') }}">
                                    @csrf @method('DELETE')
                                    <input type="hidden" name="periodo_id" value="{{ $filtrosAsignaturas['periodo_id'] }}"><input type="hidden" name="asignatura_id" value="{{ $asignatura->id }}"><input type="hidden" name="docente_id" value="{{ $docente->docente_id }}">
                                    <button class="rounded-md bg-[#EAF7EF] px-2.5 py-1 text-xs font-bold text-[#005227] hover:bg-red-50 hover:text-red-700">{{ $docente->nombre }} ×</button>
                                </form>
                            @empty
                                <span class="text-sm text-amber-700">Sin docente asignado en este periodo</span>
                            @endforelse
                        </div>
                    </div>
                    <span class="w-fit rounded-full bg-[#EAF2FB] px-3 py-1 text-xs font-bold text-[#15529A]">{{ ucfirst($asignatura->estado) }}</span>
                    <div class="space-y-2 md:col-span-full">
                        <details class="rounded-md border border-slate-200 p-3">
                            <summary class="cursor-pointer text-sm font-bold text-[#15529A]">Editar asignatura</summary>
                            <form method="POST" action="{{ route('asignaturas.actualizar', $asignatura) }}" class="mt-3 grid gap-3 md:grid-cols-4">
                                @csrf @method('PATCH')
                                <input type="hidden" name="periodo_asignaturas" value="{{ $filtrosAsignaturas['periodo_id'] }}">
                                <label class="text-sm font-semibold">Nombre<input name="nombre" maxlength="120" required value="{{ $asignatura->nombre }}" class="mt-1 block w-full rounded-md border-slate-300"></label>
                                <label class="text-sm font-semibold">Clave<input name="clave" maxlength="30" value="{{ $asignatura->clave }}" class="mt-1 block w-full rounded-md border-slate-300"></label>
                                <label class="text-sm font-semibold">Carrera<select name="carrera_id" required class="mt-1 block w-full rounded-md border-slate-300">@foreach($carreras as $opcion)<option value="{{ $opcion->id }}" @selected($opcion->id === $asignatura->carrera_id)>{{ $opcion->clave }}</option>@endforeach</select></label>
                                <label class="text-sm font-semibold">Cuatrimestre<input name="grado" type="number" min="1" max="12" required value="{{ $asignatura->grado }}" class="mt-1 block w-full rounded-md border-slate-300"></label>
                                <label class="text-sm font-semibold">Estado<select name="estado" class="mt-1 block w-full rounded-md border-slate-300"><option value="activo" @selected($asignatura->estado === 'activo')>Activa</option><option value="inactivo" @selected($asignatura->estado === 'inactivo')>Inactiva</option></select></label>
                                <button class="w-fit rounded-md bg-[#15529A] px-3 py-2 text-sm font-bold text-white">Guardar cambios</button>
                            </form>
                        </details>
                        <x-confirmar-accion :accion="route('asignaturas.eliminar', $asignatura)" etiqueta="Eliminar asignatura"
                            mensaje="Confirmo que deseo eliminar la asignatura si no tiene vínculos académicos." />
                    </div>
                </section>
            @empty
                <div class="px-5 py-12 text-center text-sm text-slate-500">No hay asignaturas para esta carrera y cuatrimestre.</div>
            @endforelse
        </div>
        <div class="border-t border-slate-200 px-5 py-3 text-sm text-slate-500">Lista completa: <strong class="text-slate-800">{{ $asignaturas->count() }} {{ $asignaturas->count() === 1 ? 'asignatura' : 'asignaturas' }}</strong></div>
    </article>
</x-contenedor-aplicacion>
