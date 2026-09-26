<x-acceso titulo="Elige tu nueva contraseña" descripcion="Usa al menos 8 caracteres. Después de guardarla volverás al inicio de sesión." paso="Paso 3 de 3">
    <form method="POST" action="{{ route('password.actualizar') }}" autocomplete="off" class="mt-8 space-y-5">
        @csrf
        <div><label for="contrasena" class="block text-sm font-semibold text-slate-700">Nueva contraseña</label><input id="contrasena" name="contrasena" type="password" minlength="8" maxlength="255" autocomplete="new-password" required class="mt-2 block w-full rounded-md border border-slate-300 bg-white px-4 py-3"></div>
        <div><label for="contrasena_confirmation" class="block text-sm font-semibold text-slate-700">Confirmar nueva contraseña</label><input id="contrasena_confirmation" name="contrasena_confirmation" type="password" minlength="8" maxlength="255" autocomplete="new-password" required class="mt-2 block w-full rounded-md border border-slate-300 bg-white px-4 py-3"></div>
        <button type="submit" class="w-full rounded-md bg-[#15529A] px-5 py-3 font-bold text-white">Guardar contraseña</button>
    </form>
</x-acceso>
