<x-contenedor-aplicacion title="Pendientes del periodo | Administración de proyectos" active="periodos" :navegacion="$navegacion" :role-name="$roleName">
    <section class="border-b border-slate-200 pb-5">
        <a href="{{ route('modulos.show', 'periodos') }}" class="text-sm font-bold text-[#15529A] underline">← Volver a periodos</a>
        <p class="mt-4 text-sm font-semibold uppercase tracking-[0.18em] text-[#21A366]">Revisión antes del cierre</p>
        <h2 class="mt-2 text-2xl font-bold text-[#0D376D]">Pendientes de {{ $periodo->nombre }}</h2>
        <p class="mt-2 text-sm text-slate-600">Consulta los proyectos sin PDF final y las guías en borrador de este periodo. Esta vista no modifica entregas ni aprobaciones.</p>
    </section>

    <div class="mt-5 grid gap-3 sm:grid-cols-2">
        <a href="#proyectos" class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-amber-900 hover:border-amber-400">
            <p class="text-sm font-bold">Proyectos sin PDF final</p>
            <p class="mt-1 text-2xl font-extrabold">{{ $proyectosSinPdf->count() }}</p>
        </a>
        <a href="#guias" class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-amber-900 hover:border-amber-400">
            <p class="text-sm font-bold">Guías en borrador</p>
            <p class="mt-1 text-2xl font-extrabold">{{ $guiasBorrador->count() }}</p>
        </a>
    </div>

    <section id="proyectos" class="mt-6 scroll-mt-4 overflow-hidden rounded-lg border border-slate-200 bg-white">
        <div class="border-b border-slate-200 p-5">
            <h3 class="text-lg font-bold text-[#0D376D]">Proyectos sin PDF final</h3>
            <p class="mt-1 text-sm text-slate-600">Abre el formato final para consultar entregas, firmas y motivos pendientes.</p>
        </div>
        <div class="divide-y divide-slate-200">
            @forelse($proyectosSinPdf as $proyecto)
                <article class="flex flex-wrap items-center justify-between gap-3 px-5 py-4">
                    <div>
                        <p class="font-bold text-slate-900">{{ $proyecto->titulo }}</p>
                        <p class="mt-1 text-sm text-slate-600">{{ $proyecto->equipo->grupoAcademico->carrera?->clave }} · {{ $proyecto->equipo->grupoAcademico->grado }}{{ $proyecto->equipo->grupoAcademico->grupo }} · {{ $proyecto->equipo->nombre }} · {{ $proyecto->guiaIntegradora->nombre }}</p>
                        <p class="mt-1 text-xs text-slate-500">Estado: {{ ucfirst(str_replace('_', ' ', $proyecto->estado)) }}</p>
                    </div>
                    <a href="{{ route('documentos.mostrar', ['proyecto' => $proyecto, 'origen' => 'periodos.pendientes', 'contexto' => ['periodo' => $periodo->id]]) }}" class="rounded-md border border-[#15529A] px-3 py-2 text-sm font-bold text-[#15529A] hover:bg-blue-50">Ver formato y firmas</a>
                </article>
            @empty
                <p class="p-5 text-sm text-slate-600">Todos los proyectos de este periodo tienen un PDF final.</p>
            @endforelse
        </div>
    </section>

    <section id="guias" class="mt-6 scroll-mt-4 overflow-hidden rounded-lg border border-slate-200 bg-white">
        <div class="border-b border-slate-200 p-5">
            <h3 class="text-lg font-bold text-[#0D376D]">Guías en borrador</h3>
            <p class="mt-1 text-sm text-slate-600">Se muestran todas las carreras del periodo. Puedes abrir la guía en su módulo de Coordinación.</p>
        </div>
        <div class="divide-y divide-slate-200">
            @forelse($guiasBorrador as $guia)
                <article class="flex flex-wrap items-center justify-between gap-3 px-5 py-4">
                    <div>
                        <p class="font-bold text-slate-900">{{ $guia->nombre }} · v{{ $guia->version }}</p>
                        <p class="mt-1 text-sm text-slate-600">{{ $guia->asignatura?->carrera?->clave }} · {{ $guia->asignatura?->nombre }}</p>
                    </div>
                    <div class="flex flex-wrap gap-3 text-sm font-bold text-[#15529A]">
                        <a href="{{ route('guias.preview', $guia) }}" class="underline">Vista previa</a>
                        <a href="{{ route('modulos.show', ['modulo' => 'guias', 'periodo_guias' => $periodo->id, 'carrera_guias' => $guia->asignatura?->carrera_id, 'estado_guias' => 'borrador']) }}" class="underline">Abrir en Guías</a>
                    </div>
                </article>
            @empty
                <p class="p-5 text-sm text-slate-600">No hay guías en borrador en este periodo.</p>
            @endforelse
        </div>
    </section>
</x-contenedor-aplicacion>
