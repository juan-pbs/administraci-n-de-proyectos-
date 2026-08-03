@php
    $tipo = $seccion['tipo'] ?? 'modulo';
    $pasos = collect($seccion['pasos'] ?? []);
    $notas = collect($seccion['notas'] ?? []);
    $capturas = collect($seccion['capturas'] ?? []);
    $marcas = $capturas->flatMap(fn (array $captura) => $captura['marcas'] ?? []);
    $detalles = collect($seccion['detalles'] ?? []);
@endphp

<x-layouts.app title="Ayuda {{ $seccion['titulo'] }}">
    <main class="min-h-screen bg-[#EEEEEE] px-4 py-10 sm:px-6">
        <article class="mx-auto max-w-4xl bg-white px-7 py-9 text-slate-900 shadow-sm sm:px-12 sm:py-12">
            <div class="max-w-2xl">
                <p class="text-xs font-black uppercase tracking-[0.18em] text-[#2F6330]">Ayuda de esta pantalla</p>
                <h1 class="mt-2 text-xl font-bold text-slate-800">{{ $seccion['titulo'] }}</h1>

                <p class="mt-5 text-base leading-6 text-slate-800">
                    {{ $seccion['descripcion'] }}
                </p>
            </div>

            @if($pasos->isNotEmpty())
                <div class="mt-6 grid gap-3 sm:grid-cols-3">
                    @foreach($pasos as $paso)
                        <div class="rounded-md border border-slate-200 bg-slate-50 px-4 py-3">
                            <div class="grid h-7 w-7 place-items-center rounded-md bg-[#2F6330] text-sm font-black text-white">
                                {{ $loop->iteration }}
                            </div>
                            <p class="mt-3 text-sm leading-6 text-slate-700">{{ $paso }}</p>
                        </div>
                    @endforeach
                </div>
            @endif

            @if($marcas->isNotEmpty())
                <div class="mt-6 rounded-md border border-[#B7D7BD] bg-[#F3FAF5] px-4 py-3 text-sm font-semibold leading-6 text-[#2F6330]">
                    Sigue las flechas de la captura para ubicar campos, filtros, botones y zonas importantes de la pantalla.
                </div>
            @endif

            @if($tipo === 'contacto')
                <div class="mt-7 rounded-md border border-[#B7D7BD] bg-[#F3FAF5] px-4 py-3 text-sm font-semibold leading-6 text-[#2F6330]">
                    Reporta la incidencia con el responsable del sistema.
                </div>
            @endif

            @if($tipo === 'logout')
                <div class="mt-7 rounded-md border border-[#B7D7BD] bg-[#F3FAF5] px-4 py-3 text-sm font-semibold leading-6 text-[#2F6330]">
                    Al cerrar sesion se protege tu cuenta y se evita que alguien mas use tu acceso.
                </div>
            @endif

            @foreach($capturas as $captura)
                @include('partials.ayuda.figura-anotada', ['seccion' => [...$seccion, ...$captura]])
            @endforeach

            @if($notas->isNotEmpty())
                <div class="mt-4 grid gap-2 sm:grid-cols-3">
                    @foreach($notas as $nota)
                        <div class="rounded-md border border-[#B7D7BD] bg-[#F3FAF5] px-3 py-2 text-xs font-bold text-[#2F6330]">
                            {{ $nota }}
                        </div>
                    @endforeach
                </div>
            @endif

            @if($detalles->isNotEmpty())
                <section class="mt-7">
                    <h2 class="text-base font-bold text-slate-900">Campos y botones clave</h2>
                    <div class="mt-3 grid gap-3 sm:grid-cols-2">
                        @foreach($detalles as $detalle)
                            <div class="rounded-md border border-slate-200 bg-white px-4 py-3 shadow-sm">
                                <p class="text-sm font-bold text-[#0D376D]">{{ $detalle['titulo'] }}</p>
                                <p class="mt-2 text-sm leading-6 text-slate-600">{{ $detalle['texto'] }}</p>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif

            <button type="button" onclick="window.close()" class="mt-8 w-full rounded-md border border-slate-300 bg-white px-4 py-3 text-sm font-semibold text-slate-900 transition hover:bg-slate-100">
                Cerrar
            </button>
        </article>
    </main>
</x-layouts.app>
