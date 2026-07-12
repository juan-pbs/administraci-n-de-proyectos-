<x-contenedor-aplicacion title="Guías | Administración de proyectos" active="guias" :navegacion="$navegacion" :role-name="$roleName">
    <section class="border-b border-slate-200 pb-5">
        <p class="text-sm font-semibold uppercase tracking-[0.18em] text-[#21A366]">Estructura académica</p>
        <h2 class="mt-2 text-2xl font-bold text-[#0D376D]">Guías integradoras</h2>
        <p class="mt-2 max-w-4xl text-sm leading-6 text-slate-600">
            Configura la guía como tabla institucional: competencias, objetivo, contenido por apartado, asignaturas que contribuyen, fechas de entrega y firmas requeridas.
        </p>
    </section>

    <x-mensajes-formulario />

    <section class="mt-5 grid gap-3 md:grid-cols-3">
        @foreach ($metricasGuias as $metrica)
            <article class="rounded-md border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">{{ $metrica['label'] }}</p>
                <p class="mt-2 text-2xl font-bold text-[#0D376D]">{{ $metrica['value'] }}</p>
            </article>
        @endforeach
    </section>

    <section class="mt-5 grid gap-5 xl:grid-cols-2">
        <article class="rounded-md border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4">
                <h3 class="font-bold text-slate-900">Nueva guía</h3>
            </div>
            <form method="POST" action="{{ route('guias.guardar') }}" class="space-y-4 p-5">
                @csrf
                <div class="grid gap-3 md:grid-cols-2">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700">Periodo inicial</label>
                        <select name="periodo_id" required class="mt-2 block w-full rounded-md border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-[#15529A] focus:ring-2 focus:ring-[#15529A]/20">
                            @foreach ($periodos as $periodo)
                                <option value="{{ $periodo->id }}">{{ $periodo->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700">Periodo final</label>
                        <select name="periodo_fin_id" class="mt-2 block w-full rounded-md border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-[#15529A] focus:ring-2 focus:ring-[#15529A]/20">
                            <option value="">Mismo periodo</option>
                            @foreach ($periodos as $periodo)
                                <option value="{{ $periodo->id }}">{{ $periodo->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700">Asignatura principal</label>
                    <select name="asignatura_id" class="mt-2 block w-full rounded-md border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-[#15529A] focus:ring-2 focus:ring-[#15529A]/20">
                        <option value="">Sin asignatura principal</option>
                        @foreach ($asignaturas as $asignatura)
                            <option value="{{ $asignatura->id }}">{{ $asignatura->carrera?->clave }} - {{ $asignatura->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700">Nombre</label>
                    <input name="nombre" value="{{ old('nombre') }}" required class="mt-2 block w-full rounded-md border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-[#15529A] focus:ring-2 focus:ring-[#15529A]/20">
                </div>
                <div class="grid gap-3 md:grid-cols-3">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700">Cuatrimestre</label>
                        <input name="cuatrimestre" value="{{ old('cuatrimestre') }}" placeholder="Tercer cuatrimestre" class="mt-2 block w-full rounded-md border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-[#15529A] focus:ring-2 focus:ring-[#15529A]/20">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700">Versión</label>
                        <input name="version" value="{{ old('version', '1.0') }}" required class="mt-2 block w-full rounded-md border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-[#15529A] focus:ring-2 focus:ring-[#15529A]/20">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700">Estado</label>
                        <select name="estado" class="mt-2 block w-full rounded-md border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-[#15529A] focus:ring-2 focus:ring-[#15529A]/20">
                            <option value="borrador">Borrador</option>
                            <option value="publicada">Publicada</option>
                            <option value="cerrada">Cerrada</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700">Competencias a evaluar</label>
                    <textarea name="competencias_evaluar" rows="3" class="mt-2 block w-full rounded-md border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-[#15529A] focus:ring-2 focus:ring-[#15529A]/20">{{ old('competencias_evaluar') }}</textarea>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700">Objetivo de aprendizaje</label>
                    <textarea name="objetivo_aprendizaje" rows="3" class="mt-2 block w-full rounded-md border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-[#15529A] focus:ring-2 focus:ring-[#15529A]/20">{{ old('objetivo_aprendizaje') }}</textarea>
                </div>
                <button class="rounded-md bg-[#15529A] px-4 py-2.5 text-sm font-bold text-white transition hover:bg-[#0D376D]">Guardar guía</button>
            </form>
        </article>

        <article class="rounded-md border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4">
                <h3 class="font-bold text-slate-900">Nuevo apartado</h3>
            </div>
            <form method="POST" action="{{ route('guias.apartados.guardar') }}" class="space-y-4 p-5">
                @csrf
                <div>
                    <label class="block text-sm font-semibold text-slate-700">Guía</label>
                    <select name="guia_integradora_id" required class="mt-2 block w-full rounded-md border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-[#15529A] focus:ring-2 focus:ring-[#15529A]/20">
                        @foreach ($guias as $guia)
                            <option value="{{ $guia->id }}">{{ $guia->nombre }} v{{ $guia->version }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700">Orden</label>
                        <input name="orden" type="number" min="1" value="{{ old('orden', 1) }}" required class="mt-2 block w-full rounded-md border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-[#15529A] focus:ring-2 focus:ring-[#15529A]/20">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700">Ponderación</label>
                        <input name="ponderacion" type="number" min="0" max="100" step="0.01" value="{{ old('ponderacion', 10) }}" required class="mt-2 block w-full rounded-md border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-[#15529A] focus:ring-2 focus:ring-[#15529A]/20">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700">Título / contenido</label>
                    <input name="titulo" value="{{ old('titulo') }}" required placeholder="Capítulo I - Protocolo del proyecto" class="mt-2 block w-full rounded-md border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-[#15529A] focus:ring-2 focus:ring-[#15529A]/20">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700">Fecha límite</label>
                    <input name="fecha_limite" type="datetime-local" value="{{ old('fecha_limite') }}" class="mt-2 block w-full rounded-md border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-[#15529A] focus:ring-2 focus:ring-[#15529A]/20">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700">Puntos que debe realizar el equipo</label>
                    <textarea name="descripcion" rows="5" class="mt-2 block w-full rounded-md border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-[#15529A] focus:ring-2 focus:ring-[#15529A]/20" placeholder="1. Project Charter&#10;2. Selección de herramienta de comunicación">{{ old('descripcion') }}</textarea>
                </div>
                <div class="grid gap-2 sm:grid-cols-2">
                    <label class="flex items-center gap-3 rounded-md border border-slate-200 bg-slate-50 px-3 py-3 text-sm font-semibold text-slate-700">
                        <input name="requiere_documento" value="1" type="checkbox" checked class="h-4 w-4 rounded border-slate-300 text-[#15529A] focus:ring-[#15529A]">
                        Requiere documento
                    </label>
                    <label class="flex items-center gap-3 rounded-md border border-slate-200 bg-slate-50 px-3 py-3 text-sm font-semibold text-slate-700">
                        <input name="requiere_codigo" value="1" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-[#15529A] focus:ring-[#15529A]">
                        Requiere código
                    </label>
                </div>
                <button class="rounded-md bg-[#15529A] px-4 py-2.5 text-sm font-bold text-white transition hover:bg-[#0D376D]">Guardar apartado</button>
            </form>
        </article>
    </section>

    <section class="mt-5 grid gap-5 xl:grid-cols-2">
        <article class="rounded-md border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4">
                <h3 class="font-bold text-slate-900">Asignatura que contribuye</h3>
            </div>
            <form method="POST" action="{{ route('guias.apartados.asignaturas.guardar') }}" class="space-y-4 p-5">
                @csrf
                <div>
                    <label class="block text-sm font-semibold text-slate-700">Apartado</label>
                    <select name="apartado_guia_id" required class="mt-2 block w-full rounded-md border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-[#15529A] focus:ring-2 focus:ring-[#15529A]/20">
                        @foreach ($apartados as $apartado)
                            <option value="{{ $apartado->id }}">{{ $apartado->guiaIntegradora->nombre }} - {{ $apartado->orden }}. {{ $apartado->titulo }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700">Asignatura</label>
                    <select name="asignatura_id" required class="mt-2 block w-full rounded-md border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-[#15529A] focus:ring-2 focus:ring-[#15529A]/20">
                        @foreach ($asignaturas as $asignatura)
                            <option value="{{ $asignatura->id }}">{{ $asignatura->carrera?->clave }} - {{ $asignatura->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700">Rol de contribución</label>
                    <input name="rol_contribucion" placeholder="Evaluación y mejora para el desarrollo de software" class="mt-2 block w-full rounded-md border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-[#15529A] focus:ring-2 focus:ring-[#15529A]/20">
                </div>
                <label class="flex items-center gap-3 rounded-md border border-slate-200 bg-slate-50 px-3 py-3 text-sm font-semibold text-slate-700">
                    <input name="requiere_firma" value="1" type="checkbox" checked class="h-4 w-4 rounded border-slate-300 text-[#15529A] focus:ring-[#15529A]">
                    Requiere firma
                </label>
                <button class="rounded-md bg-[#15529A] px-4 py-2.5 text-sm font-bold text-white transition hover:bg-[#0D376D]">Vincular asignatura</button>
            </form>
        </article>

        <article class="rounded-md border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4">
                <h3 class="font-bold text-slate-900">Firma requerida</h3>
            </div>
            <form method="POST" action="{{ route('guias.apartados.firmas.guardar') }}" class="space-y-4 p-5">
                @csrf
                <div>
                    <label class="block text-sm font-semibold text-slate-700">Apartado</label>
                    <select name="apartado_guia_id" required class="mt-2 block w-full rounded-md border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-[#15529A] focus:ring-2 focus:ring-[#15529A]/20">
                        @foreach ($apartados as $apartado)
                            <option value="{{ $apartado->id }}">{{ $apartado->guiaIntegradora->nombre }} - {{ $apartado->orden }}. {{ $apartado->titulo }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="grid gap-3 md:grid-cols-2">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700">Orden</label>
                        <input name="orden" type="number" min="1" value="1" required class="mt-2 block w-full rounded-md border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-[#15529A] focus:ring-2 focus:ring-[#15529A]/20">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700">Etiqueta</label>
                        <input name="etiqueta" placeholder="Primer asesor" required class="mt-2 block w-full rounded-md border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-[#15529A] focus:ring-2 focus:ring-[#15529A]/20">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700">Asignatura relacionada</label>
                    <select name="asignatura_id" class="mt-2 block w-full rounded-md border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-[#15529A] focus:ring-2 focus:ring-[#15529A]/20">
                        <option value="">Sin asignatura específica</option>
                        @foreach ($asignaturas as $asignatura)
                            <option value="{{ $asignatura->id }}">{{ $asignatura->carrera?->clave }} - {{ $asignatura->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700">Docente fijo</label>
                    <select name="docente_id" class="mt-2 block w-full rounded-md border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-[#15529A] focus:ring-2 focus:ring-[#15529A]/20">
                        <option value="">Se definirá en el proyecto</option>
                        @foreach ($docentes as $docente)
                            <option value="{{ $docente->id }}">{{ $docente->matricula }} - {{ $docente->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <label class="flex items-center gap-3 rounded-md border border-slate-200 bg-slate-50 px-3 py-3 text-sm font-semibold text-slate-700">
                    <input name="requerida" value="1" type="checkbox" checked class="h-4 w-4 rounded border-slate-300 text-[#15529A] focus:ring-[#15529A]">
                    Firma obligatoria
                </label>
                <button class="rounded-md bg-[#15529A] px-4 py-2.5 text-sm font-bold text-white transition hover:bg-[#0D376D]">Guardar firma</button>
            </form>
        </article>
    </section>

    <section class="mt-5 space-y-5">
        @foreach ($guias as $guia)
            <article class="rounded-md border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-5 py-4">
                    <div class="flex flex-col gap-2 lg:flex-row lg:items-start lg:justify-between">
                        <div>
                            <h3 class="font-bold text-slate-900">{{ $guia->nombre }}</h3>
                            <p class="mt-1 text-sm text-slate-500">
                                {{ $guia->periodo->nombre }}{{ $guia->periodoFin ? ' a '.$guia->periodoFin->nombre : '' }} · {{ $guia->cuatrimestre ?? 'Sin cuatrimestre' }} · v{{ $guia->version }}
                            </p>
                        </div>
                        <span class="rounded-md bg-[#EAF7EF] px-3 py-1 text-xs font-bold uppercase tracking-[0.12em] text-[#0F7D47]">{{ $guia->estado }}</span>
                    </div>
                    @if ($guia->competencias_evaluar || $guia->objetivo_aprendizaje)
                        <div class="mt-4 grid gap-3 lg:grid-cols-2">
                            <div class="rounded-md bg-slate-50 p-3">
                                <p class="text-xs font-bold uppercase tracking-[0.12em] text-slate-500">Competencias a evaluar</p>
                                <p class="mt-1 text-sm leading-6 text-slate-700">{{ $guia->competencias_evaluar ?: '-' }}</p>
                            </div>
                            <div class="rounded-md bg-slate-50 p-3">
                                <p class="text-xs font-bold uppercase tracking-[0.12em] text-slate-500">Objetivo de aprendizaje</p>
                                <p class="mt-1 text-sm leading-6 text-slate-700">{{ $guia->objetivo_aprendizaje ?: '-' }}</p>
                            </div>
                        </div>
                    @endif
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-[#0D376D] text-white">
                            <tr>
                                <th class="px-4 py-3 text-left">Contenido</th>
                                <th class="px-4 py-3 text-left">Asignaturas que contribuyen</th>
                                <th class="px-4 py-3 text-left">Fecha</th>
                                <th class="px-4 py-3 text-left">Firmas</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                            @forelse ($guia->apartados->sortBy('orden') as $apartado)
                                <tr class="align-top">
                                    <td class="px-4 py-3">
                                        <p class="font-bold text-slate-800">{{ $apartado->orden }}. {{ $apartado->titulo }}</p>
                                        <p class="mt-1 whitespace-pre-line text-slate-600">{{ $apartado->descripcion ?: '-' }}</p>
                                    </td>
                                    <td class="px-4 py-3 text-slate-600">
                                        @forelse ($apartado->asignaturasContribuyentes as $asignatura)
                                            <p><span class="font-semibold text-slate-800">{{ $asignatura->clave }}</span> {{ $asignatura->nombre }}{{ $asignatura->pivot->rol_contribucion ? ' - '.$asignatura->pivot->rol_contribucion : '' }}</p>
                                        @empty
                                            -
                                        @endforelse
                                    </td>
                                    <td class="px-4 py-3 font-semibold text-slate-700">{{ $apartado->fecha_limite?->format('d/m/Y') ?? '-' }}</td>
                                    <td class="px-4 py-3 text-slate-600">
                                        @forelse ($apartado->firmas as $firma)
                                            <p>{{ $firma->orden }}. {{ $firma->etiqueta }}{{ $firma->asignatura ? ' - '.$firma->asignatura->nombre : '' }}{{ $firma->docente ? ' / '.$firma->docente->nombre : '' }}</p>
                                        @empty
                                            -
                                        @endforelse
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-6 text-center text-slate-500">Aún no hay apartados configurados.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </article>
        @endforeach
    </section>
</x-contenedor-aplicacion>
