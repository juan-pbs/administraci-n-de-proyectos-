<div class="mb-4 grid gap-2">
    <details class="rounded-md border border-slate-200 bg-white p-3">
        <summary class="cursor-pointer text-sm font-bold text-[#15529A]">Editar proyecto</summary>
        <form method="POST" action="{{ route('proyectos.actualizar', $proyecto) }}" class="mt-3 grid gap-3 md:grid-cols-2">
            @csrf @method('PATCH')
            <input type="hidden" name="periodo_proyectos" value="{{ $periodoSeleccionado }}"><input type="hidden" name="grupo_proyectos" value="{{ $grupoSeleccionado }}">
            <label class="text-sm font-semibold">Título<input name="titulo" maxlength="255" required value="{{ $proyecto->titulo }}" class="mt-1 block w-full rounded-md border-slate-300"></label>
            <label class="text-sm font-semibold">Guía
                <select name="guia_integradora_id" required class="mt-1 block w-full rounded-md border-slate-300">
                    <option value="{{ $proyecto->guia_integradora_id }}">{{ $proyecto->guiaIntegradora->nombre }}</option>
                    @foreach($guias->where('asignatura.carrera_id', $carreraId)->where('cuatrimestre', (string) $grado)->where('id', '!=', $proyecto->guia_integradora_id) as $opcion)
                        <option value="{{ $opcion->id }}">{{ $opcion->nombre }} · v{{ $opcion->version }}</option>
                    @endforeach
                </select>
            </label>
            <label class="text-sm font-semibold md:col-span-2">Descripción<textarea name="descripcion" rows="3" class="mt-1 block w-full rounded-md border-slate-300">{{ $proyecto->descripcion }}</textarea></label>
            @if($tieneHistorial)
                <label class="text-sm font-semibold md:col-span-2">Motivo de la corrección<textarea name="motivo" rows="2" maxlength="2000" required class="mt-1 block w-full rounded-md border-slate-300"></textarea></label>
                <p class="text-xs text-slate-600 md:col-span-2">Si corriges título o descripción, las firmas existentes deben autorizarse de nuevo.</p>
            @endif
            <button class="w-fit rounded-md bg-[#15529A] px-3 py-2 text-sm font-bold text-white">Guardar proyecto</button>
        </form>
    </details>
    <x-confirmar-accion :accion="route('proyectos.eliminar', $proyecto)" etiqueta="Eliminar proyecto"
        mensaje="Confirmo que quiero eliminar este proyecto si no tiene entregas, PDF ni historial de cierre.">
        <input type="hidden" name="periodo_proyectos" value="{{ $periodoSeleccionado }}"><input type="hidden" name="grupo_proyectos" value="{{ $grupoSeleccionado }}">
    </x-confirmar-accion>
</div>
