<x-contenedor-aplicacion title="Proyectos | Administracion de proyectos" active="proyectos" :navegacion="$navegacion" :role-name="$roleName">
    <section class="border-b border-slate-200 pb-5">
        <p class="text-sm font-semibold uppercase tracking-[0.18em] text-[#21A366]">Proyecto integrador</p>
        <h2 class="mt-2 text-2xl font-bold text-[#0D376D]">Proyectos</h2>
        <p class="mt-2 max-w-4xl text-sm leading-6 text-slate-600">
            Asocia equipos con guias, administra asesores/evaluadores del proyecto y registra las asignaturas participantes con su docente responsable.
        </p>
    </section>

    <x-mensajes-formulario />

    <section data-async-region="metricas-proyectos" class="mt-5 grid gap-3 md:grid-cols-3">
        @foreach ($metricasProyectos as $metrica)
            <article class="rounded-md border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">{{ $metrica['label'] }}</p>
                <p class="mt-2 text-2xl font-bold text-[#0D376D]">{{ $metrica['value'] }}</p>
            </article>
        @endforeach
    </section>

    <section data-async-region="acciones-proyectos" class="mt-5 grid gap-5 xl:grid-cols-2">
        <article class="rounded-md border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4">
                <h3 class="font-bold text-slate-900">Asignar docente</h3>
            </div>
            <form method="POST" action="{{ route('proyectos.docentes.guardar') }}" class="space-y-4 p-5" data-async-form data-async-targets="mensajes metricas-proyectos acciones-proyectos listado-proyectos">
                @csrf
                <div>
                    <label class="block text-sm font-semibold text-slate-700">Proyecto</label>
                    <select name="proyecto_id" required class="mt-2 block w-full rounded-md border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-[#15529A] focus:ring-2 focus:ring-[#15529A]/20">
                        @foreach ($proyectos as $proyecto)
                            <option value="{{ $proyecto->id }}">{{ $proyecto->titulo }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700">Docente</label>
                    <select name="docente_id" required class="mt-2 block w-full rounded-md border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-[#15529A] focus:ring-2 focus:ring-[#15529A]/20">
                        @foreach ($docentes as $docente)
                            <option value="{{ $docente->id }}">{{ $docente->matricula }} - {{ $docente->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700">Participacion</label>
                    <select name="tipo_participacion" class="mt-2 block w-full rounded-md border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-[#15529A] focus:ring-2 focus:ring-[#15529A]/20">
                        <option value="asesor">Asesor</option>
                        <option value="evaluador">Evaluador</option>
                        <option value="asesor_evaluador">Asesor y evaluador</option>
                    </select>
                </div>
                <button class="rounded-md bg-[#15529A] px-4 py-2.5 text-sm font-bold text-white transition hover:bg-[#0D376D]">Asignar docente</button>
            </form>
        </article>

        <article class="rounded-md border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4">
                <h3 class="font-bold text-slate-900">Asignatura participante</h3>
            </div>
            <form method="POST" action="{{ route('proyectos.asignaturas.guardar') }}" class="space-y-4 p-5" data-async-form data-async-targets="mensajes metricas-proyectos acciones-proyectos listado-proyectos">
                @csrf
                <div>
                    <label class="block text-sm font-semibold text-slate-700">Proyecto</label>
                    <select name="proyecto_id" required class="mt-2 block w-full rounded-md border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-[#15529A] focus:ring-2 focus:ring-[#15529A]/20">
                        @foreach ($proyectos as $proyecto)
                            <option value="{{ $proyecto->id }}">{{ $proyecto->titulo }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700">Asignatura</label>
                    <select name="asignatura_id" required class="mt-2 block w-full rounded-md border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-[#15529A] focus:ring-2 focus:ring-[#15529A]/20">
                        @foreach ($asignaturas as $asignatura)
                            <option value="{{ $asignatura->id }}">{{ $asignatura->clave }} - {{ $asignatura->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700">Docente responsable</label>
                    <select name="docente_id" class="mt-2 block w-full rounded-md border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-[#15529A] focus:ring-2 focus:ring-[#15529A]/20">
                        <option value="">Sin docente definido</option>
                        @foreach ($docentes as $docente)
                            <option value="{{ $docente->id }}">{{ $docente->matricula }} - {{ $docente->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <label class="flex items-center gap-3 rounded-md border border-slate-200 bg-slate-50 px-3 py-3 text-sm font-semibold text-slate-700">
                    <input name="participa_evaluacion" value="1" type="checkbox" checked class="h-4 w-4 rounded border-slate-300 text-[#15529A] focus:ring-[#15529A]">
                    Participa en evaluacion
                </label>
                <button class="rounded-md bg-[#15529A] px-4 py-2.5 text-sm font-bold text-white transition hover:bg-[#0D376D]">Vincular asignatura</button>
            </form>
        </article>

        <article class="rounded-md border border-slate-200 bg-white shadow-sm xl:col-span-2">
            <div class="border-b border-slate-200 px-5 py-4">
                <h3 class="font-bold text-slate-900">Crear proyecto</h3>
            </div>
            <form method="POST" action="{{ route('proyectos.guardar') }}" class="grid gap-4 p-5 lg:grid-cols-2" data-async-form data-async-targets="mensajes metricas-proyectos acciones-proyectos listado-proyectos">
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
                    <label class="block text-sm font-semibold text-slate-700">Guia integradora</label>
                    <select name="guia_integradora_id" required class="mt-2 block w-full rounded-md border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-[#15529A] focus:ring-2 focus:ring-[#15529A]/20">
                        @foreach ($guias as $guia)
                            <option value="{{ $guia->id }}">{{ $guia->nombre }} v{{ $guia->version }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700">Titulo del proyecto</label>
                    <input name="titulo" value="{{ old('titulo') }}" required class="mt-2 block w-full rounded-md border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-[#15529A] focus:ring-2 focus:ring-[#15529A]/20">
                </div>
                <div class="lg:row-span-2">
                    <label class="block text-sm font-semibold text-slate-700">Descripcion</label>
                    <textarea name="descripcion" rows="6" class="mt-2 block h-[168px] w-full resize-y rounded-md border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-[#15529A] focus:ring-2 focus:ring-[#15529A]/20">{{ old('descripcion') }}</textarea>
                </div>
                <div class="flex items-end">
                    <button class="rounded-md bg-[#15529A] px-4 py-2.5 text-sm font-bold text-white transition hover:bg-[#0D376D]">Guardar proyecto</button>
                </div>
            </form>
        </article>
    </section>

    <section data-async-region="listado-proyectos" class="mt-5 space-y-5">
        @foreach ($proyectos as $proyecto)
            <article class="rounded-md border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-5 py-4">
                    <div class="flex flex-col gap-2 lg:flex-row lg:items-start lg:justify-between">
                        <div>
                            <h3 class="font-bold text-slate-900">{{ $proyecto->titulo }}</h3>
                            <p class="mt-1 text-sm text-slate-500">{{ $proyecto->equipo->nombre }} - {{ $proyecto->equipo->grupoAcademico->carrera->clave }} {{ $proyecto->equipo->grupoAcademico->nombre }} - {{ $proyecto->guiaIntegradora->nombre }}</p>
                        </div>
                        <span class="rounded-md bg-slate-100 px-3 py-1 text-xs font-bold uppercase tracking-[0.12em] text-slate-600">{{ str_replace('_', ' ', $proyecto->estado) }}</span>
                    </div>
                </div>
                <div class="grid gap-5 p-5 xl:grid-cols-3">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.16em] text-[#21A366]">Asesores del equipo</p>
                        <div class="mt-3 space-y-2 text-sm text-slate-600">
                            @forelse ($proyecto->equipo->asesores as $asesor)
                                <p>{{ $asesor->nombre }}{{ $asesor->pivot->principal ? ' - Principal' : '' }}</p>
                            @empty
                                <p>Sin asesores asignados al equipo.</p>
                            @endforelse
                        </div>
                    </div>
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.16em] text-[#21A366]">Docentes del proyecto</p>
                        <div class="mt-3 divide-y divide-slate-200">
                            @forelse ($proyecto->docentes as $docente)
                                <div class="flex items-center justify-between gap-3 py-2 text-sm">
                                    <p class="text-slate-700">{{ $docente->nombre }} - {{ str_replace('_', ' ', $docente->pivot->tipo_participacion) }}</p>
                                    <form method="POST" action="{{ route('proyectos.docentes.quitar') }}" data-async-form data-async-targets="mensajes metricas-proyectos acciones-proyectos listado-proyectos">
                                        @csrf
                                        @method('DELETE')
                                        <input type="hidden" name="proyecto_id" value="{{ $proyecto->id }}">
                                        <input type="hidden" name="docente_id" value="{{ $docente->id }}">
                                        <button class="rounded-md border border-red-200 px-3 py-1.5 text-xs font-bold text-red-700 transition hover:bg-red-50">Retirar</button>
                                    </form>
                                </div>
                            @empty
                                <p class="py-2 text-sm text-slate-500">Sin docentes asignados.</p>
                            @endforelse
                        </div>
                    </div>
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.16em] text-[#21A366]">Asignaturas participantes</p>
                        <div class="mt-3 space-y-2 text-sm text-slate-600">
                            @forelse ($proyecto->asignaturas as $asignatura)
                                @php
                                    $docenteAsignatura = $docentes->firstWhere('id', $asignatura->pivot->docente_id);
                                @endphp
                                <p><span class="font-semibold text-slate-800">{{ $asignatura->clave }}</span> {{ $asignatura->nombre }}{{ $docenteAsignatura ? ' - '.$docenteAsignatura->nombre : '' }}</p>
                            @empty
                                <p>Sin asignaturas vinculadas.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </article>
        @endforeach
    </section>
</x-contenedor-aplicacion>
