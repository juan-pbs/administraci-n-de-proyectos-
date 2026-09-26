<section class="mt-5 rounded-lg border border-slate-200 bg-white p-5 shadow-sm" data-busqueda-panel data-busqueda-url="{{ route('dashboard.buscar') }}" aria-labelledby="buscar-acciones-titulo">
    <h3 id="buscar-acciones-titulo" class="text-lg font-bold text-[#0D376D]">¿Qué necesitas hacer?</h3>
    <p id="buscar-acciones-ayuda" class="mt-1 text-sm text-slate-500">Escribe lo que buscas. No hace falta conocer el nombre del módulo.</p>
    <form method="GET" action="{{ route('dashboard') }}" class="mt-4 flex gap-2" data-busqueda-form>
        <label for="buscar-acciones" class="sr-only">Buscar acciones del sistema</label>
        <input id="buscar-acciones" name="buscar" type="search" maxlength="160" value="{{ $consultaBusqueda }}" autocomplete="off" aria-describedby="buscar-acciones-ayuda" aria-controls="buscar-acciones-resultados" placeholder="Ej. {{ $role === 'coordinacion' ? 'subir alumnos o cambiar fechas' : 'acomodar alumnos o poner mi firma' }}" class="min-w-0 flex-1 rounded-md border border-slate-300 px-4 py-3 text-sm outline-none focus:border-[#15529A] focus:ring-2 focus:ring-[#15529A]/20">
        <button type="submit" class="rounded-md bg-[#15529A] px-4 py-3 text-sm font-bold text-white">Buscar</button>
    </form>
    <div class="mt-3 flex flex-wrap gap-2" aria-label="Ejemplos de búsqueda">
        @foreach($role === 'coordinacion' ? ['subir alumnos', 'agregar profesores', 'cambiar fechas', 'vista previa PDF'] : ['acomodar alumnos', 'revisar repo', 'poner mi firma', 'PDF final'] as $ejemplo)
            <a href="{{ route('dashboard', ['buscar' => $ejemplo]) }}" data-busqueda-ejemplo="{{ $ejemplo }}" class="rounded-full bg-[#EAF2FB] px-3 py-1.5 text-xs font-semibold text-[#15529A] hover:bg-blue-100">{{ $ejemplo }}</a>
        @endforeach
    </div>
    <p role="status" aria-live="polite" aria-atomic="true" class="mt-4 text-sm font-semibold text-slate-600" data-busqueda-estado @if($consultaBusqueda === '') hidden @endif>{{ count($resultadosBusqueda).' acciones relacionadas' }}</p>
    <ul id="buscar-acciones-resultados" class="mt-2 grid gap-2 md:grid-cols-2" data-busqueda-resultados @if($consultaBusqueda === '') hidden @endif>
        @foreach($resultadosBusqueda as $resultado)
            <li><a href="{{ $resultado['ruta'] }}" class="block h-full rounded-md border border-slate-200 p-3 transition hover:border-[#15529A] hover:bg-[#F5F9FE] focus:outline-none focus:ring-2 focus:ring-[#15529A]">
                <span class="text-xs font-semibold text-[#21A366]">{{ $resultado['modulo'] }}</span>
                <span class="mt-1 block font-bold text-[#0D376D]">{{ $resultado['titulo'] }}</span>
                <span class="mt-1 block text-sm leading-5 text-slate-500">{{ $resultado['descripcion'] }}</span>
            </a></li>
        @endforeach
    </ul>
    <p class="mt-3 text-sm text-slate-500" data-busqueda-vacia @if($resultadosBusqueda) hidden @endif>No encontramos una acción relacionada. Prueba con otra palabra o consulta <a href="{{ route('ayuda') }}" class="font-semibold text-[#15529A] underline">Ayuda</a>.</p>
</section>
