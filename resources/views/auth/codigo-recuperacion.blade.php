<x-acceso titulo="Escribe el código" descripcion="Revisa tu correo e ingresa el código de 8 dígitos. Es válido durante 10 minutos." paso="Paso 2 de 3">
    <form method="POST" action="{{ route('password.verificar') }}" autocomplete="off" class="mt-8 space-y-5">
        @csrf
        <div><label for="codigo" class="block text-sm font-semibold text-slate-700">Código de recuperación</label>
        <input id="codigo" name="codigo" type="text" inputmode="numeric" pattern="[0-9]{8}" minlength="8" maxlength="8" autocomplete="off" required class="mt-2 block w-full rounded-md border border-slate-300 bg-white px-4 py-3 text-center text-xl tracking-widest" placeholder="12345678"></div>
        <button type="submit" class="w-full rounded-md bg-[#15529A] px-5 py-3 font-bold text-white">Verificar código</button>
    </form>
    <form method="POST" action="{{ route('password.reenviar') }}" class="mt-5" data-reenviar-codigo data-reenviar-en="{{ $reenviarEn }}" data-servidor-ahora="{{ $ahora }}">
        @csrf
        <button type="submit" @disabled($reenviarEn > $ahora) class="w-full rounded-md border border-[#15529A] px-5 py-3 font-semibold text-[#15529A] disabled:cursor-not-allowed disabled:opacity-50" data-reenviar-boton>{{ $reenviarEn > $ahora ? 'Volver a enviar código en '.gmdate('i:s', max(0, $reenviarEn - $ahora)) : 'Volver a enviar código' }}</button>
        <p class="mt-2 text-center text-xs text-slate-500">Puedes solicitar otro código cada 3 minutos. Un código nuevo invalida el anterior.</p>
    </form>
</x-acceso>
