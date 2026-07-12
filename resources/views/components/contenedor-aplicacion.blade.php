@props([
    'title' => 'Administración de proyectos',
    'active' => 'dashboard',
    'navegacion' => [],
    'roleName' => 'Estudiante / Equipo',
])

<x-layouts.app title="{{ $title }}">
    <div class="h-screen overflow-hidden bg-slate-100">
        <div class="flex h-full min-h-0">
            <aside class="hidden h-screen w-72 shrink-0 border-r border-slate-200 bg-white lg:block">
                <div class="flex h-full min-h-0 flex-col">
                    <div class="shrink-0 border-b border-slate-200 px-5 py-5">
                        <img src="{{ asset('assets/utvm/utvm-logo-transparente.png') }}" alt="Logo UTVM" class="h-16 w-auto">
                        <p class="mt-3 text-xs font-semibold uppercase tracking-[0.18em] text-[#21A366]">Sistema académico</p>
                        <p class="mt-1 text-sm font-bold text-[#0D376D]">Proyectos integradores</p>
                    </div>

                    <nav class="min-h-0 flex-1 space-y-1 overflow-y-auto px-3 py-4">
                        @foreach ($navegacion as $item)
                            <a
                                href="{{ $item['ruta'] }}"
                                class="flex items-center gap-3 rounded-md px-3 py-2.5 text-sm font-semibold transition {{ $active === $item['clave'] ? 'bg-[#EAF7EF] text-[#0F7D47]' : 'text-slate-600 hover:bg-slate-100 hover:text-[#0D376D]' }}"
                            >
                                <span class="grid h-7 w-7 place-items-center rounded-md border border-slate-200 bg-white text-sm">{{ $item['icono'] }}</span>
                                <span class="truncate">{{ $item['titulo'] }}</span>
                            </a>
                        @endforeach
                    </nav>
                </div>
            </aside>

            <div class="flex min-h-0 min-w-0 flex-1 flex-col">
                <header class="z-20 shrink-0 border-b border-slate-200 bg-white/95 backdrop-blur">
                    <div class="flex items-center justify-between gap-4 px-4 py-3 sm:px-6 lg:px-8">
                        <div class="min-w-0">
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[#21A366]">UTVM</p>
                            <h1 class="truncate text-lg font-bold text-[#0D376D]">Administración de proyectos integradores</h1>
                        </div>

                        <div class="flex items-center gap-3">
                            <div class="hidden text-right sm:block">
                                <p class="text-sm font-semibold text-slate-900">{{ auth()->user()->nombre }}</p>
                                <p class="text-xs text-slate-500">{{ $roleName }}</p>
                            </div>

                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="rounded-md border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-100">
                                    Salir
                                </button>
                            </form>
                        </div>
                    </div>

                    <nav class="flex gap-2 overflow-x-auto border-t border-slate-100 px-4 py-2 sm:px-6 lg:hidden">
                        @foreach ($navegacion as $item)
                            <a
                                href="{{ $item['ruta'] }}"
                                class="whitespace-nowrap rounded-md px-3 py-2 text-sm font-semibold {{ $active === $item['clave'] ? 'bg-[#15529A] text-white' : 'bg-white text-slate-600' }}"
                            >
                                {{ $item['titulo'] }}
                            </a>
                        @endforeach
                    </nav>
                </header>

                <div class="min-h-0 flex-1 overflow-y-auto">
                    <main class="px-4 py-6 sm:px-6 lg:px-8">
                        {{ $slot }}
                    </main>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
