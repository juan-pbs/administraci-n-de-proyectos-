<x-contenedor-aplicacion
    title="Carreras y grupos | Administración de proyectos"
    active="carreras-grupos"
    :navegacion="$navegacion"
    :role-name="$roleName"
>
    <section class="border-b border-slate-200 pb-5">
        <p class="text-sm font-semibold uppercase tracking-[0.18em] text-[#21A366]">Control académico</p>
        <div class="mt-3 flex flex-col gap-3 xl:flex-row xl:items-end xl:justify-between">
            <div class="min-w-0">
                <h2 class="text-2xl font-bold text-[#0D376D]">Carreras y grupos</h2>
                <p class="mt-2 max-w-4xl text-sm leading-6 text-slate-600">
                    Organiza alumnos por carrera, grado y grupo. Desde esta base se forman equipos de trabajo; los docentes se asocian a carreras y después pueden participar como asesores de uno o varios equipos o como evaluadores de apartados específicos.
                </p>
            </div>
        </div>
    </section>

    @if (session('estado'))
        <div class="mt-5 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">
            {{ session('estado') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mt-5 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            <p class="font-bold">Revisa los datos capturados:</p>
            <ul class="mt-2 list-inside list-disc">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <section class="mt-5 grid gap-3 md:grid-cols-4">
        @foreach ($metricasCarreras as $metrica)
            <article class="rounded-md border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">{{ $metrica['label'] }}</p>
                <p class="mt-2 text-2xl font-bold text-[#0D376D]">{{ $metrica['value'] }}</p>
            </article>
        @endforeach
    </section>

    <section class="mt-5 grid gap-5 xl:grid-cols-3">
        <article class="rounded-md border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4">
                <h3 class="font-bold text-slate-900">Nueva carrera</h3>
                <p class="mt-1 text-sm text-slate-500">Alta de programa académico.</p>
            </div>

            <form method="POST" action="{{ route('carreras.guardar') }}" class="space-y-4 p-5">
                @csrf
                <div>
                    <label for="nombre" class="block text-sm font-semibold text-slate-700">Nombre de la carrera</label>
                    <input id="nombre" name="nombre" value="{{ old('nombre') }}" required class="mt-2 block w-full rounded-md border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-[#15529A] focus:ring-2 focus:ring-[#15529A]/20" placeholder="Tecnologías de la Información">
                </div>
                <div>
                    <label for="clave" class="block text-sm font-semibold text-slate-700">Clave</label>
                    <input id="clave" name="clave" value="{{ old('clave') }}" required class="mt-2 block w-full rounded-md border border-slate-300 px-3 py-2.5 text-sm uppercase outline-none focus:border-[#15529A] focus:ring-2 focus:ring-[#15529A]/20" placeholder="TI">
                </div>
                <button type="submit" class="rounded-md bg-[#15529A] px-4 py-2.5 text-sm font-bold text-white transition hover:bg-[#0D376D]">
                    Guardar carrera
                </button>
            </form>
        </article>

        <article class="rounded-md border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4">
                <h3 class="font-bold text-slate-900">Nuevo grupo</h3>
                <p class="mt-1 text-sm text-slate-500">Se liga a periodo, carrera, grado y grupo.</p>
            </div>

            <form method="POST" action="{{ route('grupos-academicos.guardar') }}" class="space-y-4 p-5">
                @csrf
                <div>
                    <label for="periodo_id" class="block text-sm font-semibold text-slate-700">Periodo</label>
                    <select id="periodo_id" name="periodo_id" required class="mt-2 block w-full rounded-md border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-[#15529A] focus:ring-2 focus:ring-[#15529A]/20">
                        @foreach ($periodos as $periodo)
                            <option value="{{ $periodo->id }}" @selected((string) old('periodo_id') === (string) $periodo->id)>{{ $periodo->nombre }} - {{ ucfirst($periodo->estado) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="carrera_id" class="block text-sm font-semibold text-slate-700">Carrera</label>
                    <select id="carrera_id" name="carrera_id" required class="mt-2 block w-full rounded-md border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-[#15529A] focus:ring-2 focus:ring-[#15529A]/20">
                        @foreach ($carreras as $carrera)
                            <option value="{{ $carrera->id }}" @selected((string) old('carrera_id') === (string) $carrera->id)>{{ $carrera->clave }} - {{ $carrera->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="grado" class="block text-sm font-semibold text-slate-700">Grado</label>
                        <input id="grado" name="grado" type="number" min="1" max="12" value="{{ old('grado', 9) }}" required class="mt-2 block w-full rounded-md border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-[#15529A] focus:ring-2 focus:ring-[#15529A]/20">
                    </div>
                    <div>
                        <label for="grupo" class="block text-sm font-semibold text-slate-700">Grupo</label>
                        <input id="grupo" name="grupo" value="{{ old('grupo', 'B') }}" maxlength="10" required class="mt-2 block w-full rounded-md border border-slate-300 px-3 py-2.5 text-sm uppercase outline-none focus:border-[#15529A] focus:ring-2 focus:ring-[#15529A]/20">
                    </div>
                </div>
                <button type="submit" class="rounded-md bg-[#15529A] px-4 py-2.5 text-sm font-bold text-white transition hover:bg-[#0D376D]">
                    Guardar grupo
                </button>
            </form>
        </article>

        <article class="rounded-md border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4">
                <h3 class="font-bold text-slate-900">Docente por carrera</h3>
                <p class="mt-1 text-sm text-slate-500">Base para asesores y evaluadores.</p>
            </div>

            <form method="POST" action="{{ route('docentes-carrera.guardar') }}" class="space-y-4 p-5">
                @csrf
                <div>
                    <label for="docente_carrera_id" class="block text-sm font-semibold text-slate-700">Carrera</label>
                    <select id="docente_carrera_id" name="carrera_id" required class="mt-2 block w-full rounded-md border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-[#15529A] focus:ring-2 focus:ring-[#15529A]/20">
                        @foreach ($carreras as $carrera)
                            <option value="{{ $carrera->id }}">{{ $carrera->clave }} - {{ $carrera->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="docente_id" class="block text-sm font-semibold text-slate-700">Docente / asesor</label>
                    <select id="docente_id" name="docente_id" required class="mt-2 block w-full rounded-md border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-[#15529A] focus:ring-2 focus:ring-[#15529A]/20">
                        @foreach ($docentes as $docente)
                            <option value="{{ $docente->id }}">{{ $docente->matricula }} - {{ $docente->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="rounded-md bg-[#15529A] px-4 py-2.5 text-sm font-bold text-white transition hover:bg-[#0D376D]">
                    Asignar docente
                </button>
            </form>
        </article>
    </section>

    <section class="mt-5 grid gap-5 2xl:grid-cols-[minmax(0,0.95fr)_minmax(0,1.05fr)]">
        <article class="min-w-0 rounded-md border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4">
                <h3 class="font-bold text-slate-900">Jerarquía por carrera</h3>
                <p class="mt-1 text-sm text-slate-500">Carrera, docentes asociados, grupos y alumnos asignados.</p>
            </div>

            <div class="max-h-[640px] divide-y divide-slate-200 overflow-y-auto">
                @forelse ($carreras as $carrera)
                    <div class="p-5">
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-[#21A366]">{{ $carrera->clave }}</p>
                                <h4 class="mt-1 font-bold text-[#0D376D]">{{ $carrera->nombre }}</h4>
                            </div>
                            <span class="w-fit rounded-md bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-600">{{ ucfirst($carrera->estado) }}</span>
                        </div>

                        <div class="mt-4 grid gap-3 sm:grid-cols-3">
                            <div class="rounded-md bg-slate-50 p-3">
                                <p class="text-xs font-semibold text-slate-500">Grupos</p>
                                <p class="mt-1 text-xl font-bold text-slate-900">{{ $carrera->grupos_count }}</p>
                            </div>
                            <div class="rounded-md bg-slate-50 p-3">
                                <p class="text-xs font-semibold text-slate-500">Alumnos</p>
                                <p class="mt-1 text-xl font-bold text-slate-900">{{ $carrera->alumnos_count }}</p>
                            </div>
                            <div class="rounded-md bg-slate-50 p-3">
                                <p class="text-xs font-semibold text-slate-500">Docentes</p>
                                <p class="mt-1 text-xl font-bold text-slate-900">{{ $carrera->docentes->count() }}</p>
                            </div>
                        </div>

                        <div class="mt-4">
                            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Docentes asignados</p>
                            <div class="mt-2 flex flex-wrap gap-2">
                                @forelse ($carrera->docentes as $docente)
                                    <span class="rounded-md bg-[#EAF7EF] px-2.5 py-1 text-xs font-bold text-[#0F7D47]">{{ $docente->nombre }}</span>
                                @empty
                                    <span class="text-sm text-slate-500">Sin docentes asociados.</span>
                                @endforelse
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="p-5 text-sm text-slate-500">Aún no hay carreras registradas.</p>
                @endforelse
            </div>
        </article>

        <article class="min-w-0 rounded-md border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4">
                <h3 class="font-bold text-slate-900">Grados y grupos</h3>
                <p class="mt-1 text-sm text-slate-500">Los alumnos se asignan aquí antes de formar equipos.</p>
            </div>

            <form method="GET" action="{{ route('modulos.show', 'carreras-grupos') }}" class="grid gap-3 border-b border-slate-200 p-4 md:grid-cols-2 xl:grid-cols-6">
                <div class="xl:col-span-2">
                    <label for="busqueda_grupos" class="block text-xs font-bold uppercase tracking-[0.12em] text-slate-500">Buscar</label>
                    <input id="busqueda_grupos" name="busqueda_grupos" value="{{ $filtrosGrupos['busqueda'] }}" placeholder="Carrera, periodo, grado o grupo" class="mt-2 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm outline-none focus:border-[#15529A] focus:ring-2 focus:ring-[#15529A]/20">
                </div>
                <div>
                    <label for="carrera_grupos" class="block text-xs font-bold uppercase tracking-[0.12em] text-slate-500">Carrera</label>
                    <select id="carrera_grupos" name="carrera_grupos" class="mt-2 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm outline-none focus:border-[#15529A] focus:ring-2 focus:ring-[#15529A]/20">
                        <option value="">Todas</option>
                        @foreach ($carreras as $carrera)
                            <option value="{{ $carrera->id }}" @selected((string) $filtrosGrupos['carrera_id'] === (string) $carrera->id)>{{ $carrera->clave }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="periodo_grupos" class="block text-xs font-bold uppercase tracking-[0.12em] text-slate-500">Periodo</label>
                    <select id="periodo_grupos" name="periodo_grupos" class="mt-2 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm outline-none focus:border-[#15529A] focus:ring-2 focus:ring-[#15529A]/20">
                        <option value="">Todos</option>
                        @foreach ($periodos as $periodo)
                            <option value="{{ $periodo->id }}" @selected((string) $filtrosGrupos['periodo_id'] === (string) $periodo->id)>{{ $periodo->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="grado_grupos" class="block text-xs font-bold uppercase tracking-[0.12em] text-slate-500">Grado</label>
                    <select id="grado_grupos" name="grado_grupos" class="mt-2 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm outline-none focus:border-[#15529A] focus:ring-2 focus:ring-[#15529A]/20">
                        <option value="">Todos</option>
                        @foreach ($opcionesGrados as $grado)
                            <option value="{{ $grado }}" @selected((string) $filtrosGrupos['grado'] === (string) $grado)>{{ $grado }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="grupo_grupos" class="block text-xs font-bold uppercase tracking-[0.12em] text-slate-500">Grupo</label>
                    <select id="grupo_grupos" name="grupo_grupos" class="mt-2 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm outline-none focus:border-[#15529A] focus:ring-2 focus:ring-[#15529A]/20">
                        <option value="">Todos</option>
                        @foreach ($opcionesGrupos as $grupo)
                            <option value="{{ $grupo }}" @selected((string) $filtrosGrupos['grupo'] === (string) $grupo)>{{ $grupo }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex gap-2 md:col-span-2 xl:col-span-6">
                    <button class="rounded-md bg-[#15529A] px-4 py-2 text-sm font-bold text-white transition hover:bg-[#0D376D]">Filtrar</button>
                    <a href="{{ route('modulos.show', 'carreras-grupos') }}" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-bold text-slate-700 transition hover:bg-slate-50">Limpiar</a>
                </div>
            </form>

            <div class="max-h-[520px] overflow-x-auto overflow-y-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="sticky top-0 z-10 bg-[#0D376D] text-white">
                        <tr>
                            <th class="whitespace-nowrap px-4 py-3 text-left font-bold">Carrera</th>
                            <th class="whitespace-nowrap px-4 py-3 text-left font-bold">Periodo</th>
                            <th class="whitespace-nowrap px-4 py-3 text-left font-bold">Grado</th>
                            <th class="whitespace-nowrap px-4 py-3 text-left font-bold">Grupo</th>
                            <th class="whitespace-nowrap px-4 py-3 text-left font-bold">Alumnos</th>
                            <th class="whitespace-nowrap px-4 py-3 text-left font-bold">Equipos</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @forelse ($gruposTabla as $grupoAcademico)
                            <tr class="hover:bg-slate-50">
                                <td class="whitespace-nowrap px-4 py-3 font-semibold text-slate-800">{{ $grupoAcademico->carrera->clave }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-slate-600">{{ $grupoAcademico->periodo->nombre }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-slate-600">{{ $grupoAcademico->grado }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-slate-600">{{ $grupoAcademico->grupo }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-slate-600">{{ $grupoAcademico->alumnos_count }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-slate-600">{{ $grupoAcademico->equipos_count }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-6 text-center text-sm text-slate-500">Aún no hay grupos registrados.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-200 px-4 py-3">
                {{ $gruposTabla->links() }}
            </div>
        </article>
    </section>
</x-contenedor-aplicacion>
