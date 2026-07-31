<x-contenedor-aplicacion title="Asignaturas | Administración de proyectos" active="asignaturas" :navegacion="$navegacion" :role-name="$roleName">
    <section class="border-b border-slate-200 pb-5">
        <p class="text-sm font-semibold uppercase tracking-[0.18em] text-[#21A366]">Carga académica periódica</p>
        <h2 class="mt-2 text-2xl font-bold text-[#0D376D]">Asignaturas y docentes</h2>
        <p class="mt-2 max-w-4xl text-sm leading-6 text-slate-600">Las asignaturas forman un catálogo por carrera y cuatrimestre. Los docentes se asignan nuevamente en cada periodo; una asignación anterior no se hereda.</p>
    </section>

    <x-mensajes-formulario />

    <section data-async-region="subject-metrics" class="mt-5 grid gap-3 md:grid-cols-3">
        @foreach($metricasAsignaturas as $metrica)
            <article class="rounded-md border border-slate-200 bg-white p-4 shadow-sm"><p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">{{ $metrica['label'] }}</p><p class="mt-2 text-2xl font-bold text-[#0D376D]">{{ $metrica['value'] }}</p></article>
        @endforeach
    </section>

    <section class="mt-5 grid gap-4 xl:grid-cols-2">
        <details class="rounded-lg border border-slate-200 bg-white shadow-sm" @if($errors->any()) open @endif>
            <summary class="cursor-pointer list-none p-5"><p class="text-xs font-bold uppercase tracking-[0.15em] text-[#21A366]">Catálogo permanente</p><div class="mt-1 flex justify-between"><h3 class="font-bold text-[#0D376D]">Registrar asignatura</h3><span class="text-xl text-[#15529A]">＋</span></div></summary>
            <form method="POST" action="{{ route('asignaturas.guardar') }}" class="grid gap-4 border-t border-slate-200 p-5 md:grid-cols-2">
                @csrf
                <div><label class="text-sm font-semibold text-slate-700">Carrera</label><select name="carrera_id" required class="mt-2 block w-full rounded-md border-slate-300 text-sm">@foreach($carreras as $carrera)<option value="{{ $carrera->id }}" @selected((string)old('carrera_id',$filtrosAsignaturas['carrera_id']) === (string)$carrera->id)>{{ $carrera->clave }} - {{ $carrera->nombre }}</option>@endforeach</select></div>
                <div><label class="text-sm font-semibold text-slate-700">Cuatrimestre</label><input name="grado" type="number" min="1" max="12" value="{{ old('grado',$filtrosAsignaturas['grado']) }}" required class="mt-2 block w-full rounded-md border-slate-300 text-sm"></div>
                <div class="md:col-span-2"><label class="text-sm font-semibold text-slate-700">Nombre</label><input name="nombre" value="{{ old('nombre') }}" required class="mt-2 block w-full rounded-md border-slate-300 text-sm"></div>
                <div><label class="text-sm font-semibold text-slate-700">Clave</label><input name="clave" value="{{ old('clave') }}" required class="mt-2 block w-full rounded-md border-slate-300 text-sm uppercase"></div>
                <div class="flex items-end"><button class="rounded-md bg-[#15529A] px-5 py-2.5 text-sm font-bold text-white">Guardar asignatura</button></div>
            </form>
        </details>

        <details class="rounded-lg border border-slate-200 bg-white shadow-sm">
            <summary class="cursor-pointer list-none p-5"><p class="text-xs font-bold uppercase tracking-[0.15em] text-[#21A366]">Asignación por periodo</p><div class="mt-1 flex justify-between"><h3 class="font-bold text-[#0D376D]">Asignar docente</h3><span class="text-xl text-[#15529A]">＋</span></div></summary>
            <form method="POST" action="{{ route('asignaturas.docentes.guardar') }}" class="grid gap-4 border-t border-slate-200 p-5">
                @csrf
                <div><label class="text-sm font-semibold text-slate-700">Periodo</label><select name="periodo_id" required class="mt-2 block w-full rounded-md border-slate-300 text-sm">@foreach($periodos as $periodo)<option value="{{ $periodo->id }}" @selected((string)$filtrosAsignaturas['periodo_id'] === (string)$periodo->id)>{{ $periodo->nombre }}</option>@endforeach</select></div>
                <div><label class="text-sm font-semibold text-slate-700">Asignatura</label><select name="asignatura_id" required class="mt-2 block w-full rounded-md border-slate-300 text-sm">@foreach($asignaturas as $asignatura)<option value="{{ $asignatura->id }}">{{ $asignatura->clave }} - {{ $asignatura->nombre }}</option>@endforeach</select></div>
                <div><label class="text-sm font-semibold text-slate-700">Docente de la carrera</label><select name="docente_id" required class="mt-2 block w-full rounded-md border-slate-300 text-sm">@foreach($docentes as $docente)<option value="{{ $docente->id }}">{{ $docente->matricula }} - {{ $docente->nombre }} ({{ $docente->role?->nombre_visible }})</option>@endforeach</select></div>
                <button class="w-fit rounded-md bg-[#15529A] px-5 py-2.5 text-sm font-bold text-white">Asignar en este periodo</button>
            </form>
        </details>
    </section>

    <article data-async-region="subjects-list" class="mt-5 overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col gap-4 border-b border-slate-200 p-5 xl:flex-row xl:items-end xl:justify-between">
            <div><p class="text-xs font-bold uppercase tracking-[0.15em] text-[#21A366]">Vista operativa</p><h3 class="mt-1 text-lg font-bold text-[#0D376D]">Carga de asignaturas</h3></div>
            <form method="GET" action="{{ route('modulos.show','asignaturas') }}" data-async-form data-async-targets="subjects-list subject-metrics" class="flex flex-wrap items-end gap-2">
                <div><label class="block text-xs font-bold uppercase text-slate-500">Periodo</label><select name="periodo_asignaturas" class="mt-1 rounded-md border-slate-300 text-sm">@foreach($periodos as $periodo)<option value="{{ $periodo->id }}" @selected((string)$filtrosAsignaturas['periodo_id'] === (string)$periodo->id)>{{ $periodo->nombre }}</option>@endforeach</select></div>
                <div><label class="block text-xs font-bold uppercase text-slate-500">Carrera</label><select name="carrera_asignaturas" class="mt-1 rounded-md border-slate-300 text-sm">@foreach($carreras as $carrera)<option value="{{ $carrera->id }}" @selected((string)$filtrosAsignaturas['carrera_id'] === (string)$carrera->id)>{{ $carrera->clave }}</option>@endforeach</select></div>
                <div><label class="block text-xs font-bold uppercase text-slate-500">Cuatrimestre</label><select name="grado_asignaturas" class="mt-1 rounded-md border-slate-300 text-sm">@foreach($grados as $grado)<option value="{{ $grado }}" @selected((string)$filtrosAsignaturas['grado'] === (string)$grado)>{{ $grado }}</option>@endforeach</select></div>
                <input name="busqueda_asignaturas" value="{{ $filtrosAsignaturas['busqueda'] }}" placeholder="Clave o asignatura" class="rounded-md border-slate-300 text-sm">
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
                <a href="{{ route('modulos.show',['modulo'=>'asignaturas','periodo_asignaturas'=>$filtrosAsignaturas['periodo_id'],'carrera_asignaturas'=>$filtrosAsignaturas['carrera_id'],'grado_asignaturas'=>$grado]) }}" data-async-link data-async-targets="subjects-list subject-metrics" class="rounded-full px-4 py-1.5 text-sm font-bold {{ (int)$filtrosAsignaturas['grado'] === (int)$grado ? 'bg-[#21A366] text-white' : 'bg-[#EAF7EF] text-[#0F7D47]' }}">{{ $grado }}°</a>
            @endforeach
        </nav>

        <div class="divide-y divide-slate-200">
            @forelse($asignaturas as $asignatura)
                @php($docentesAsignados = $asignaciones->get($asignatura->id, collect()))
                <section class="grid gap-4 p-5 md:grid-cols-[130px_minmax(0,1fr)_minmax(260px,1fr)_100px] md:items-center">
                    <div><p class="text-xs font-bold uppercase text-[#21A366]">{{ $asignatura->carrera?->clave }}</p><p class="mt-1 font-bold text-[#0D376D]">{{ $asignatura->clave }}</p></div>
                    <div><h4 class="font-semibold text-slate-900">{{ $asignatura->nombre }}</h4><p class="mt-1 text-sm text-slate-500">{{ $asignatura->grado }}° cuatrimestre</p></div>
                    <div>
                        <p class="text-xs font-bold uppercase text-slate-500">Docentes en el periodo</p>
                        <div class="mt-2 flex flex-wrap gap-2">
                            @forelse($docentesAsignados as $docente)
                                <form method="POST" action="{{ route('asignaturas.docentes.quitar') }}">
                                    @csrf @method('DELETE')
                                    <input type="hidden" name="periodo_id" value="{{ $filtrosAsignaturas['periodo_id'] }}"><input type="hidden" name="asignatura_id" value="{{ $asignatura->id }}"><input type="hidden" name="docente_id" value="{{ $docente->docente_id }}">
                                    <button class="rounded-md bg-[#EAF7EF] px-2.5 py-1 text-xs font-bold text-[#0F7D47] hover:bg-red-50 hover:text-red-700">{{ $docente->nombre }} ×</button>
                                </form>
                            @empty
                                <span class="text-sm text-amber-700">Sin docente asignado en este periodo</span>
                            @endforelse
                        </div>
                    </div>
                    <span class="w-fit rounded-full bg-[#EAF2FB] px-3 py-1 text-xs font-bold text-[#15529A]">{{ ucfirst($asignatura->estado) }}</span>
                </section>
            @empty
                <div class="px-5 py-12 text-center text-sm text-slate-500">No hay asignaturas para esta carrera y cuatrimestre.</div>
            @endforelse
        </div>
        <div class="border-t border-slate-200 px-5 py-3 text-sm text-slate-500">Lista completa: <strong class="text-slate-800">{{ $asignaturas->count() }} asignaturas</strong></div>
    </article>
</x-contenedor-aplicacion>
