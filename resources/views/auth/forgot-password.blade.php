<x-acceso titulo="Olvidaste tu contraseña" descripcion="Ingresa tu correo registrado. Te enviaremos un código para recuperar el acceso aquí mismo." paso="Paso 1 de 3">
    <form method="POST" action="{{ route('password.enviar') }}" autocomplete="off" class="mt-8 space-y-5">
        @csrf
        <div><label for="correo_recuperacion" class="block text-sm font-semibold text-slate-700">Correo registrado</label>
        <input id="correo_recuperacion" name="correo_recuperacion" type="email" maxlength="255" autocomplete="off" required class="mt-2 block w-full rounded-md border border-slate-300 bg-white px-4 py-3" placeholder="correo@utvm.edu.mx"></div>
        <button type="submit" class="w-full rounded-md bg-[#15529A] px-5 py-3 font-bold text-white">Enviar código</button>
    </form>
</x-acceso>
