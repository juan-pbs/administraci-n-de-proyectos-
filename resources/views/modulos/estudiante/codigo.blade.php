<x-contenedor-aplicacion title="Código y demostración | Administración de proyectos" active="codigo-estudiante" :navegacion="$navegacion" :role-name="$roleName">
    <h2 class="text-2xl font-bold text-[#0D376D]">Código, demostración y aplicación</h2>
    <p class="mt-2 text-sm text-slate-600">Entrega el repositorio, una URL del trabajo alojado o un instalador. Estos productos se comparten únicamente con el docente que puede revisar tu repositorio.</p>
    <x-mensajes-formulario />
    @include('modulos.estudiante.selector-proyecto')
    @if(session('status'))<p class="mt-5 rounded border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">{{ session('status') }}</p>@endif
    @if($proyecto && $apartadoCodigo)
        @if($producto)
        <section class="mt-5 rounded-lg border bg-white p-5">
            <h3 class="font-bold">Última entrega: {{ $producto->version }}</h3>
            <p class="mt-2 break-all text-sm">Repositorio: {{ $producto->repositorio_url ?: 'Sin repositorio' }}</p>
            <p class="mt-2 break-all text-sm">Demostración: {{ $producto->demostracion_url ?: 'Sin URL de demostración' }}</p>
            <p class="mt-2 text-xs text-slate-500">Subida por {{ $producto->entrega?->entregadoPor?->nombre ?? 'Integrante del equipo' }}</p>
            @foreach($producto->entrega?->archivos ?? [] as $archivo)<a href="{{ route('estudiante.archivos.descargar', ['archivo' => $archivo, 'proyecto_contexto' => $proyecto->id]) }}" class="mt-2 block text-sm font-bold text-[#155AA3] underline">{{ $archivo->nombre_original }}</a>@endforeach
        </section>
        @endif
        <section class="mt-5 rounded-lg border bg-white p-5"><h3 class="font-bold">{{ $apartadoCodigo->titulo }}</h3>
        <p class="mt-2 text-sm text-slate-500">Fecha límite: {{ $apartadoCodigo->fecha_limite?->format('d/m/Y H:i') ?? 'Sin fecha definida' }}</p>
        @if($apartadoCodigo->habilitado_para_entrega && (!$apartadoCodigo->fecha_limite || $apartadoCodigo->fecha_limite->isFuture()))
        <form method="POST" enctype="multipart/form-data" action="{{ route('estudiante.codigo.guardar') }}" class="mt-4 grid gap-4">
            @csrf
            <input type="hidden" name="proyecto_contexto" value="{{ $proyecto->id }}">
            <div class="grid gap-4 md:grid-cols-[1fr_180px]">
                <label class="text-sm font-semibold">URL del repositorio<input name="repositorio_url" type="url" value="{{ old('repositorio_url', $producto?->repositorio_url) }}" placeholder="https://github.com/equipo/proyecto" class="mt-2 w-full rounded-md border-slate-300 text-sm"></label>
                <label class="text-sm font-semibold">Versión<input required maxlength="40" name="version" value="{{ old('version', $producto?->version ?? 'v1.0.0') }}" class="mt-2 w-full rounded-md border-slate-300 text-sm"></label>
            </div>
            <label class="text-sm font-semibold">URL de demostración alojada (opcional)<input name="demostracion_url" type="url" maxlength="1000" value="{{ old('demostracion_url', $producto?->demostracion_url) }}" placeholder="https://mi-proyecto.example.com" class="mt-2 w-full rounded-md border-slate-300 text-sm"><span class="mt-1 block text-xs text-slate-500">Debe ser una dirección HTTPS pública. No uses enlaces con contraseñas ni direcciones locales.</span></label>
            <label class="text-sm font-semibold">Aplicación móvil o de escritorio<input name="aplicacion" type="file" accept=".apk,.exe,.msi,.dmg,.appimage,.deb" class="mt-2 block w-full rounded border p-3"><span class="mt-1 block text-xs text-slate-500">APK, EXE, MSI, DMG, AppImage o DEB; hasta 150 MB. Se entrega para descargar, el sistema no ejecuta instaladores.</span></label>
            <label class="text-sm font-semibold">Archivos comprimidos o técnicos<input multiple name="archivos[]" type="file" accept=".zip,.rar,.7z,.tar,.gz,.sql,.md,.txt" class="mt-2 block w-full rounded border p-3"><span class="mt-1 block text-xs text-slate-500">Hasta 10 archivos de 150 MB. Incluye nuevamente los archivos que correspondan a esta versión.</span></label>
            <p class="text-sm text-slate-500">Puedes entregar cualquiera de estas opciones o combinarlas. Cada envío registra una versión nueva que deberá ser aprobada.</p>
            <button class="w-fit rounded-md bg-[#155AA3] px-5 py-3 font-bold text-white">{{ $producto ? 'Enviar nueva versión' : 'Registrar entrega' }}</button>
        </form>
        @else<p class="mt-4 rounded bg-red-50 p-4 text-sm text-red-800">El plazo terminó. Las entregas permanecen visibles y ya no pueden reemplazarse.</p>@endif
        </section>
    @else<p class="mt-5 rounded bg-amber-50 p-5 text-sm text-amber-800">Tu proyecto todavía no tiene un apartado de código configurado.</p>@endif
</x-contenedor-aplicacion>
