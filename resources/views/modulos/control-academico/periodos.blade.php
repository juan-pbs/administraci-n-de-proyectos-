<x-contenedor-aplicacion title="Periodos | Administración de proyectos" active="periodos" :navegacion="$navegacion" :role-name="$roleName">
    <section class="border-b border-slate-200 pb-5">
        <p class="text-sm font-semibold uppercase tracking-[0.18em] text-[#21A366]">Control académico</p>
        <h2 class="mt-2 text-2xl font-bold text-[#0D376D]">Periodos académicos</h2>
        <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">Los periodos organizan carreras, grupos, guías, equipos, entregas y reportes.</p>
    </section>

    <x-mensajes-formulario />

    <section class="mt-5 grid gap-3 md:grid-cols-3">
        @foreach ($metricasPeriodos as $metrica)
            <article class="rounded-md border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">{{ $metrica['label'] }}</p>
                <p class="mt-2 text-2xl font-bold text-[#0D376D]">{{ $metrica['value'] }}</p>
            </article>
        @endforeach
    </section>

    <section class="mt-5 grid gap-5 xl:grid-cols-[360px_minmax(0,1fr)]">
        <article class="rounded-md border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4">
                <h3 class="font-bold text-slate-900">Nuevo periodo</h3>
            </div>
            <form method="POST" action="{{ route('periodos.guardar') }}" class="space-y-4 p-5">
                @csrf
                <div>
                    <label class="block text-sm font-semibold text-slate-700">Nombre</label>
                    <input name="nombre" value="{{ old('nombre') }}" required class="mt-2 block w-full rounded-md border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-[#15529A] focus:ring-2 focus:ring-[#15529A]/20" placeholder="Septiembre - Noviembre 2026">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700">Inicio</label>
                        <input name="fecha_inicio" type="date" value="{{ old('fecha_inicio') }}" required class="mt-2 block w-full rounded-md border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-[#15529A] focus:ring-2 focus:ring-[#15529A]/20">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700">Cierre</label>
                        <input name="fecha_fin" type="date" value="{{ old('fecha_fin') }}" required class="mt-2 block w-full rounded-md border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-[#15529A] focus:ring-2 focus:ring-[#15529A]/20">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700">Estado</label>
                    <select name="estado" class="mt-2 block w-full rounded-md border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-[#15529A] focus:ring-2 focus:ring-[#15529A]/20">
                        <option value="borrador">Borrador</option>
                        <option value="activo">Activo</option>
                        <option value="cerrado">Cerrado</option>
                    </select>
                </div>
                <button class="rounded-md bg-[#15529A] px-4 py-2.5 text-sm font-bold text-white transition hover:bg-[#0D376D]">Guardar periodo</button>
            </form>
        </article>

        <article class="min-w-0 rounded-md border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4">
                <h3 class="font-bold text-slate-900">Periodos registrados</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-[#0D376D] text-white">
                        <tr>
                            <th class="px-4 py-3 text-left">Periodo</th>
                            <th class="px-4 py-3 text-left">Inicio</th>
                            <th class="px-4 py-3 text-left">Cierre</th>
                            <th class="px-4 py-3 text-left">Grupos</th>
                            <th class="px-4 py-3 text-left">Estado</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @foreach ($periodos as $periodo)
                            <tr>
                                <td class="px-4 py-3 font-semibold text-slate-800">{{ $periodo->nombre }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $periodo->fecha_inicio }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $periodo->fecha_fin }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $periodo->grupos_count }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ ucfirst($periodo->estado) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </article>
    </section>
</x-contenedor-aplicacion>
