<x-contenedor-aplicacion
    title="Mis asignaciones | Administración de proyectos"
    active="asignaciones-docente"
    :navegacion="$navegacion"
    :role-name="$roleName"
>
    <section class="border-b border-slate-200 pb-5">
        <p class="text-sm font-semibold uppercase tracking-[0.18em] text-[#21A366]">Alcance de evaluación</p>
        <div class="mt-3 flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <h2 class="text-2xl font-bold text-[#0D376D]">Mis asignaciones</h2>
                <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">Apartados de la guía que Coordinación te asignó para revisar y calificar durante el periodo.</p>
            </div>
            <span class="w-fit rounded-md bg-[#EAF7EF] px-3 py-2 text-sm font-semibold text-[#0F7D47]">{{ $periodo?->nombre ?? 'Sin periodo activo' }}</span>
        </div>
    </section>

    <section class="mt-5 grid gap-3 sm:grid-cols-3">
        <article class="rounded-lg border border-slate-200 bg-white p-4"><p class="text-xs font-bold uppercase tracking-wider text-slate-500">Apartados</p><p class="mt-2 text-2xl font-bold text-[#0D376D]">{{ $firmas->count() }}</p></article>
        <article class="rounded-lg border border-slate-200 bg-white p-4"><p class="text-xs font-bold uppercase tracking-wider text-slate-500">Materias</p><p class="mt-2 text-2xl font-bold text-[#0D376D]">{{ $firmas->pluck('asignatura_id')->filter()->unique()->count() }}</p></article>
        <article class="rounded-lg border border-slate-200 bg-white p-4"><p class="text-xs font-bold uppercase tracking-wider text-slate-500">Ponderación asignada</p><p class="mt-2 text-2xl font-bold text-[#0D376D]">{{ number_format($firmas->sum(fn($firma) => $firma->apartadoGuia?->ponderacion ?? 0), 0) }}%</p></article>
    </section>

    <section class="mt-5 overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-5 py-4">
            <h3 class="font-bold text-slate-900">Apartados asignados</h3>
            <p class="mt-1 text-sm text-slate-500">Esta lista es informativa; las asignaciones solo las modifica Coordinación.</p>
        </div>
        <div class="divide-y divide-slate-200">
            @forelse($firmas as $firma)
                @php($apartado = $firma->apartadoGuia)
                <article class="grid gap-4 px-5 py-4 lg:grid-cols-[80px_minmax(0,1fr)_220px_140px] lg:items-center">
                    <div><p class="text-xs font-semibold text-slate-500">Orden</p><p class="mt-1 text-xl font-bold text-[#0D376D]">{{ $apartado?->orden }}</p></div>
                    <div>
                        <p class="font-bold text-slate-900">{{ $apartado?->titulo }}</p>
                        <p class="mt-1 text-sm text-slate-500">{{ $apartado?->guiaIntegradora?->nombre }} · {{ $firma->asignatura?->nombre ?? $firma->etiqueta }}</p>
                        @if($apartado?->descripcion)<p class="mt-2 text-sm leading-5 text-slate-600">{{ $apartado->descripcion }}</p>@endif
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-slate-500">Evidencias a revisar</p>
                        <div class="mt-2 flex flex-wrap gap-2">
                            @if($apartado?->requiere_documento)<span class="rounded-full bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-700">Documento</span>@endif
                            @if($apartado?->requiere_codigo)<span class="rounded-full bg-violet-50 px-2.5 py-1 text-xs font-semibold text-violet-700">Código</span>@endif
                            @if(!$apartado?->requiere_documento && !$apartado?->requiere_codigo)<span class="text-sm text-slate-400">Sin archivo específico</span>@endif
                        </div>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-slate-500">Valor / límite</p>
                        <p class="mt-1 font-bold text-slate-800">{{ number_format($apartado?->ponderacion ?? 0, 0) }}%</p>
                        <p class="mt-1 text-xs text-slate-500">{{ $apartado?->fecha_limite?->format('d/m/Y H:i') ?? 'Sin fecha límite' }}</p>
                    </div>
                </article>
            @empty
                <div class="px-5 py-12 text-center">
                    <p class="font-semibold text-slate-700">Aún no tienes apartados asignados.</p>
                    <p class="mt-1 text-sm text-slate-500">Cuando Coordinación te asigne una parte de la guía aparecerá aquí.</p>
                </div>
            @endforelse
        </div>
    </section>
</x-contenedor-aplicacion>
