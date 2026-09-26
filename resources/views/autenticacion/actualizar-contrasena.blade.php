<x-layouts.app title="Actualizar contraseña | Administración de proyectos">
    <main class="flex min-h-screen items-center justify-center bg-[#eef6fb] px-5 py-10">
        <section class="relative w-full max-w-[460px] rounded-md border border-slate-200 bg-white p-8 shadow-sm">
            <div class="mb-4 flex justify-center sm:absolute sm:right-4 sm:top-4 sm:mb-0">
                <x-boton-ayuda-acceso seccion="actualizar-contrasena" />
            </div>

            <div class="flex justify-center">
                <img
                    src="{{ asset('assets/utvm/utvm-logo-transparente.png') }}"
                    alt="Logo UTVM"
                    class="h-20 w-auto"
                >
            </div>

            <div class="mt-7 text-center">
                <p class="text-sm font-semibold uppercase tracking-[0.18em] text-[#21A366]">Seguridad de tu cuenta</p>
                <h1 class="mt-2 text-2xl font-bold text-[#0D376D]">Actualiza tu contraseña</h1>
                <p class="mt-2 text-sm leading-6 text-slate-600">Puedes cambiar tu contraseña cuando lo necesites.</p>
            </div>

            <form method="POST" action="{{ route('contrasena.actualizar') }}" class="mt-8 space-y-5" data-auth-privado>
                @csrf

                <div>
                    <label for="contrasena_actual" class="block text-sm font-semibold text-slate-700">Contraseña actual</label>
                    <input
                        id="contrasena_actual"
                        name="contrasena_actual"
                        type="password"
                        autocomplete="current-password"
                        required
                        class="mt-2 block w-full rounded-md border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-[#15529A] focus:ring-2 focus:ring-[#15529A]/20"
                    >
                    @error('contrasena_actual')
                        <p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="contrasena" class="block text-sm font-semibold text-slate-700">Nueva contraseña</label>
                    <input
                        id="contrasena"
                        name="contrasena"
                        type="password"
                        autocomplete="new-password"
                        required
                        class="mt-2 block w-full rounded-md border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-[#15529A] focus:ring-2 focus:ring-[#15529A]/20"
                    >
                    @error('contrasena')
                        <p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="contrasena_confirmation" class="block text-sm font-semibold text-slate-700">Confirmar nueva contraseña</label>
                    <input
                        id="contrasena_confirmation"
                        name="contrasena_confirmation"
                        type="password"
                        autocomplete="new-password"
                        required
                        class="mt-2 block w-full rounded-md border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-[#15529A] focus:ring-2 focus:ring-[#15529A]/20"
                    >
                </div>

                <button type="submit" class="inline-flex w-full items-center justify-center rounded-md bg-[#15529A] px-5 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-[#0D376D] focus:outline-none focus:ring-2 focus:ring-[#15529A]/30">
                    Actualizar contraseña
                </button>
            </form>
        </section>
    </main>
</x-layouts.app>
