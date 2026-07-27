<x-contenedor-aplicacion title="Jerarquia | Administracion de proyectos" active="jerarquia-proyectos" :navegacion="$navegacion" :role-name="$roleName">
    <section class="border-b border-slate-200 pb-5">
        <p class="text-sm font-semibold uppercase tracking-[0.18em] text-[#21A366]">Responsabilidad academica</p>
        <h2 class="mt-2 text-2xl font-bold text-[#0D376D]">Jerarquia de proyectos por cuatrimestre</h2>
        <p class="mt-2 max-w-4xl text-sm leading-6 text-slate-600">El encargado administra una carrera y cuatrimestre; designa al docente lider y su materia. El lider carga alumnos, forma equipos y define el contexto de cada proyecto.</p>
    </section>

    <x-mensajes-formulario />

    <section data-async-region="acciones-jerarquia" class="mt-5 grid gap-5 xl:grid-cols-2">
        @if ($puedeCrearEncargos)
            <article class="rounded-md border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-5 py-4">
                    <h3 class="font-bold text-slate-900">Asignar encargado de proyectos</h3>
                </div>
                <form method="POST" action="{{ route('jerarquia.encargados.guardar') }}" class="grid gap-3 p-5 sm:grid-cols-2" data-async-form data-async-targets="mensajes acciones-jerarquia tabla-jerarquia">
                    @csrf
                    <select name="encargado_id" required class="rounded-md border-slate-300">
                        <option value="">Encargado</option>
                        @foreach($encargados as $item)
                            <option value="{{ $item->id }}">{{ $item->nombre }}</option>
                        @endforeach
                    </select>
                    <select name="periodo_id" required class="rounded-md border-slate-300">
                        <option value="">Periodo</option>
                        @foreach($periodos as $item)
                            <option value="{{ $item->id }}">{{ $item->nombre }}</option>
                        @endforeach
                    </select>
                    <select name="carrera_id" required class="rounded-md border-slate-300">
                        <option value="">Carrera</option>
                        @foreach($carreras as $item)
                            <option value="{{ $item->id }}">{{ $item->clave }} - {{ $item->nombre }}</option>
                        @endforeach
                    </select>
                    <input name="cuatrimestre" type="number" min="1" max="12" required placeholder="Cuatrimestre" class="rounded-md border-slate-300">
                    <button class="rounded-md bg-[#15529A] px-4 py-2.5 text-sm font-bold text-white sm:col-span-2">Guardar encargo</button>
                </form>
            </article>
        @endif

        <article class="rounded-md border border-slate-200 bg-white shadow-sm xl:col-span-2">
            <div class="border-b border-slate-200 px-5 py-4">
                <h3 class="font-bold text-slate-900">Asignar lider de proyecto a un grupo</h3>
            </div>
            <form method="POST" action="{{ route('jerarquia.lideres.guardar') }}" class="grid gap-3 p-5 md:grid-cols-3" data-async-form data-async-targets="mensajes acciones-jerarquia tabla-jerarquia">
                @csrf
                <select name="grupo_academico_id" required class="rounded-md border-slate-300">
                    <option value="">Grupo bajo mi responsabilidad</option>
                    @foreach($grupos as $grupo)
                        <option value="{{ $grupo->id }}">{{ $grupo->periodo->nombre }} - {{ $grupo->carrera->clave }} {{ $grupo->grado }}{{ $grupo->grupo }}</option>
                    @endforeach
                </select>
                <select name="lider_proyecto_id" required class="rounded-md border-slate-300">
                    <option value="">Docente lider</option>
                    @foreach($lideres as $item)
                        <option value="{{ $item->id }}">{{ $item->matricula }} - {{ $item->nombre }}</option>
                    @endforeach
                </select>
                <select name="asignatura_lider_id" required class="rounded-md border-slate-300">
                    <option value="">Materia lider</option>
                    @foreach($asignaturas as $item)
                        <option value="{{ $item->id }}">{{ $item->clave }} - {{ $item->nombre }}</option>
                    @endforeach
                </select>
                <div class="md:col-span-3">
                    <button class="rounded-md bg-[#15529A] px-4 py-2.5 text-sm font-bold text-white">Asignar lider</button>
                </div>
            </form>
        </article>
    </section>

    <section data-async-region="tabla-jerarquia" class="mt-5 overflow-x-auto rounded-md border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
                <tr>
                    @foreach(['Periodo','Carrera / cuatrimestre','Grupo','Lider','Materia lider'] as $h)
                        <th class="px-4 py-3 text-left text-xs font-bold uppercase text-slate-500">{{ $h }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
                @forelse($grupos as $grupo)
                    <tr>
                        <td class="px-4 py-3">{{ $grupo->periodo->nombre }}</td>
                        <td class="px-4 py-3">{{ $grupo->carrera->clave }} - {{ $grupo->grado }} grado</td>
                        <td class="px-4 py-3 font-semibold">{{ $grupo->grado }}{{ $grupo->grupo }}</td>
                        <td class="px-4 py-3">{{ $grupo->liderProyecto?->nombre ?? 'Pendiente' }}</td>
                        <td class="px-4 py-3">{{ $grupo->asignaturaLider?->nombre ?? 'Pendiente' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-6 text-center text-slate-500">No hay grupos dentro de esta responsabilidad.</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>
</x-contenedor-aplicacion>
