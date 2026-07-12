<x-contenedor-aplicacion
    title="{{ $pagina['titulo'] }} | Administración de proyectos"
    :active="$active"
    :navegacion="$navegacion"
    :role-name="$roleName"
>
    <section class="flex flex-col gap-4 border-b border-slate-200 pb-5 xl:flex-row xl:items-end xl:justify-between">
        <div class="min-w-0">
            <p class="text-sm font-semibold uppercase tracking-[0.18em] text-[#21A366]">Módulo</p>
            <h2 class="mt-2 text-2xl font-bold text-[#0D376D]">{{ $pagina['titulo'] }}</h2>
            <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">{{ $pagina['subtitulo'] }}</p>
        </div>

        <div class="flex flex-wrap gap-2">
            @foreach ($pagina['acciones'] as $accion)
                <button type="button" class="rounded-md border border-[#15529A] bg-white px-3 py-2 text-sm font-semibold text-[#15529A] transition hover:bg-[#EAF2FB]">
                    {{ $accion }}
                </button>
            @endforeach
        </div>
    </section>

    <section class="mt-5 grid gap-3 md:grid-cols-3">
        @foreach ($pagina['metricas'] as $metrica)
            <article class="rounded-md border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">{{ $metrica['label'] }}</p>
                <p class="mt-2 text-2xl font-bold text-[#0D376D]">{{ $metrica['value'] }}</p>
            </article>
        @endforeach
    </section>

    <section class="mt-5 grid gap-5 xl:grid-cols-[minmax(330px,0.8fr)_minmax(0,1.2fr)]">
        <article class="rounded-md border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4">
                <h3 class="font-bold text-slate-900">{{ $pagina['formulario']['titulo'] }}</h3>
            </div>

            <form class="space-y-4 p-5">
                @foreach ($pagina['formulario']['campos'] as $campo)
                    <div>
                        @if ($campo['tipo'] === 'checkbox')
                            <label class="flex items-center gap-3 rounded-md border border-slate-200 bg-slate-50 px-3 py-3 text-sm font-semibold text-slate-700">
                                <input type="checkbox" class="h-4 w-4 rounded border-slate-300 text-[#15529A] focus:ring-[#15529A]" @checked(($campo['valor'] ?? null) === '1')>
                                {{ $campo['label'] }}
                            </label>
                        @else
                            <label class="block text-sm font-semibold text-slate-700">{{ $campo['label'] }}</label>

                            @if ($campo['tipo'] === 'select')
                                <select class="mt-2 block w-full rounded-md border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 outline-none transition focus:border-[#15529A] focus:ring-2 focus:ring-[#15529A]/20">
                                    @foreach ($campo['opciones'] as $opcion)
                                        <option>{{ $opcion }}</option>
                                    @endforeach
                                </select>
                            @elseif ($campo['tipo'] === 'textarea')
                                <textarea rows="5" class="mt-2 block w-full resize-y rounded-md border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 outline-none transition focus:border-[#15529A] focus:ring-2 focus:ring-[#15529A]/20">{{ $campo['valor'] ?? '' }}</textarea>
                            @else
                                <input
                                    type="{{ $campo['tipo'] }}"
                                    value="{{ $campo['valor'] ?? '' }}"
                                    class="mt-2 block w-full rounded-md border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 outline-none transition file:mr-3 file:rounded-md file:border-0 file:bg-[#EAF7EF] file:px-3 file:py-2 file:text-sm file:font-semibold file:text-[#0F7D47] focus:border-[#15529A] focus:ring-2 focus:ring-[#15529A]/20"
                                >
                            @endif
                        @endif
                    </div>
                @endforeach

                <div class="flex gap-2 pt-2">
                    <button type="button" class="rounded-md bg-[#15529A] px-4 py-2.5 text-sm font-bold text-white transition hover:bg-[#0D376D]">
                        Guardar
                    </button>
                    <button type="button" class="rounded-md border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                        Limpiar
                    </button>
                </div>
            </form>
        </article>

        <article class="min-w-0 rounded-md border border-slate-200 bg-white shadow-sm">
            <div class="flex items-center justify-between gap-3 border-b border-slate-200 px-5 py-4">
                <h3 class="font-bold text-slate-900">{{ $pagina['tabla']['titulo'] }}</h3>
                <input type="search" placeholder="Buscar" class="w-40 rounded-md border border-slate-300 px-3 py-2 text-sm outline-none focus:border-[#15529A] focus:ring-2 focus:ring-[#15529A]/20 sm:w-64">
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-[#0D376D] text-white">
                        <tr>
                            @foreach ($pagina['tabla']['columnas'] as $columna)
                                <th class="whitespace-nowrap px-4 py-3 text-left font-bold">{{ $columna }}</th>
                            @endforeach
                            <th class="whitespace-nowrap px-4 py-3 text-left font-bold">Acción</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 bg-white">
                        @foreach ($pagina['tabla']['filas'] as $fila)
                            <tr class="hover:bg-slate-50">
                                @foreach ($fila as $celda)
                                    <td class="whitespace-nowrap px-4 py-3 text-slate-700">{{ $celda }}</td>
                                @endforeach
                                <td class="whitespace-nowrap px-4 py-3">
                                    <button type="button" class="rounded-md bg-[#EAF7EF] px-3 py-1.5 text-xs font-bold text-[#0F7D47]">
                                        Abrir
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </article>
    </section>
</x-contenedor-aplicacion>
