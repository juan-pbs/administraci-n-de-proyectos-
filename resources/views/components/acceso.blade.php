@props(['titulo', 'descripcion', 'paso' => 'Recuperación de acceso'])
<x-layouts.app :title="$titulo.' | Administración de proyectos'">
<main class="flex min-h-screen items-center justify-center bg-[#eef6fb] px-5 py-10" data-auth-privado>
    <section class="relative w-full max-w-[460px] rounded-md border border-slate-200 bg-white p-8 shadow-sm">
        <div class="mb-4 flex justify-center sm:absolute sm:right-4 sm:top-4 sm:mb-0"><x-boton-ayuda-acceso seccion="recuperar-contrasena" /></div>
        <div class="flex justify-center"><img src="{{ asset('assets/utvm/utvm-logo-transparente.png') }}" alt="Logo UTVM" class="h-24 w-auto"></div>
        <div class="mt-7 text-center"><p class="text-sm font-semibold uppercase tracking-[0.18em] text-[#21A366]">{{ $paso }}</p><h1 class="mt-2 text-2xl font-bold text-[#0D376D]">{{ $titulo }}</h1><p class="mt-2 text-sm leading-6 text-slate-600">{{ $descripcion }}</p></div>
        @if(session('estado'))<p role="status" class="mt-5 rounded-md bg-green-50 p-3 text-sm text-green-800">{{ session('estado') }}</p>@endif
        @if($errors->any())<div role="alert" class="mt-5 rounded-md bg-red-50 p-3 text-sm text-red-800">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
        {{ $slot }}
        <a href="{{ route('login') }}" class="mt-6 block text-center text-sm font-semibold text-[#15529A]">Volver al inicio de sesión</a>
    </section>
</main>
</x-layouts.app>
