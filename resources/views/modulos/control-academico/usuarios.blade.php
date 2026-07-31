<x-contenedor-aplicacion title="Usuarios | Administración de proyectos" active="usuarios" :navegacion="$navegacion" :role-name="$roleName">
    <section class="border-b border-slate-200 pb-5">
        <p class="text-sm font-semibold uppercase tracking-[0.18em] text-[#21A366]">{{ $esCoordinacion ? 'Control de acceso' : 'Grupos asignados' }}</p>
        <h2 class="mt-2 text-2xl font-bold text-[#0D376D]">{{ $esCoordinacion ? 'Usuarios' : 'Lista de alumnos' }}</h2>
        <p class="mt-2 max-w-4xl text-sm leading-6 text-slate-600">
            {{ $esCoordinacion ? 'Los alumnos se cargan por listas y periodo. Los docentes permanecen en el sistema, pero sus asignaciones académicas se realizan nuevamente en cada periodo.' : 'Consulta y carga únicamente los alumnos de los grupos donde eres docente líder.' }}
        </p>
    </section>

    <x-mensajes-formulario />

    @if($esCoordinacion)
    <nav class="mt-5 flex gap-2 rounded-lg border border-slate-200 bg-white p-2 shadow-sm" aria-label="Secciones de usuarios">
        <button type="button" data-user-tab="alumnos" class="user-tab flex-1 rounded-md px-5 py-3 text-left transition">
            <span class="block text-xs font-bold uppercase tracking-[0.16em]">Alumnos</span>
            <span class="mt-1 block text-sm opacity-75">{{ $alumnos->count() }} en la lista seleccionada</span>
        </button>
        <button type="button" data-user-tab="docentes" class="user-tab flex-1 rounded-md px-5 py-3 text-left transition">
            <span class="block text-xs font-bold uppercase tracking-[0.16em]">Docentes</span>
            <span class="mt-1 block text-sm opacity-75">{{ $docentes->count() }} en la carrera seleccionada</span>
        </button>
    </nav>
    @endif

    <section data-user-panel="alumnos" class="mt-5 space-y-5">
        @if($esCoordinacion)
        <article class="rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 p-5">
                <p class="text-xs font-bold uppercase tracking-[0.16em] text-[#21A366]">Carga principal</p>
                <h3 class="mt-1 text-lg font-bold text-[#0D376D]">Listas de alumnos por periodo</h3>
                <p class="mt-2 text-sm text-slate-600">Agrega una o varias listas. Para cada archivo indica carrera, grado y grupo; el Excel solo necesita cédula/matrícula, nómina/nombre y correo.</p>
            </div>
            <form method="POST" action="{{ route('usuarios.alumnos.previsualizar') }}" enctype="multipart/form-data" class="p-5">
                @csrf
                <div class="max-w-sm">
                    <label class="block text-sm font-semibold text-slate-700">Periodo de la carga</label>
                    <select name="periodo_id" required class="mt-2 block w-full rounded-md border-slate-300 text-sm">
                        @foreach ($periodos as $periodo)
                            <option value="{{ $periodo->id }}" @selected((string) $filtrosAlumnos['periodo_id'] === (string) $periodo->id)>
                                {{ $periodo->nombre }}{{ $periodo->estado === 'activo' ? ' · Activo' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div id="student-list-files" class="mt-5 space-y-3"></div>
                <div class="mt-4 flex flex-wrap gap-3">
                    <button type="button" id="add-student-list" class="rounded-md border border-[#15529A] px-4 py-2.5 text-sm font-bold text-[#15529A] hover:bg-[#EAF2FB]">+ Agregar otra lista</button>
                    <button class="rounded-md bg-[#15529A] px-5 py-2.5 text-sm font-bold text-white hover:bg-[#0D376D]">Generar vista previa</button>
                </div>
            </form>
        </article>

        @if ($vistaPrevia)
            <article class="rounded-lg border-2 border-[#21A366] bg-white shadow-sm">
                <div class="flex flex-col gap-3 border-b border-slate-200 p-5 md:flex-row md:items-center md:justify-between">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.16em] text-[#21A366]">Revisión antes de guardar</p>
                        <h3 class="mt-1 text-lg font-bold text-[#0D376D]">Vista previa de {{ count($vistaPrevia['listas']) }} listas</h3>
                    </div>
                    <form method="POST" action="{{ route('usuarios.alumnos.confirmar') }}">
                        @csrf
                        <input type="hidden" name="preview" value="{{ $tokenVistaPrevia }}">
                        <button class="rounded-md bg-[#21A366] px-5 py-2.5 text-sm font-bold text-white hover:bg-[#17804F]">Confirmar y cargar alumnos</button>
                    </form>
                </div>
                <div class="space-y-5 p-5">
                    @foreach ($vistaPrevia['listas'] as $indiceLista => $lista)
                        @php($carreraLista = $carreras->firstWhere('id', $lista['carrera_id']))
                        <section class="overflow-hidden rounded-md border border-slate-200" data-preview-table>
                            <div class="flex flex-wrap items-center justify-between gap-2 bg-slate-50 px-4 py-3">
                                <div>
                                    <h4 class="font-bold text-slate-900">{{ $lista['nombre'] }}</h4>
                                    <p class="text-sm text-slate-500">{{ $carreraLista?->clave }} · {{ $lista['grado'] }}{{ $lista['grupo'] }} · {{ count($lista['alumnos']) }} alumnos detectados</p>
                                </div>
                                <span class="rounded-full bg-[#EAF7EF] px-3 py-1 text-xs font-bold text-[#0F7D47]">{{ count($lista['alumnos']) }} admitibles</span>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-slate-200 text-sm">
                                    <thead class="bg-[#0D376D] text-white"><tr><th class="px-4 py-3 text-left">#</th><th class="px-4 py-3 text-left">Matrícula / cédula</th><th class="px-4 py-3 text-left">Nombre</th><th class="px-4 py-3 text-left">Correo</th></tr></thead>
                                    <tbody class="divide-y divide-slate-100">
                                        @foreach ($lista['alumnos'] as $indice => $alumno)
                                            <tr data-preview-row class="hover:bg-slate-50"><td class="px-4 py-3 text-slate-500">{{ $indice + 1 }}</td><td class="px-4 py-3 font-semibold">{{ $alumno['matricula'] }}</td><td class="px-4 py-3">{{ $alumno['nombre_completo'] }}</td><td class="px-4 py-3">{{ $alumno['correo_electronico'] }}</td></tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <div class="flex items-center justify-between border-t border-slate-200 px-4 py-3 text-sm">
                                <button type="button" data-preview-prev class="font-bold text-[#15529A]">Anterior</button>
                                <span data-preview-page class="text-slate-500"></span>
                                <button type="button" data-preview-next class="font-bold text-[#15529A]">Siguiente</button>
                            </div>
                        </section>
                    @endforeach
                </div>
            </article>
        @endif
        @endif

        <article data-async-region="students-list" class="rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-col gap-3 border-b border-slate-200 p-5 lg:flex-row lg:items-end lg:justify-between">
                <div><h3 class="font-bold text-slate-900">Alumnos del periodo</h3><p class="mt-1 text-sm text-slate-500">Cada periodo inicia con sus propias listas, grupos, guías y proyectos.</p></div>
                <form method="GET" action="{{ route('modulos.show', 'usuarios') }}" data-async-form data-async-targets="students-list" class="flex flex-wrap items-end gap-2">
                    <input type="hidden" name="seccion" value="alumnos">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wide text-slate-500">Periodo</label>
                        <select name="periodo_alumnos" class="mt-1 rounded-md border-slate-300 text-sm">@foreach ($periodos as $periodo)<option value="{{ $periodo->id }}" @selected((string) $filtrosAlumnos['periodo_id'] === (string) $periodo->id)>{{ $periodo->nombre }}</option>@endforeach</select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wide text-slate-500">Carrera</label>
                        <select name="carrera_alumnos" class="mt-1 rounded-md border-slate-300 text-sm">@foreach ($carreras as $carrera)<option value="{{ $carrera->id }}" @selected((string) $filtrosAlumnos['carrera_id'] === (string) $carrera->id)>{{ $carrera->clave }} - {{ $carrera->nombre }}</option>@endforeach</select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wide text-slate-500">Buscar matrícula</label>
                        <input name="busqueda_alumnos" value="{{ $filtrosAlumnos['busqueda'] }}" placeholder="Matrícula, nombre o correo" class="mt-1 rounded-md border-slate-300 text-sm">
                    </div>
                    <button class="rounded-md bg-[#15529A] px-4 py-2 text-sm font-bold text-white">Cambiar carrera / filtrar</button>
                </form>
            </div>
            <nav class="flex flex-wrap gap-2 border-b border-slate-200 bg-slate-50 px-5 py-3" aria-label="Listas por grado y grupo">
                @forelse ($gruposPaginacion as $grupoLista)
                    <a href="{{ route('modulos.show', ['modulo' => 'usuarios', 'seccion' => 'alumnos', 'periodo_alumnos' => $filtrosAlumnos['periodo_id'], 'carrera_alumnos' => $filtrosAlumnos['carrera_id'], 'grupo_alumnos' => $grupoLista->id, 'busqueda_alumnos' => $filtrosAlumnos['busqueda']]) }}"
                       data-async-link data-async-targets="students-list"
                       class="rounded-md px-4 py-2 text-sm font-bold transition {{ (int) $grupoSeleccionado === (int) $grupoLista->id ? 'bg-[#0D376D] text-white shadow-sm' : 'border border-slate-300 bg-white text-[#15529A] hover:bg-[#EAF2FB]' }}">
                        {{ $grupoLista->grado }}{{ $grupoLista->grupo }}
                    </a>
                @empty
                    <span class="text-sm text-slate-500">No hay listas para esta carrera y periodo.</span>
                @endforelse
            </nav>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50"><tr><th class="px-4 py-3 text-left">Matrícula</th><th class="px-4 py-3 text-left">Nombre</th><th class="px-4 py-3 text-left">Correo</th><th class="px-4 py-3 text-left">Carrera</th><th class="px-4 py-3 text-left">Grupo</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($alumnos as $alumno)
                            <tr><td class="px-4 py-3 font-semibold">{{ $alumno->matricula }}</td><td class="px-4 py-3">{{ $alumno->nombre }}</td><td class="px-4 py-3">{{ $alumno->correo }}</td><td class="px-4 py-3">{{ $alumno->carrera?->clave ?? '-' }}</td><td class="px-4 py-3">{{ $alumno->grupoAcademico?->nombre ?? '-' }}</td></tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-8 text-center text-slate-500">No hay alumnos en esta lista o no coinciden con la búsqueda.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-200 px-5 py-3 text-sm text-slate-500">
                Lista completa: <strong class="text-slate-800">{{ $alumnos->count() }} alumnos</strong>
            </div>
        </article>
    </section>

    @if($esCoordinacion)
    <section data-user-panel="docentes" class="mt-5 space-y-5">
        <article class="rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 p-5">
                <p class="text-xs font-bold uppercase tracking-[0.16em] text-[#21A366]">Alta múltiple</p>
                <h3 class="mt-1 text-lg font-bold text-[#0D376D]">Registrar docentes</h3>
                <p class="mt-2 text-sm text-slate-600">Los docentes se conservan entre periodos. Agrega tantas filas como necesites y guárdalas en una sola operación.</p>
            </div>
            <form method="POST" action="{{ route('usuarios.docentes.guardar') }}" class="p-5">
                @csrf
                <div id="teacher-rows" class="space-y-3"></div>
                <div class="mt-4 flex flex-wrap gap-3">
                    <button type="button" id="add-teacher" class="rounded-md border border-[#15529A] px-4 py-2.5 text-sm font-bold text-[#15529A] hover:bg-[#EAF2FB]">+ Agregar docente</button>
                    <button class="rounded-md bg-[#15529A] px-5 py-2.5 text-sm font-bold text-white hover:bg-[#0D376D]">Guardar docentes</button>
                </div>
            </form>
        </article>

        <article data-async-region="teachers-list" class="rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-col gap-3 border-b border-slate-200 p-5 lg:flex-row lg:items-end lg:justify-between">
                <div><h3 class="font-bold text-slate-900">Docentes registrados</h3><p class="mt-1 text-sm text-slate-500">Lista completa por carrera, sin paginación numérica.</p></div>
                <form method="GET" action="{{ route('modulos.show', 'usuarios') }}" data-async-form data-async-targets="teachers-list" class="flex flex-wrap items-end gap-2">
                    <input type="hidden" name="seccion" value="docentes">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wide text-slate-500">Carrera</label>
                        <select name="carrera_docentes" class="mt-1 rounded-md border-slate-300 text-sm">@foreach ($carreras as $carrera)<option value="{{ $carrera->id }}" @selected((string) $filtrosDocentes['carrera_id'] === (string) $carrera->id)>{{ $carrera->clave }} - {{ $carrera->nombre }}</option>@endforeach</select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wide text-slate-500">Buscar docente</label>
                        <input name="busqueda_docentes" value="{{ $filtrosDocentes['busqueda'] }}" placeholder="Clave, nombre o correo" class="mt-1 rounded-md border-slate-300 text-sm">
                    </div>
                    <button class="rounded-md bg-[#15529A] px-4 py-2 text-sm font-bold text-white">Cambiar carrera / filtrar</button>
                </form>
            </div>
            <nav class="flex flex-wrap gap-2 border-b border-slate-200 bg-slate-50 px-5 py-3" aria-label="Docentes por carrera">
                @foreach ($carreras as $carrera)
                    <a href="{{ route('modulos.show', ['modulo' => 'usuarios', 'seccion' => 'docentes', 'carrera_docentes' => $carrera->id, 'busqueda_docentes' => $filtrosDocentes['busqueda']]) }}"
                       data-async-link data-async-targets="teachers-list"
                       class="rounded-md px-4 py-2 text-sm font-bold transition {{ (int) $filtrosDocentes['carrera_id'] === (int) $carrera->id ? 'bg-[#0D376D] text-white shadow-sm' : 'border border-slate-300 bg-white text-[#15529A] hover:bg-[#EAF2FB]' }}">
                        {{ $carrera->clave }}
                    </a>
                @endforeach
            </nav>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50"><tr><th class="px-4 py-3 text-left">Clave</th><th class="px-4 py-3 text-left">Nombre</th><th class="px-4 py-3 text-left">Rol</th><th class="px-4 py-3 text-left">Carrera</th><th class="px-4 py-3 text-left">Correo</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($docentes as $docente)
                            <tr><td class="px-4 py-3 font-semibold">{{ $docente->matricula }}</td><td class="px-4 py-3">{{ $docente->nombre }}</td><td class="px-4 py-3">{{ $docente->role?->nombre_visible }}</td><td class="px-4 py-3">{{ $docente->carrera?->clave ?? '-' }}</td><td class="px-4 py-3">{{ $docente->correo }}</td></tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-8 text-center text-slate-500">No hay docentes en esta carrera o no coinciden con la búsqueda.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-200 px-5 py-3 text-sm text-slate-500">Lista completa: <strong class="text-slate-800">{{ $docentes->count() }} docentes</strong></div>
        </article>
    </section>
    @endif

    @if($esCoordinacion)
    <template id="student-list-template">
        <div class="student-list-row grid gap-3 rounded-md border border-slate-200 bg-slate-50 p-4 lg:grid-cols-[1.5fr_1fr_110px_110px_auto]">
            <div><label class="text-xs font-bold uppercase text-slate-500">Archivo Excel</label><input type="file" accept=".xlsx,.xls,.csv,.txt" required class="mt-2 block w-full text-sm"></div>
            <div><label class="text-xs font-bold uppercase text-slate-500">Carrera</label><select required class="mt-2 block w-full rounded-md border-slate-300 text-sm">@foreach($carreras as $carrera)<option value="{{ $carrera->id }}">{{ $carrera->clave }}</option>@endforeach</select></div>
            <div><label class="text-xs font-bold uppercase text-slate-500">Grado</label><input type="number" min="1" max="12" required class="mt-2 block w-full rounded-md border-slate-300 text-sm"></div>
            <div><label class="text-xs font-bold uppercase text-slate-500">Grupo</label><input maxlength="10" required placeholder="A" class="mt-2 block w-full rounded-md border-slate-300 text-sm uppercase"></div>
            <button type="button" class="remove-row self-end rounded-md px-3 py-2 text-sm font-bold text-red-600">Quitar</button>
        </div>
    </template>
    @endif

    @if($esCoordinacion)
    <template id="teacher-template">
        <div class="teacher-row grid gap-3 rounded-md border border-slate-200 bg-slate-50 p-4 lg:grid-cols-6">
            <input required placeholder="Nombre completo" class="rounded-md border-slate-300 text-sm lg:col-span-2">
            <input required placeholder="Matrícula / clave" class="rounded-md border-slate-300 text-sm">
            <input type="email" required placeholder="Correo" class="rounded-md border-slate-300 text-sm">
            <select required class="rounded-md border-slate-300 text-sm">@foreach($rolesDocentes as $rol)<option value="{{ $rol->nombre }}">{{ $rol->nombre_visible }}</option>@endforeach</select>
            <div class="flex gap-2"><select required class="min-w-0 flex-1 rounded-md border-slate-300 text-sm">@foreach($carreras as $carrera)<option value="{{ $carrera->id }}">{{ $carrera->clave }}</option>@endforeach</select><button type="button" class="remove-row font-bold text-red-600">×</button></div>
        </div>
    </template>
    @endif

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const active = @json($seccionActiva);
            const activate = (name) => {
                document.querySelectorAll('[data-user-panel]').forEach(panel => panel.hidden = panel.dataset.userPanel !== name);
                document.querySelectorAll('[data-user-tab]').forEach(tab => {
                    const selected = tab.dataset.userTab === name;
                    tab.className = `user-tab flex-1 rounded-md px-5 py-3 text-left transition ${selected ? 'bg-[#0D376D] text-white shadow-sm' : 'text-slate-600 hover:bg-slate-50'}`;
                });
            };
            document.querySelectorAll('[data-user-tab]').forEach(tab => tab.addEventListener('click', () => activate(tab.dataset.userTab)));
            activate(active);

            const addRepeatedRow = (containerId, templateId, prefix, fields) => {
                const container = document.getElementById(containerId);
                const fragment = document.getElementById(templateId).content.cloneNode(true);
                const row = fragment.firstElementChild;
                const index = Number(container.dataset.nextIndex || 0);
                container.dataset.nextIndex = String(index + 1);
                row.querySelectorAll('input, select').forEach((field, fieldIndex) => field.name = `${prefix}[${index}][${fields[fieldIndex]}]`);
                row.querySelector('.remove-row').addEventListener('click', () => {
                    if (container.children.length > 1) row.remove();
                });
                container.appendChild(row);
            };
            const addList = () => addRepeatedRow('student-list-files', 'student-list-template', 'listas', ['archivo', 'carrera_id', 'grado', 'grupo']);
            const addTeacher = () => addRepeatedRow('teacher-rows', 'teacher-template', 'docentes', ['nombre', 'matricula', 'correo', 'rol', 'carrera_id']);
            document.getElementById('add-student-list')?.addEventListener('click', addList);
            document.getElementById('add-teacher')?.addEventListener('click', addTeacher);
            if (document.getElementById('student-list-files')) addList();
            if (document.getElementById('teacher-rows')) addTeacher();

            document.querySelectorAll('[data-preview-table]').forEach(table => {
                const rows = [...table.querySelectorAll('[data-preview-row]')];
                const size = 10;
                let page = 1;
                const pages = Math.max(1, Math.ceil(rows.length / size));
                const render = () => {
                    rows.forEach((row, index) => row.hidden = index < (page - 1) * size || index >= page * size);
                    table.querySelector('[data-preview-page]').textContent = `Página ${page} de ${pages}`;
                    table.querySelector('[data-preview-prev]').disabled = page === 1;
                    table.querySelector('[data-preview-next]').disabled = page === pages;
                };
                table.querySelector('[data-preview-prev]').addEventListener('click', () => { if (page > 1) { page--; render(); } });
                table.querySelector('[data-preview-next]').addEventListener('click', () => { if (page < pages) { page++; render(); } });
                render();
            });
        });
    </script>
</x-contenedor-aplicacion>
