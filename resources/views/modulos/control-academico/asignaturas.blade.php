<x-contenedor-aplicacion title="Asignaturas | Administración de proyectos" active="asignaturas" :navegacion="$navegacion" :role-name="$roleName">
    <section class="border-b border-slate-200 pb-5">
        <p class="text-sm font-semibold uppercase tracking-[0.18em] text-[#21A366]">Evaluación multidisciplinaria</p>
        <h2 class="mt-2 text-2xl font-bold text-[#0D376D]">Asignaturas participantes</h2>
        <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">Las asignaturas se vinculan a carrera y grado para después asociarlas a proyectos y docentes evaluadores.</p>
    </section>

    <x-mensajes-formulario />

    <section class="mt-5 grid gap-3 md:grid-cols-3">
        @foreach ($metricasAsignaturas as $metrica)
            <article class="rounded-md border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">{{ $metrica['label'] }}</p>
                <p class="mt-2 text-2xl font-bold text-[#0D376D]">{{ $metrica['value'] }}</p>
            </article>
        @endforeach
    </section>

    <section class="mt-5 grid gap-5 2xl:grid-cols-[340px_380px_minmax(0,1fr)]">
        <article class="rounded-md border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4">
                <h3 class="font-bold text-slate-900">Nueva asignatura</h3>
            </div>
            <form method="POST" action="{{ route('asignaturas.guardar') }}" class="space-y-4 p-5">
                @csrf
                <div>
                    <label class="block text-sm font-semibold text-slate-700">Carrera</label>
                    <select name="carrera_id" required class="mt-2 block w-full rounded-md border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-[#15529A] focus:ring-2 focus:ring-[#15529A]/20">
                        @foreach ($carreras as $carrera)
                            <option value="{{ $carrera->id }}">{{ $carrera->clave }} - {{ $carrera->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700">Nombre</label>
                    <input name="nombre" value="{{ old('nombre') }}" required class="mt-2 block w-full rounded-md border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-[#15529A] focus:ring-2 focus:ring-[#15529A]/20">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700">Clave</label>
                        <input name="clave" value="{{ old('clave') }}" class="mt-2 block w-full rounded-md border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-[#15529A] focus:ring-2 focus:ring-[#15529A]/20">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700">Grado</label>
                        <input name="grado" type="number" min="1" max="12" value="{{ old('grado', 9) }}" class="mt-2 block w-full rounded-md border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-[#15529A] focus:ring-2 focus:ring-[#15529A]/20">
                    </div>
                </div>
                <button class="rounded-md bg-[#15529A] px-4 py-2.5 text-sm font-bold text-white transition hover:bg-[#0D376D]">Guardar asignatura</button>
            </form>
        </article>

        <article class="rounded-md border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4">
                <h3 class="font-bold text-slate-900">Docente por asignatura</h3>
                <p class="mt-1 text-sm text-slate-500">Vincula materias con docentes responsables o evaluadores.</p>
            </div>
            <form method="POST" action="{{ route('asignaturas.docentes.guardar') }}" class="space-y-4 p-5">
                @csrf
                <div>
                    <label class="block text-sm font-semibold text-slate-700">Asignatura</label>
                    <select name="asignatura_id" required class="mt-2 block w-full rounded-md border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-[#15529A] focus:ring-2 focus:ring-[#15529A]/20">
                        @foreach ($asignaturas as $asignatura)
                            <option value="{{ $asignatura->id }}">{{ $asignatura->clave ?? 'S/C' }} - {{ $asignatura->nombre }} ({{ $asignatura->carrera?->clave ?? '-' }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700">Docente</label>
                    <select name="docente_id" required class="mt-2 block w-full rounded-md border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-[#15529A] focus:ring-2 focus:ring-[#15529A]/20">
                        @foreach ($docentes as $docente)
                            <option value="{{ $docente->id }}">{{ $docente->matricula }} - {{ $docente->nombre }} ({{ $docente->carrera?->clave ?? 'Sin carrera' }})</option>
                        @endforeach
                    </select>
                </div>
                <button class="rounded-md bg-[#15529A] px-4 py-2.5 text-sm font-bold text-white transition hover:bg-[#0D376D]">Vincular docente</button>
            </form>
        </article>

        <article class="min-w-0 rounded-md border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4">
                <h3 class="font-bold text-slate-900">Asignaturas registradas</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-[#0D376D] text-white">
                        <tr>
                            <th class="px-4 py-3 text-left">Clave</th>
                            <th class="px-4 py-3 text-left">Asignatura</th>
                            <th class="px-4 py-3 text-left">Carrera</th>
                            <th class="px-4 py-3 text-left">Grado</th>
                            <th class="px-4 py-3 text-left">Docentes</th>
                            <th class="px-4 py-3 text-left">Estado</th>
                            <th class="px-4 py-3 text-left">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @foreach ($asignaturas as $asignatura)
                            <tr>
                                <td class="px-4 py-3 font-semibold text-slate-800">{{ $asignatura->clave ?? '-' }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $asignatura->nombre }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $asignatura->carrera?->clave ?? '-' }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $asignatura->grado ?? '-' }}</td>
                                <td class="px-4 py-3 text-slate-600">
                                    <div class="flex flex-wrap gap-1.5">
                                        @forelse ($asignatura->docentes as $docente)
                                            <span class="rounded-md bg-[#EAF7EF] px-2 py-1 text-xs font-bold text-[#0F7D47]">{{ $docente->nombre }}</span>
                                        @empty
                                            <span class="text-slate-400">Sin docente</span>
                                        @endforelse
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-slate-600">{{ ucfirst($asignatura->estado) }}</td>
                                <td class="px-4 py-3">
                                    @foreach ($asignatura->docentes as $docente)
                                        <form method="POST" action="{{ route('asignaturas.docentes.quitar') }}" class="mb-1">
                                            @csrf
                                            @method('DELETE')
                                            <input type="hidden" name="asignatura_id" value="{{ $asignatura->id }}">
                                            <input type="hidden" name="docente_id" value="{{ $docente->id }}">
                                            <button class="rounded-md border border-red-200 px-2 py-1 text-xs font-bold text-red-700 transition hover:bg-red-50">
                                                Quitar {{ $docente->matricula }}
                                            </button>
                                        </form>
                                    @endforeach
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </article>
    </section>
</x-contenedor-aplicacion>
