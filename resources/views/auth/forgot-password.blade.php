<x-layouts.app title="Recuperar contraseña | Administración de proyectos">
    <main class="flex min-h-screen items-center justify-center bg-[#eef6fb] px-5 py-10">
        <section class="relative w-full max-w-[460px] rounded-md border border-slate-200 bg-white p-8 shadow-sm">
            <div class="mb-4 flex justify-center sm:absolute sm:right-4 sm:top-4 sm:mb-0">
                <x-boton-ayuda-acceso seccion="recuperar-contrasena" />
            </div>

            <div class="flex justify-center">
                <img
                    src="{{ asset('assets/utvm/utvm-logo-transparente.png') }}"
                    alt="Logo UTVM"
                    class="h-24 w-auto"
                >
            </div>

            <div class="mt-7 text-center">
                <p class="text-sm font-semibold uppercase tracking-[0.18em] text-[#21A366]">Recuperación de acceso</p>
                <h1 class="mt-2 text-2xl font-bold text-[#0D376D]">Olvidaste tu contraseña</h1>
                <p class="mt-2 text-sm leading-6 text-slate-600">Ingresa el correo registrado para recuperar tu acceso.</p>
            </div>

            <form class="mt-8 space-y-5">
                <div>
                    <label for="email" class="block text-sm font-semibold text-slate-700">Correo registrado</label>
                    <input
                        id="email"
                        name="email"
                        type="email"
                        autocomplete="email"
                        required
                        class="mt-2 block w-full rounded-md border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 outline-none transition focus:border-[#15529A] focus:ring-2 focus:ring-[#15529A]/20"
                        placeholder="correo@utvm.edu.mx"
                    >
                </div>

                <button type="button" class="inline-flex w-full items-center justify-center rounded-md bg-[#15529A] px-5 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-[#0D376D] focus:outline-none focus:ring-2 focus:ring-[#15529A]/30">
                    Enviar instrucciones
                </button>
            </form>

            <a href="{{ route('login') }}" class="mt-6 block text-center text-sm font-semibold text-[#15529A] transition hover:text-[#0D376D]">
                Volver al inicio de sesión
            </a>
        </section>
    </main>
</x-layouts.app>
