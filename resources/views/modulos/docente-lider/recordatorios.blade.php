<x-contenedor-aplicacion title="Recordatorios | Administración de proyectos" active="recordatorios" :navegacion="$navegacion" :role-name="$roleName">
    <section class="border-b border-slate-200 pb-5">
        <p class="text-sm font-semibold uppercase tracking-[0.18em] text-[#21A366]">Docente líder</p>
        <h2 class="mt-2 text-2xl font-bold text-[#0D376D]">Recordatorios de entrega</h2>
        <p class="mt-2 max-w-3xl text-sm text-slate-600">Envía un recordatorio a los integrantes de tus equipos que aún tienen entregas pendientes o por corregir.</p>
    </section>

    <x-mensajes-formulario />

    <section class="mt-6 rounded-lg border border-slate-200 bg-white p-5">
        <h3 class="font-bold text-[#0D376D]">Enviar recordatorios manualmente</h3>
        <p class="mt-2 text-sm text-slate-600">Cada botón considera los apartados abiertos del proyecto. El mismo recordatorio manual no se repite para un estudiante, apartado y versión de la asignación durante el día.</p>
        <form method="POST" action="{{ route('docente-lider.recordatorios.enviar-todos') }}" class="mt-4">
            @csrf
            <button class="rounded-md bg-[#0D376D] px-4 py-2 text-sm font-bold text-white">Enviar recordatorios a todos mis proyectos</button>
            <p class="mt-2 text-xs text-slate-500">Incluye todos tus proyectos en proceso, aunque no aparezcan en esta página.</p>
        </form>
        <div class="mt-4 divide-y divide-slate-200">
            @forelse($proyectos as $proyecto)
                <div class="flex flex-wrap items-center justify-between gap-3 py-4">
                    <div><p class="font-semibold text-slate-900">{{ $proyecto->titulo }}</p><p class="text-sm text-slate-500">{{ $proyecto->equipo->nombre }} · {{ $proyecto->guiaIntegradora->nombre }}</p></div>
                    <form method="POST" action="{{ route('docente-lider.recordatorios.enviar', $proyecto) }}">@csrf<button class="rounded-md bg-[#15529A] px-4 py-2 text-sm font-bold text-white">Enviar recordatorios ahora</button></form>
                </div>
            @empty
                <p class="py-5 text-sm text-slate-500">No tienes proyectos en proceso.</p>
            @endforelse
        </div>
        <div class="mt-4">{{ $proyectos->links() }}</div>
    </section>

    <section class="mt-6 space-y-5">
        <div><h3 class="font-bold text-[#0D376D]">Formatos de correo</h3><p class="mt-1 text-sm text-slate-600">Puedes usar {nombre}, {proyecto}, {actividad} y {fecha_limite}. El asunto, los datos de la actividad, el enlace y la imagen institucional permanecen en el correo.</p></div>
        @foreach($tipos as $tipo => $nombre)
            <form method="POST" action="{{ route('docente-lider.recordatorios.guardar') }}" class="rounded-lg border border-slate-200 bg-white p-5">
                @csrf
                <input type="hidden" name="tipo" value="{{ $tipo }}">
                <h4 class="font-bold text-slate-900">{{ $nombre }}</h4>
                <p class="mt-1 text-sm text-slate-600">Este es el formato del texto para los correos de {{ strtolower($nombre) }} de tus equipos.</p>
                <label for="texto-{{ $tipo }}" class="mt-4 block text-sm font-semibold text-slate-700">Texto del correo</label>
                <textarea id="texto-{{ $tipo }}" name="texto" rows="5" maxlength="3000" required class="mt-2 block w-full rounded-md border-slate-300 text-sm">{{ old('tipo') === $tipo ? old('texto') : $plantillas[$tipo] }}</textarea>
                <button class="mt-3 rounded-md bg-[#21A366] px-4 py-2 text-sm font-bold text-white">Guardar formato</button>
            </form>
        @endforeach
    </section>
</x-contenedor-aplicacion>
