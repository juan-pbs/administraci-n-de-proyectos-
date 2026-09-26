<x-contenedor-aplicacion title="Periodos | Administración de proyectos" active="periodos" :navegacion="$navegacion" :role-name="$roleName">
    <section class="border-b border-slate-200 pb-5">
        <p class="text-sm font-semibold uppercase tracking-[0.18em] text-[#21A366]">Control académico</p>
        <h2 class="mt-2 text-2xl font-bold text-[#0D376D]">Periodos académicos</h2>
        <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">Cada periodo inicia una operación independiente: se cargan nuevos alumnos, grupos, asignaciones y guías; únicamente se conserva el catálogo de docentes.</p>
    </section>

    <x-mensajes-formulario />

    <section class="mt-5 grid gap-3 md:grid-cols-3">
        @foreach ($metricasPeriodos as $metrica)
            <article class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">{{ $metrica['label'] }}</p>
                <p class="mt-2 text-2xl font-bold text-[#0D376D]">{{ $metrica['value'] }}</p>
            </article>
        @endforeach
    </section>

    <details class="group mt-5 rounded-lg border border-slate-200 bg-white shadow-sm" @if($errors->any()) open @endif>
        <summary class="flex cursor-pointer list-none items-center justify-between px-5 py-4">
            <div>
                <h3 class="font-bold text-slate-900">Registrar nuevo periodo</h3>
                <p class="mt-1 text-sm text-slate-500">Define las fechas. El nuevo ciclo se guarda como borrador.</p>
            </div>
            <span class="rounded-md bg-[#15529A] px-3 py-2 text-sm font-bold text-white group-open:bg-[#0D376D]">Nuevo periodo</span>
        </summary>
        <form method="POST" action="{{ route('periodos.guardar') }}" class="grid gap-4 border-t border-slate-200 p-5 md:grid-cols-4">
            @csrf
            <label class="text-sm font-semibold text-slate-700 md:col-span-2">Nombre
                <input name="nombre" value="{{ old('nombre') }}" required class="mt-2 block w-full rounded-md border-slate-300 text-sm" placeholder="Septiembre - Diciembre 2026">
            </label>
            <label class="text-sm font-semibold text-slate-700">Inicio
                <input name="fecha_inicio" type="date" value="{{ old('fecha_inicio') }}" required class="mt-2 block w-full rounded-md border-slate-300 text-sm">
            </label>
            <label class="text-sm font-semibold text-slate-700">Cierre
                <input name="fecha_fin" type="date" value="{{ old('fecha_fin') }}" required class="mt-2 block w-full rounded-md border-slate-300 text-sm">
            </label>
            <p class="text-sm text-slate-500">Estado inicial: <strong>Borrador</strong>. Actívalo desde el historial cuando hayas preparado su estructura.</p>
            <div class="flex items-end md:col-span-3">
                <button class="rounded-md bg-[#15529A] px-4 py-2.5 text-sm font-bold text-white hover:bg-[#0D376D]">Guardar periodo</button>
            </div>
        </form>
    </details>

    <section class="mt-5 overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-5 py-4">
            <h3 class="font-bold text-slate-900">Historial de periodos</h3>
            <p class="mt-1 text-sm text-slate-500">Los periodos cerrados se conservan para consulta, sin heredar su operación al siguiente.</p>
        </div>
        <div class="divide-y divide-slate-200">
            @forelse($periodos as $periodo)
                @php
                    $estiloEstado = match($periodo->estado) {
                        'activo' => 'bg-[#EAF7EF] text-[#0F7D47]',
                        'cerrado' => 'bg-slate-100 text-slate-600',
                        default => 'bg-amber-50 text-amber-700',
                    };
                @endphp
                <article class="grid gap-4 px-5 py-4 sm:grid-cols-[minmax(0,1fr)_auto_auto] sm:items-center">
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <h4 class="font-bold text-[#0D376D]">{{ $periodo->nombre }}</h4>
                            <span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $estiloEstado }}">{{ ucfirst($periodo->estado) }}</span>
                        </div>
                        <p class="mt-1 text-sm text-slate-500">{{ \Illuminate\Support\Carbon::parse($periodo->fecha_inicio)->format('d/m/Y') }} — {{ \Illuminate\Support\Carbon::parse($periodo->fecha_fin)->format('d/m/Y') }}</p>
                    </div>
                    <div class="rounded-md bg-slate-50 px-4 py-2 text-center">
                        <p class="text-xs font-semibold uppercase text-slate-500">Grupos</p>
                        <p class="font-bold text-slate-900">{{ $periodo->grupos_count }}</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-3">
                        <a href="{{ route('modulos.show', 'carreras-grupos') }}?periodo_id={{ $periodo->id }}" class="text-sm font-bold text-[#15529A] hover:underline">Abrir estructura →</a>
                        @if($periodo->estado === 'borrador')
                            <form method="POST" action="{{ route('periodos.activar', $periodo) }}">@csrf @method('PATCH')<button @disabled($periodos->contains('estado', 'activo')) class="rounded-md bg-[#15529A] px-3 py-2 text-sm font-bold text-white disabled:opacity-40">Activar periodo</button></form>
                            @if($periodos->contains('estado', 'activo'))<p class="text-xs text-slate-500">Primero cierra el periodo activo.</p>@endif
                        @endif
                    </div>
                    @if($periodo->estado === 'activo')
                        <div class="sm:col-span-3">
                            @if(\Illuminate\Support\Carbon::parse($periodo->fecha_fin)->endOfDay()->isPast())<p class="mb-3 rounded-md bg-amber-50 p-3 text-sm font-semibold text-amber-800">La fecha de fin ya pasó. Revisa los pendientes y decide cuándo cerrar este periodo.</p>@endif
                            <details class="rounded-md border border-amber-200 bg-amber-50 p-3"><summary class="cursor-pointer text-sm font-bold text-amber-900">Revisar pendientes y cerrar periodo</summary>
                                <p class="mt-3 text-sm text-amber-900">{{ $periodo->proyectos_sin_pdf }} proyectos sin PDF final · {{ $periodo->guias_borrador }} guías en borrador.</p>
                                <div class="mt-2 flex flex-wrap gap-4 text-sm font-bold text-[#15529A]"><a class="underline" href="{{ route('modulos.show', ['modulo' => 'proyectos', 'periodo_proyectos' => $periodo->id]) }}">Revisar proyectos</a><a class="underline" href="{{ route('modulos.show', ['modulo' => 'guias', 'periodo_guias' => $periodo->id, 'estado_guias' => 'borrador']) }}">Revisar guías en borrador</a></div>
                                <p class="mt-2 text-sm text-slate-600">El cierre conserva el historial y cierra las guías publicadas que terminan en este ciclo. Las pendientes no se aprueban ni generan PDF automáticamente.</p>
                                <form method="POST" action="{{ route('periodos.cerrar', $periodo) }}" class="mt-3 space-y-3">@csrf @method('PATCH')<label class="flex items-start gap-2 text-sm"><input type="checkbox" name="confirmar_cierre" value="1" required class="mt-1 rounded">He revisado los pendientes y deseo cerrar el periodo para consulta.</label><button class="rounded-md bg-amber-800 px-3 py-2 text-sm font-bold text-white">Cerrar periodo</button></form>
                            </details>
                        </div>
                    @endif
                </article>
            @empty
                <p class="px-5 py-10 text-center text-sm text-slate-500">Todavía no hay periodos registrados.</p>
            @endforelse
        </div>
    </section>
</x-contenedor-aplicacion>
