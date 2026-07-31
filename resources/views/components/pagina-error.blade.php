@props([
    'codigo',
    'titulo',
    'mensaje',
    'detalle' => null,
    'tono' => 'azul',
    'reintentar' => false,
])

@php
    $colores = match ($tono) {
        'rojo' => ['borde' => 'border-red-200', 'fondo' => 'bg-red-50', 'texto' => 'text-red-700'],
        'ambar' => ['borde' => 'border-amber-200', 'fondo' => 'bg-amber-50', 'texto' => 'text-amber-700'],
        'verde' => ['borde' => 'border-emerald-200', 'fondo' => 'bg-emerald-50', 'texto' => 'text-emerald-700'],
        default => ['borde' => 'border-blue-200', 'fondo' => 'bg-blue-50', 'texto' => 'text-[#15529A]'],
    };
@endphp

<x-layouts.app :title="$codigo.' | '.$titulo">
    <main class="relative flex min-h-screen items-center justify-center overflow-hidden bg-[#eef6fb] px-5 py-12 sm:px-8">
        <div class="absolute inset-x-0 top-0 h-2 bg-[#21A366]"></div>
        <div class="absolute -left-24 top-20 h-72 w-72 rounded-full bg-[#15529A]/10 blur-3xl"></div>
        <div class="absolute -right-24 bottom-16 h-72 w-72 rounded-full bg-[#21A366]/10 blur-3xl"></div>

        <section class="relative w-full max-w-2xl rounded-lg border border-slate-200 bg-white p-7 text-center shadow-sm sm:p-10" aria-labelledby="titulo-error">
            <img
                src="{{ asset('assets/utvm/utvm-logo-transparente.png') }}"
                alt="Universidad Tecnológica del Valle del Mezquital"
                class="mx-auto h-20 w-auto sm:h-24"
            >

            <div class="mx-auto mt-7 inline-flex rounded-full border px-4 py-1.5 text-sm font-bold {{ $colores['borde'] }} {{ $colores['fondo'] }} {{ $colores['texto'] }}">
                Error {{ $codigo }}
            </div>

            <h1 id="titulo-error" class="mt-5 text-3xl font-bold tracking-tight text-[#0D376D] sm:text-4xl">
                {{ $titulo }}
            </h1>
            <p class="mx-auto mt-4 max-w-xl text-base leading-7 text-slate-600">
                {{ $mensaje }}
            </p>

            @if ($detalle)
                <p class="mx-auto mt-3 max-w-xl text-sm leading-6 text-slate-500">
                    {{ $detalle }}
                </p>
            @endif

            <div class="mt-8 flex flex-col justify-center gap-3 sm:flex-row">
                @auth
                    <a href="{{ route('dashboard') }}" class="inline-flex items-center justify-center rounded-md bg-[#15529A] px-5 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-[#0D376D] focus:outline-none focus:ring-2 focus:ring-[#15529A]/30">
                        Ir al panel principal
                    </a>
                @else
                    <a href="{{ route('login') }}" class="inline-flex items-center justify-center rounded-md bg-[#15529A] px-5 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-[#0D376D] focus:outline-none focus:ring-2 focus:ring-[#15529A]/30">
                        Iniciar sesión
                    </a>
                @endauth

                @if ($reintentar)
                    <a href="{{ request()->fullUrl() }}" class="inline-flex items-center justify-center rounded-md border border-slate-300 bg-white px-5 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-300">
                        Intentar nuevamente
                    </a>
                @else
                    <button type="button" onclick="history.back()" class="inline-flex items-center justify-center rounded-md border border-slate-300 bg-white px-5 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-300">
                        Volver a la página anterior
                    </button>
                @endif
            </div>

            <p class="mt-8 text-xs text-slate-500">Universidad Tecnológica del Valle del Mezquital</p>
        </section>
    </main>
</x-layouts.app>
