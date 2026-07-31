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
                <p class="mt-1 text-sm text-slate-500">Define las fechas y el estado inicial del ciclo.</p>
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
            <label class="text-sm font-semibold text-slate-700">Estado
                <select name="estado" class="mt-2 block w-full rounded-md border-slate-300 text-sm">
                    <option value="borrador" @selected(old('estado') === 'borrador')>Borrador</option>
                    <option value="activo" @selected(old('estado') === 'activo')>Activo</option>
                    <option value="cerrado" @selected(old('estado') === 'cerrado')>Cerrado</option>
                </select>
            </label>
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
                    <a href="{{ route('modulos.show', 'carreras-grupos') }}?periodo_id={{ $periodo->id }}" class="text-sm font-bold text-[#15529A] hover:underline">Abrir estructura →</a>
                </article>
            @empty
                <p class="px-5 py-10 text-center text-sm text-slate-500">Todavía no hay periodos registrados.</p>
            @endforelse
        </div>
    </section>
</x-contenedor-aplicacion>
