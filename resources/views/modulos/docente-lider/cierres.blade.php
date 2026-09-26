<x-contenedor-aplicacion title="Cierres y prórrogas" active="cierres" :navegacion="$navegacion" :role-name="$roleName">
    <h2 class="text-2xl font-bold text-[#0D376D]">Cierres y prórrogas</h2>
    <p class="mt-2 text-sm text-slate-600">Al terminar el periodo se revisan los PDF finales de tus equipos. Aquí puedes resolver los pendientes y consultar tus decisiones.</p>
    <x-mensajes-formulario />
    <a href="{{ route('docente-lider.estado-guias') }}" class="mt-4 inline-block font-bold text-[#155AA3] underline">Consultar el estado de todas las guías de mis equipos</a>
    <div class="mt-5 space-y-3">
        @forelse($cierres as $cierre)
        <article class="rounded-lg border bg-white p-5">
            <div class="flex flex-wrap justify-between gap-3"><div><h3 class="font-bold">{{ $cierre->proyecto->titulo }}</h3><p class="mt-1 text-sm text-slate-500">{{ $cierre->proyecto->equipo->nombre }} · {{ $cierre->proyecto->equipo->grupoAcademico->grado }}{{ $cierre->proyecto->equipo->grupoAcademico->grupo }}</p></div><span class="rounded bg-slate-100 px-3 py-1 text-sm">{{ ucfirst($cierre->estado) }}</span></div>
            <p class="mt-3 text-sm">{{ count($cierre->pendientes ?? []) }} pendientes registrados al revisar el cierre.</p>
            @if($cierre->estado === 'prorroga')<p class="mt-2 text-sm text-amber-800">Fecha de prórroga: {{ $cierre->prorrogas->where('ronda', $cierre->ronda)->max('fecha_limite')?->format('d/m/Y H:i') }} ({{ config('app.timezone') }})</p>@endif
            <a href="{{ route('docente-lider.cierres.mostrar', $cierre->proyecto) }}" class="mt-3 inline-block font-bold text-[#155AA3] underline">Ver pendientes y decisión</a>
        </article>
        @empty<p class="rounded-lg border bg-white p-6 text-sm text-slate-500">No hay cierres pendientes registrados para tus equipos.</p>@endforelse
    </div>
    <div class="mt-5">{{ $cierres->links() }}</div>
</x-contenedor-aplicacion>
