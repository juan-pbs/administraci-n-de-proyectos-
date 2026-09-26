<x-contenedor-aplicacion title="Demostración | Administración de proyectos" active="revision-codigo" :navegacion="$navegacion" :role-name="$roleName">
    <h2 class="text-2xl font-bold text-[#0D376D]">{{ $producto->proyecto->titulo }}</h2>
    <p class="mt-2 text-sm text-slate-600">Demostración de la versión {{ $producto->version }}. Es un sitio externo proporcionado por el equipo.</p>
    <div class="mt-5 rounded-lg border bg-white p-5">
        <p class="break-all text-sm">{{ $producto->demostracion_url }}</p>
        <div class="mt-4 flex flex-wrap gap-3"><button type="button" data-cargar-demo class="rounded bg-[#155AA3] px-4 py-2 font-bold text-white">Cargar vista previa</button><a href="{{ $producto->demostracion_url }}" target="_blank" rel="noopener noreferrer" class="rounded border px-4 py-2 font-bold text-[#155AA3]">Abrir demostración</a></div>
        <p class="mt-3 text-sm text-slate-600">Si el alojamiento impide la vista integrada o requiere iniciar sesión, usa “Abrir demostración”. No introduzcas credenciales del sistema académico en este sitio.</p>
    </div>
    <iframe data-demo-frame data-url="{{ $producto->demostracion_url }}" title="Demostración del equipo" sandbox="allow-scripts allow-forms" referrerpolicy="no-referrer" allow="camera 'none'; microphone 'none'; geolocation 'none'; clipboard-read 'none'; clipboard-write 'none'" class="mt-5 hidden h-[70vh] w-full rounded-lg border bg-white"></iframe>
</x-contenedor-aplicacion>
