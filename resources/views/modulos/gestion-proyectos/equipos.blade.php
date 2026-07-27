<x-contenedor-aplicacion title="Equipos | Administracion de proyectos" active="equipos" :navegacion="$navegacion" :role-name="$roleName">
    <section class="border-b border-slate-200 pb-5">
        <p class="text-sm font-semibold uppercase tracking-[0.18em] text-[#21A366]">Trabajo colaborativo</p>
        <h2 class="mt-2 text-2xl font-bold text-[#0D376D]">Equipos de trabajo</h2>
        <p class="mt-2 max-w-4xl text-sm leading-6 text-slate-600">El lider de proyecto organiza a los alumnos de sus grupos y define el numero, nombre y contexto de cada equipo.</p>
    </section>

    <x-mensajes-formulario />

    <section data-async-region="metricas-equipos" class="mt-5 grid gap-3 md:grid-cols-3">
        @foreach ($metricasEquipos as $metrica)
            <article class="rounded-md border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">{{ $metrica['label'] }}</p>
                <p class="mt-2 text-2xl font-bold text-[#0D376D]">{{ $metrica['value'] }}</p>
            </article>
        @endforeach
    </section>

    <section data-async-region="acciones-equipos" class="mt-5 grid gap-5 xl:grid-cols-2">
        <article class="rounded-md border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4">
                <h3 class="font-bold text-slate-900">Agregar alumno</h3>
            </div>
            <form method="POST" action="{{ route('equipos.alumnos.guardar') }}" class="space-y-4 p-5" data-async-form data-async-targets="mensajes metricas-equipos acciones-equipos listado-equipos">
                @csrf
                <div>
                    <label class="block text-sm font-semibold text-slate-700">Equipo</label>
                    <select name="equipo_id" required class="mt-2 block w-full rounded-md border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-[#15529A] focus:ring-2 focus:ring-[#15529A]/20">
                        @foreach ($equipos as $equipo)
                            <option value="{{ $equipo->id }}">{{ $equipo->nombre }} - {{ $equipo->grupoAcademico->carrera->clave }} {{ $equipo->grupoAcademico->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700">Alumno</label>
                    <select name="estudiante_id" required class="mt-2 block w-full rounded-md border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-[#15529A] focus:ring-2 focus:ring-[#15529A]/20">
                        @foreach ($estudiantes as $estudiante)
                            <option value="{{ $estudiante->id }}">{{ $estudiante->matricula }} - {{ $estudiante->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <button class="rounded-md bg-[#15529A] px-4 py-2.5 text-sm font-bold text-white transition hover:bg-[#0D376D]">Agregar alumno</button>
            </form>
        </article>

        <article class="rounded-md border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4">
                <h3 class="font-bold text-slate-900">Asignar asesor</h3>
            </div>
            <form method="POST" action="{{ route('equipos.asesores.guardar') }}" class="space-y-4 p-5" data-async-form data-async-targets="mensajes metricas-equipos acciones-equipos listado-equipos">
                @csrf
                <div>
                    <label class="block text-sm font-semibold text-slate-700">Equipo</label>
                    <select name="equipo_id" required class="mt-2 block w-full rounded-md border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-[#15529A] focus:ring-2 focus:ring-[#15529A]/20">
                        @foreach ($equipos as $equipo)
                            <option value="{{ $equipo->id }}">{{ $equipo->nombre }} - {{ $equipo->grupoAcademico->carrera->clave }} {{ $equipo->grupoAcademico->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700">Docente / asesor</label>
                    <select name="asesor_id" required class="mt-2 block w-full rounded-md border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-[#15529A] focus:ring-2 focus:ring-[#15529A]/20">
                        @foreach ($docentes as $docente)
                            <option value="{{ $docente->id }}">{{ $docente->matricula }} - {{ $docente->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <label class="flex items-center gap-3 rounded-md border border-slate-200 bg-slate-50 px-3 py-3 text-sm font-semibold text-slate-700">
                    <input name="principal" value="1" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-[#15529A] focus:ring-[#15529A]">
                    Asesor principal
                </label>
                <button class="rounded-md bg-[#15529A] px-4 py-2.5 text-sm font-bold text-white transition hover:bg-[#0D376D]">Asignar asesor</button>
            </form>
        </article>

        <article class="rounded-md border border-slate-200 bg-white shadow-sm xl:col-span-2">
            <div class="border-b border-slate-200 px-5 py-4">
                <h3 class="font-bold text-slate-900">Crear o actualizar equipo</h3>
            </div>
            <form method="POST" action="{{ route('equipos.guardar') }}" class="grid gap-4 p-5 lg:grid-cols-2" data-async-form data-async-targets="mensajes metricas-equipos acciones-equipos listado-equipos">
                @csrf
                <div>
                    <label class="block text-sm font-semibold text-slate-700">Grupo</label>
                    <select name="grupo_academico_id" required class="mt-2 block w-full rounded-md border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-[#15529A] focus:ring-2 focus:ring-[#15529A]/20">
                        @foreach ($grupos as $grupo)
                            <option value="{{ $grupo->id }}">{{ $grupo->carrera->clave }} - {{ $grupo->grado }}{{ $grupo->grupo }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700">Nombre del equipo</label>
                    <input name="nombre" value="{{ old('nombre') }}" required class="mt-2 block w-full rounded-md border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-[#15529A] focus:ring-2 focus:ring-[#15529A]/20" placeholder="Equipo 1">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700">Numero de equipo</label>
                    <input name="numero" value="{{ old('numero') }}" type="number" min="1" required class="mt-2 block w-full rounded-md border border-slate-300 px-3 py-2.5 text-sm">
                </div>
                <div class="lg:row-span-2">
                    <label class="block text-sm font-semibold text-slate-700">Contexto del proyecto</label>
                    <textarea name="contexto_proyecto" rows="6" required class="mt-2 block h-[168px] w-full resize-y rounded-md border border-slate-300 px-3 py-2.5 text-sm" placeholder="Problema, necesidad y alcance inicial">{{ old('contexto_proyecto') }}</textarea>
                </div>
                <div class="flex items-end">
                    <button class="rounded-md bg-[#15529A] px-4 py-2.5 text-sm font-bold text-white transition hover:bg-[#0D376D]">Guardar equipo</button>
                </div>
            </form>
        </article>
    </section>

    <section data-async-region="listado-equipos" class="mt-5 space-y-5">
        @foreach ($equipos as $equipo)
            <article class="rounded-md border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-5 py-4">
                    <div class="flex flex-col gap-2 lg:flex-row lg:items-start lg:justify-between">
                        <div>
                            <h3 class="font-bold text-slate-900">{{ $equipo->nombre }}</h3>
                            <p class="mt-1 text-sm text-slate-500">Equipo {{ $equipo->numero ?? '-' }} - {{ $equipo->grupoAcademico->carrera->clave }} - {{ $equipo->grupoAcademico->nombre }} - Lider docente: {{ $equipo->grupoAcademico->liderProyecto?->nombre ?? 'Sin asignar' }}</p>
                            <p class="mt-2 text-sm text-slate-600">{{ $equipo->contexto_proyecto ?? 'Sin contexto definido.' }}</p>
                        </div>
                        <span class="rounded-md bg-slate-100 px-3 py-1 text-xs font-bold uppercase tracking-[0.12em] text-slate-600">{{ $equipo->estado }}</span>
                    </div>
                </div>
                <div class="grid gap-5 p-5 xl:grid-cols-2">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.16em] text-[#21A366]">Integrantes</p>
                        <div class="mt-3 overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-200 text-sm">
                                <tbody class="divide-y divide-slate-200">
                                    @forelse ($equipo->integrantes as $integrante)
                                        <tr>
                                            <td class="py-2 pr-3 font-semibold text-slate-800">{{ $integrante->matricula }}</td>
                                            <td class="py-2 pr-3 text-slate-600">{{ $integrante->nombre }}</td>
                                            <td class="py-2 text-right">
                                                <form method="POST" action="{{ route('equipos.alumnos.quitar') }}" data-async-form data-async-targets="mensajes metricas-equipos acciones-equipos listado-equipos">
                                                    @csrf
                                                    @method('DELETE')
                                                    <input type="hidden" name="equipo_id" value="{{ $equipo->id }}">
                                                    <input type="hidden" name="estudiante_id" value="{{ $integrante->id }}">
                                                    <button class="rounded-md border border-red-200 px-3 py-1.5 text-xs font-bold text-red-700 transition hover:bg-red-50">Retirar</button>
                                                </form>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td class="py-3 text-slate-500">Sin integrantes asignados.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.16em] text-[#21A366]">Asesores</p>
                        <div class="mt-3 overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-200 text-sm">
                                <tbody class="divide-y divide-slate-200">
                                    @forelse ($equipo->asesores as $asesor)
                                        <tr>
                                            <td class="py-2 pr-3 font-semibold text-slate-800">{{ $asesor->matricula }}</td>
                                            <td class="py-2 pr-3 text-slate-600">{{ $asesor->nombre }}{{ $asesor->pivot->principal ? ' - Principal' : '' }}</td>
                                            <td class="py-2 text-right">
                                                <form method="POST" action="{{ route('equipos.asesores.quitar') }}" data-async-form data-async-targets="mensajes metricas-equipos acciones-equipos listado-equipos">
                                                    @csrf
                                                    @method('DELETE')
                                                    <input type="hidden" name="equipo_id" value="{{ $equipo->id }}">
                                                    <input type="hidden" name="asesor_id" value="{{ $asesor->id }}">
                                                    <button class="rounded-md border border-red-200 px-3 py-1.5 text-xs font-bold text-red-700 transition hover:bg-red-50">Retirar</button>
                                                </form>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td class="py-3 text-slate-500">Sin asesores asignados.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </article>
        @endforeach
    </section>
</x-contenedor-aplicacion>
