<x-contenedor-aplicacion title="Mi firma | Administración de proyectos" active="mi-firma" :navegacion="$navegacion" :role-name="$roleName">
    <h2 class="text-2xl font-bold text-[#0D376D]">Mi firma</h2>
    <p class="mt-2 text-sm text-slate-600">Sube una imagen o dibuja tu firma con el mouse, lápiz o dedo.</p>
    <x-mensajes-formulario />
    @if($firma)<p class="mt-5 rounded-md bg-emerald-50 p-4 text-sm text-emerald-800">Tienes una firma registrada desde {{ $firma->updated_at->format('d/m/Y H:i') }}. Por privacidad, la imagen guardada no se muestra. Cambiarla no modifica las autorizaciones ni los PDF ya emitidos.</p>@endif
    <form method="POST" enctype="multipart/form-data" action="{{ route('docente.firma.guardar') }}" data-firma-form class="mt-5 max-w-3xl space-y-5 rounded-lg border border-slate-200 bg-white p-5">
        @csrf
        <label class="block text-sm font-semibold">Imagen PNG o JPG<input name="firma" type="file" accept="image/png,image/jpeg" class="mt-2 block w-full rounded border p-2"><span class="mt-1 block text-xs text-slate-500">Hasta 2 MB y 2400 × 1200 píxeles. La imagen seleccionada tiene prioridad sobre el dibujo.</span></label>
        <div><p class="mb-2 text-sm font-semibold">O dibuja aquí</p><canvas data-firma-canvas width="800" height="220" aria-label="Área para dibujar la firma" class="w-full rounded border-2 border-slate-300 bg-white" style="touch-action:none"></canvas><input type="hidden" name="firma_dibujada" data-firma-datos><button type="button" data-firma-limpiar class="mt-2 rounded border px-3 py-2 text-sm">Borrar dibujo</button><p data-firma-ayuda class="mt-2 text-sm text-slate-500" aria-live="polite">La firma se enviará al guardar.</p></div>
        <label class="block text-sm font-semibold">Confirma tu contraseña actual<input required autocomplete="current-password" type="password" name="contrasena_actual" class="mt-2 block w-full rounded border-slate-300"></label>
        <p class="text-sm text-slate-600">Al aprobar una entrega tendrás que autorizar el uso de tu firma para esa versión. Se guardará cifrada y se marcará con el proyecto al incluirla en el PDF.</p>
        <button class="rounded-md bg-[#155AA3] px-5 py-3 font-bold text-white">Guardar firma</button>
    </form>
</x-contenedor-aplicacion>
