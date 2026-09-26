<x-layouts.app title="Iniciar sesión | Administración de proyectos">
    <main class="min-h-screen bg-[#eef6fb]">
        <section class="grid min-h-screen lg:grid-cols-[minmax(0,1.05fr)_minmax(420px,0.95fr)]">
            <aside class="relative hidden min-w-0 overflow-hidden bg-[#15529A] lg:block">
                <img
                    src="{{ asset('assets/utvm/utvm-logo-banner.jpg') }}"
                    alt="Universidad Tecnológica del Valle del Mezquital"
                    class="absolute inset-0 h-full w-full object-cover object-center"
                >
                <div class="absolute inset-0 bg-[linear-gradient(90deg,rgba(13,55,109,0.72),rgba(13,55,109,0.26)_48%,rgba(21,82,154,0.12))]"></div>
                <div class="absolute inset-x-0 top-0 h-2 bg-[#21A366]"></div>

                <div class="relative flex h-full items-center p-12 text-white">
                    <div class="max-w-xl rounded-md bg-[#0D376D]/82 p-6 shadow-lg ring-1 ring-white/15 backdrop-blur">
                        <p class="text-sm font-semibold uppercase tracking-[0.22em] text-[#c9f4df]">Gestión académica</p>
                        <h1 class="mt-4 text-4xl font-bold leading-tight">Administración de proyectos integradores</h1>
                        <p class="mt-5 text-base leading-7 text-white/85">
                            Control de equipos, guías, entregas, revisiones y evidencias para el seguimiento académico.
                        </p>
                    </div>
                </div>
            </aside>

            <section class="flex min-w-0 items-center justify-center px-5 py-10 sm:px-8">
                <div class="w-full max-w-[460px]">
                    <div class="relative rounded-md border border-slate-200 bg-white p-8 shadow-sm">
                        <div class="mb-4 flex justify-center sm:absolute sm:right-4 sm:top-4 sm:mb-0">
                            <x-boton-ayuda-acceso seccion="iniciar-sesion" />
                        </div>

                        <div class="flex justify-center">
                            <img
                                src="{{ asset('assets/utvm/utvm-logo-transparente.png') }}"
                                alt="Logo UTVM"
                                class="h-24 w-auto"
                            >
                        </div>

                        <div class="mt-7 text-center">
                            <p class="text-sm font-semibold uppercase tracking-[0.18em] text-[#21A366]">Acceso al sistema</p>
                            <h2 class="mt-2 text-2xl font-bold text-[#0D376D]">Iniciar sesión</h2>
                            <p class="mt-2 text-sm leading-6 text-slate-600">Ingresa con tu matrícula y contraseña.</p>
                        </div>

                        @if(session('estado'))<p role="status" class="mt-5 rounded-md bg-green-50 p-3 text-sm text-green-800">{{ session('estado') }}</p>@endif
                        <form method="POST" action="{{ route('login') }}" class="mt-8 space-y-5" data-auth-privado>
                            @csrf

                            <div>
                                <label for="matricula" class="block text-sm font-semibold text-slate-700">Matrícula</label>
                                <input
                                    id="matricula"
                                    name="matricula"
                                    type="text"
                                    value="{{ old('matricula') }}"
                                    autocomplete="username"
                                    autofocus
                                    required
                                    class="mt-2 block w-full rounded-md border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-[#15529A] focus:ring-2 focus:ring-[#15529A]/20"
                                    placeholder="Ej. 20260001"
                                >
                                @error('matricula')
                                    <p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="password" class="block text-sm font-semibold text-slate-700">Contraseña</label>
                                <input
                                    id="password"
                                    name="password"
                                    type="password"
                                    autocomplete="current-password"
                                    required
                                    class="mt-2 block w-full rounded-md border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-[#15529A] focus:ring-2 focus:ring-[#15529A]/20"
                                    placeholder="Ingresa tu contraseña"
                                >
                                @error('password')
                                    <p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="flex items-center justify-between gap-4">
                                <label class="inline-flex items-center gap-2 text-sm text-slate-600">
                                    <input name="remember" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-[#15529A] focus:ring-[#15529A]">
                                    Recordar sesión
                                </label>
                                <a href="{{ route('password.request') }}" class="text-sm font-semibold text-[#15529A] transition hover:text-[#0D376D]">
                                    ¿Olvidaste tu contraseña?
                                </a>
                            </div>

                            <button type="submit" class="inline-flex w-full items-center justify-center rounded-md bg-[#15529A] px-5 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-[#0D376D] focus:outline-none focus:ring-2 focus:ring-[#15529A]/30">
                                Iniciar sesión
                            </button>
                        </form>
                    </div>

                    <p class="mt-6 text-center text-xs text-slate-500">Universidad Tecnológica del Valle del Mezquital</p>
                </div>
            </section>
        </section>
    </main>
</x-layouts.app>
