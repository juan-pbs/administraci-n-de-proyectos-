<x-contenedor-aplicacion title="Usuarios | Administracion de proyectos" active="usuarios" :navegacion="$navegacion" :role-name="$roleName">
    <section class="border-b border-slate-200 pb-5">
        <p class="text-sm font-semibold uppercase tracking-[0.18em] text-[#21A366]">Control de acceso</p>
        <h2 class="mt-2 text-2xl font-bold text-[#0D376D]">Usuarios</h2>
        <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">
            Gestion separada para alumnos y docentes/asesores. Los alumnos se ligan a carrera y grupo; los docentes se ligan a carrera para despues participar como asesores o evaluadores.
        </p>
    </section>

    <x-mensajes-formulario />

    <section class="mt-5 grid gap-3 md:grid-cols-4">
        @foreach ($metricasUsuarios as $metrica)
            <article class="rounded-md border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">{{ $metrica['label'] }}</p>
                <p class="mt-2 text-2xl font-bold text-[#0D376D]">{{ $metrica['value'] }}</p>
            </article>
        @endforeach
    </section>

    <section class="mt-5 grid gap-5 xl:grid-cols-2">
        <article class="rounded-md border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4">
                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-[#21A366]">Alumnos</p>
                <h3 class="mt-1 font-bold text-slate-900">Nuevo alumno</h3>
                <p class="mt-1 text-sm text-slate-500">Alta individual o carga masiva por lista de alumnos. La contrasena temporal se genera automaticamente y se envia por correo.</p>
            </div>
            <form method="POST" action="{{ route('usuarios.guardar') }}" class="space-y-4 p-5">
                @csrf
                <input type="hidden" name="rol_id" value="{{ $rolEstudiante?->id }}">

                <div>
                    <label class="block text-sm font-semibold text-slate-700">Nombre completo</label>
                    <input name="nombre" value="{{ old('nombre') }}" required class="mt-2 block w-full rounded-md border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-[#15529A] focus:ring-2 focus:ring-[#15529A]/20">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700">Matricula</label>
                    <input name="matricula" value="{{ old('matricula') }}" required class="mt-2 block w-full rounded-md border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-[#15529A] focus:ring-2 focus:ring-[#15529A]/20">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700">Correo electronico</label>
                    <input name="correo" type="email" value="{{ old('correo') }}" required class="mt-2 block w-full rounded-md border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-[#15529A] focus:ring-2 focus:ring-[#15529A]/20">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700">Carrera</label>
                    <select name="carrera_id" required class="mt-2 block w-full rounded-md border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-[#15529A] focus:ring-2 focus:ring-[#15529A]/20">
                        @foreach ($carreras as $carrera)
                            <option value="{{ $carrera->id }}">{{ $carrera->clave }} - {{ $carrera->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700">Grupo academico</label>
                    <select name="grupo_academico_id" required class="mt-2 block w-full rounded-md border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-[#15529A] focus:ring-2 focus:ring-[#15529A]/20">
                        @foreach ($grupos as $grupo)
                            <option value="{{ $grupo->id }}">{{ $grupo->carrera->clave }} - {{ $grupo->grado }}{{ $grupo->grupo }}</option>
                        @endforeach
                    </select>
                </div>
                <button class="rounded-md bg-[#15529A] px-4 py-2.5 text-sm font-bold text-white transition hover:bg-[#0D376D]">Guardar alumno</button>
            </form>
        </article>

        <article class="rounded-md border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4">
                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-[#21A366]">Docentes / asesores</p>
                <h3 class="mt-1 font-bold text-slate-900">Nuevo docente / asesor</h3>
                <p class="mt-1 text-sm text-slate-500">Alta individual; no se cargan listas para docentes. La contrasena temporal se genera automaticamente y se envia por correo.</p>
            </div>
            <form method="POST" action="{{ route('usuarios.guardar') }}" class="space-y-4 p-5">
                @csrf
                <input type="hidden" name="rol_id" value="{{ $rolDocente?->id }}">

                <div>
                    <label class="block text-sm font-semibold text-slate-700">Nombre completo</label>
                    <input name="nombre" required class="mt-2 block w-full rounded-md border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-[#15529A] focus:ring-2 focus:ring-[#15529A]/20">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700">Matricula / clave</label>
                    <input name="matricula" required class="mt-2 block w-full rounded-md border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-[#15529A] focus:ring-2 focus:ring-[#15529A]/20">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700">Correo electronico</label>
                    <input name="correo" type="email" required class="mt-2 block w-full rounded-md border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-[#15529A] focus:ring-2 focus:ring-[#15529A]/20">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700">Carrera principal</label>
                    <select name="carrera_id" required class="mt-2 block w-full rounded-md border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-[#15529A] focus:ring-2 focus:ring-[#15529A]/20">
                        @foreach ($carreras as $carrera)
                            <option value="{{ $carrera->id }}">{{ $carrera->clave }} - {{ $carrera->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <input type="hidden" name="grupo_academico_id" value="">
                <button class="rounded-md bg-[#15529A] px-4 py-2.5 text-sm font-bold text-white transition hover:bg-[#0D376D]">Guardar docente</button>
            </form>
        </article>
    </section>

    <section class="mt-5 rounded-md border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-[#21A366]">Carga masiva exclusiva para alumnos</p>
                <h3 class="mt-1 font-bold text-slate-900">Importar lista de alumnos</h3>
                <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">
                    Este proceso se usara unicamente para alumnos. La lista debera incluir matricula, nombre, correo, carrera, grado, grupo y, cuando aplique, equipo asignado.
                </p>
            </div>
            <form method="POST" action="{{ route('usuarios.alumnos.importar') }}" enctype="multipart/form-data" class="grid gap-3 sm:grid-cols-[220px_minmax(220px,1fr)_auto] sm:items-end">
                @csrf
                <div>
                    <label class="block text-sm font-semibold text-slate-700">Periodo</label>
                    <select name="periodo_id" required class="mt-2 block w-full rounded-md border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-[#15529A] focus:ring-2 focus:ring-[#15529A]/20">
                        @foreach ($periodos as $periodo)
                            <option value="{{ $periodo->id }}">{{ $periodo->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700">Archivo Excel / CSV</label>
                    <input name="archivo" type="file" accept=".xlsx,.xls,.csv,.txt" required class="mt-2 block w-full rounded-md border border-slate-300 text-sm text-slate-700 file:mr-3 file:border-0 file:bg-[#EAF7EF] file:px-3 file:py-2.5 file:text-sm file:font-semibold file:text-[#0F7D47]">
                </div>
                <button class="rounded-md border border-[#15529A] bg-white px-4 py-2.5 text-sm font-bold text-[#15529A] transition hover:bg-[#EAF2FB]">
                    Cargar lista
                </button>
            </form>
        </div>
        <div class="mt-4 flex flex-wrap gap-3">
            <a href="{{ asset('plantillas/plantilla_carga_alumnos_sin_equipos.xlsx') }}" class="rounded-md bg-[#15529A] px-4 py-2.5 text-sm font-bold text-white transition hover:bg-[#0D376D]">
                Descargar plantilla sin equipos
            </a>
            <a href="{{ asset('plantillas/plantilla_carga_alumnos_con_equipos.xlsx') }}" class="rounded-md border border-[#15529A] bg-white px-4 py-2.5 text-sm font-bold text-[#15529A] transition hover:bg-[#EAF2FB]">
                Descargar plantilla con equipos
            </a>
        </div>
    </section>

    <section class="mt-5 grid gap-5 2xl:grid-cols-2">
        <article class="min-w-0 rounded-md border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4">
                <h3 class="font-bold text-slate-900">Alumnos registrados</h3>
                <p class="mt-1 text-sm text-slate-500">Filtra por matricula, nombre, carrera o grupo academico.</p>
            </div>

            <form method="GET" action="{{ route('modulos.show', 'usuarios') }}" class="grid gap-3 border-b border-slate-200 p-4 md:grid-cols-2 xl:grid-cols-5">
                <div class="xl:col-span-2">
                    <label for="busqueda_alumnos" class="block text-xs font-bold uppercase tracking-[0.12em] text-slate-500">Buscar</label>
                    <input id="busqueda_alumnos" name="busqueda_alumnos" value="{{ $filtrosAlumnos['busqueda'] }}" placeholder="Matricula, nombre o correo" class="mt-2 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm outline-none focus:border-[#15529A] focus:ring-2 focus:ring-[#15529A]/20">
                </div>
                <div>
                    <label for="carrera_alumnos" class="block text-xs font-bold uppercase tracking-[0.12em] text-slate-500">Carrera</label>
                    <select id="carrera_alumnos" name="carrera_alumnos" class="mt-2 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm outline-none focus:border-[#15529A] focus:ring-2 focus:ring-[#15529A]/20">
                        <option value="">Todas</option>
                        @foreach ($carreras as $carrera)
                            <option value="{{ $carrera->id }}" @selected((string) $filtrosAlumnos['carrera_id'] === (string) $carrera->id)>{{ $carrera->clave }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="grupo_alumnos" class="block text-xs font-bold uppercase tracking-[0.12em] text-slate-500">Grupo</label>
                    <select id="grupo_alumnos" name="grupo_alumnos" class="mt-2 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm outline-none focus:border-[#15529A] focus:ring-2 focus:ring-[#15529A]/20">
                        <option value="">Todos</option>
                        @foreach ($grupos as $grupo)
                            <option value="{{ $grupo->id }}" @selected((string) $filtrosAlumnos['grupo_academico_id'] === (string) $grupo->id)>{{ $grupo->carrera->clave }} - {{ $grupo->grado }}{{ $grupo->grupo }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="estado_alumnos" class="block text-xs font-bold uppercase tracking-[0.12em] text-slate-500">Estado</label>
                    <select id="estado_alumnos" name="estado_alumnos" class="mt-2 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm outline-none focus:border-[#15529A] focus:ring-2 focus:ring-[#15529A]/20">
                        <option value="">Todos</option>
                        <option value="activo" @selected($filtrosAlumnos['estado'] === 'activo')>Activo</option>
                        <option value="inactivo" @selected($filtrosAlumnos['estado'] === 'inactivo')>Inactivo</option>
                    </select>
                </div>
                <div class="flex gap-2 md:col-span-2 xl:col-span-5">
                    <button class="rounded-md bg-[#15529A] px-4 py-2 text-sm font-bold text-white transition hover:bg-[#0D376D]">Filtrar alumnos</button>
                    <a href="{{ route('modulos.show', 'usuarios') }}" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-bold text-slate-700 transition hover:bg-slate-50">Limpiar</a>
                </div>
            </form>

            <div class="max-h-[520px] overflow-x-auto overflow-y-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="sticky top-0 z-10 bg-[#0D376D] text-white">
                        <tr>
                            <th class="whitespace-nowrap px-4 py-3 text-left">Matricula</th>
                            <th class="whitespace-nowrap px-4 py-3 text-left">Nombre</th>
                            <th class="whitespace-nowrap px-4 py-3 text-left">Carrera</th>
                            <th class="whitespace-nowrap px-4 py-3 text-left">Grupo</th>
                            <th class="whitespace-nowrap px-4 py-3 text-left">Estado</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @forelse ($alumnos as $alumno)
                            <tr class="hover:bg-slate-50">
                                <td class="whitespace-nowrap px-4 py-3 font-semibold text-slate-800">{{ $alumno->matricula }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $alumno->nombre }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-slate-600">{{ $alumno->carrera?->clave ?? '-' }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-slate-600">{{ $alumno->grupoAcademico?->nombre ?? '-' }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-slate-600">{{ ucfirst($alumno->estado) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-6 text-center text-slate-500">Aun no hay alumnos registrados con esos filtros.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-200 px-4 py-3">
                {{ $alumnos->links() }}
            </div>
        </article>

        <article class="min-w-0 rounded-md border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4">
                <h3 class="font-bold text-slate-900">Docentes / asesores registrados</h3>
                <p class="mt-1 text-sm text-slate-500">Filtra por clave, nombre, correo o carrera principal.</p>
            </div>

            <form method="GET" action="{{ route('modulos.show', 'usuarios') }}" class="grid gap-3 border-b border-slate-200 p-4 md:grid-cols-2 xl:grid-cols-4">
                <div class="xl:col-span-2">
                    <label for="busqueda_docentes" class="block text-xs font-bold uppercase tracking-[0.12em] text-slate-500">Buscar</label>
                    <input id="busqueda_docentes" name="busqueda_docentes" value="{{ $filtrosDocentes['busqueda'] }}" placeholder="Clave, nombre o correo" class="mt-2 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm outline-none focus:border-[#15529A] focus:ring-2 focus:ring-[#15529A]/20">
                </div>
                <div>
                    <label for="carrera_docentes" class="block text-xs font-bold uppercase tracking-[0.12em] text-slate-500">Carrera</label>
                    <select id="carrera_docentes" name="carrera_docentes" class="mt-2 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm outline-none focus:border-[#15529A] focus:ring-2 focus:ring-[#15529A]/20">
                        <option value="">Todas</option>
                        @foreach ($carreras as $carrera)
                            <option value="{{ $carrera->id }}" @selected((string) $filtrosDocentes['carrera_id'] === (string) $carrera->id)>{{ $carrera->clave }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="estado_docentes" class="block text-xs font-bold uppercase tracking-[0.12em] text-slate-500">Estado</label>
                    <select id="estado_docentes" name="estado_docentes" class="mt-2 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm outline-none focus:border-[#15529A] focus:ring-2 focus:ring-[#15529A]/20">
                        <option value="">Todos</option>
                        <option value="activo" @selected($filtrosDocentes['estado'] === 'activo')>Activo</option>
                        <option value="inactivo" @selected($filtrosDocentes['estado'] === 'inactivo')>Inactivo</option>
                    </select>
                </div>
                <div class="flex gap-2 md:col-span-2 xl:col-span-4">
                    <button class="rounded-md bg-[#15529A] px-4 py-2 text-sm font-bold text-white transition hover:bg-[#0D376D]">Filtrar docentes</button>
                    <a href="{{ route('modulos.show', 'usuarios') }}" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-bold text-slate-700 transition hover:bg-slate-50">Limpiar</a>
                </div>
            </form>

            <div class="max-h-[520px] overflow-x-auto overflow-y-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="sticky top-0 z-10 bg-[#0D376D] text-white">
                        <tr>
                            <th class="whitespace-nowrap px-4 py-3 text-left">Clave</th>
                            <th class="whitespace-nowrap px-4 py-3 text-left">Nombre</th>
                            <th class="whitespace-nowrap px-4 py-3 text-left">Carrera principal</th>
                            <th class="whitespace-nowrap px-4 py-3 text-left">Cambiar carrera</th>
                            <th class="whitespace-nowrap px-4 py-3 text-left">Correo</th>
                            <th class="whitespace-nowrap px-4 py-3 text-left">Estado</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @forelse ($docentes as $docente)
                            <tr class="hover:bg-slate-50">
                                <td class="whitespace-nowrap px-4 py-3 font-semibold text-slate-800">{{ $docente->matricula }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $docente->nombre }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-slate-600">{{ $docente->carrera?->clave ?? '-' }}</td>
                                <td class="min-w-72 px-4 py-3">
                                    <form method="POST" action="{{ route('usuarios.docentes.carrera.actualizar') }}" class="flex gap-2">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="docente_id" value="{{ $docente->id }}">
                                        <select name="carrera_id" class="block w-full rounded-md border border-slate-300 px-2 py-1.5 text-sm outline-none focus:border-[#15529A] focus:ring-2 focus:ring-[#15529A]/20">
                                            @foreach ($carreras as $carrera)
                                                <option value="{{ $carrera->id }}" @selected((int) $docente->carrera_id === (int) $carrera->id)>{{ $carrera->clave }}</option>
                                            @endforeach
                                        </select>
                                        <button class="rounded-md bg-[#15529A] px-3 py-1.5 text-xs font-bold text-white transition hover:bg-[#0D376D]">Guardar</button>
                                    </form>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-slate-600">{{ $docente->correo }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-slate-600">{{ ucfirst($docente->estado) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-6 text-center text-slate-500">Aun no hay docentes registrados con esos filtros.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-200 px-4 py-3">
                {{ $docentes->links() }}
            </div>
        </article>
    </section>

    @if ($usuariosDireccion->isNotEmpty())
        <article class="mt-5 rounded-md border border-slate-200 bg-white p-5 shadow-sm">
            <h3 class="font-bold text-slate-900">Usuarios de direccion / coordinacion</h3>
            <div class="mt-3 flex flex-wrap gap-2">
                @foreach ($usuariosDireccion as $usuarioDireccion)
                    <span class="rounded-md bg-slate-100 px-3 py-2 text-sm font-semibold text-slate-700">
                        {{ $usuarioDireccion->matricula }} - {{ $usuarioDireccion->nombre }}
                    </span>
                @endforeach
            </div>
        </article>
    @endif
</x-contenedor-aplicacion>
