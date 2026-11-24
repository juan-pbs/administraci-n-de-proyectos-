<div class="mb-4 grid gap-2">
    @unless($guia->tiene_historial)
    <details class="rounded-md border border-slate-200 bg-white p-3">
        <summary class="cursor-pointer text-sm font-bold text-[#15529A]">Editar datos de la guía</summary>
        <form method="POST" action="{{ route('guias.actualizar', $guia) }}" class="mt-3 grid gap-3 md:grid-cols-2">
            @csrf @method('PATCH')
            <label class="text-sm font-semibold">Nombre<input name="nombre" maxlength="255" value="{{ $guia->nombre }}" required class="mt-1 block w-full rounded-md border-slate-300"></label>
            <label class="text-sm font-semibold">Versión<input name="version" maxlength="30" pattern="[0-9]+(\.[0-9]+)*" value="{{ $guia->version }}" required class="mt-1 block w-full rounded-md border-slate-300"></label>
            <label class="text-sm font-semibold">Periodo<select name="periodo_id" class="mt-1 block w-full rounded-md border-slate-300">@foreach($periodos->where('estado', '!=', 'cerrado') as $opcion)<option value="{{ $opcion->id }}" @selected($opcion->id === $guia->periodo_id)>{{ $opcion->nombre }}</option>@endforeach</select></label>
            <label class="text-sm font-semibold">Periodo final<select name="periodo_fin_id" class="mt-1 block w-full rounded-md border-slate-300"><option value="">Mismo periodo inicial</option>@foreach($periodos->where('estado', '!=', 'cerrado') as $opcion)<option value="{{ $opcion->id }}" @selected($opcion->id === $guia->periodo_fin_id)>{{ $opcion->nombre }}</option>@endforeach</select></label>
            <label class="text-sm font-semibold">Asignatura principal<select name="asignatura_id" required class="mt-1 block w-full rounded-md border-slate-300"><option value="">Selecciona una asignatura</option>@foreach($asignaturas as $opcion)<option value="{{ $opcion->id }}" @selected($opcion->id === $guia->asignatura_id)>{{ $opcion->carrera?->clave }} · {{ $opcion->nombre }}</option>@endforeach</select></label>
            <label class="text-sm font-semibold">Cuatrimestre<input name="cuatrimestre" type="number" min="1" max="12" value="{{ (int) $guia->cuatrimestre }}" required class="mt-1 block w-full rounded-md border-slate-300"></label>
            <label class="text-sm font-semibold">Competencias<textarea name="competencias_evaluar" rows="3" class="mt-1 block w-full rounded-md border-slate-300">{{ $guia->competencias_evaluar }}</textarea></label>
            <label class="text-sm font-semibold">Objetivo de aprendizaje<textarea name="objetivo_aprendizaje" rows="3" class="mt-1 block w-full rounded-md border-slate-300">{{ $guia->objetivo_aprendizaje }}</textarea></label>
            <button class="w-fit rounded-md bg-[#15529A] px-3 py-2 text-sm font-bold text-white">Guardar cambios de la guía</button>
        </form>
    </details>
    @endunless
    @if($guia->estado === 'publicada' || $guia->tiene_historial)
        <details class="rounded-md border border-slate-200 bg-white p-3">
            <summary class="cursor-pointer text-sm font-bold text-[#15529A]">Crear nueva versión</summary>
            <form method="POST" action="{{ route('guias.versiones.guardar', $guia) }}" class="mt-3 grid gap-3 md:grid-cols-2">
                @csrf
                <label class="text-sm font-semibold">Número de versión<input name="version" required maxlength="30" placeholder="Ejemplo 2.0" pattern="[0-9]+(\.[0-9]+)*" class="mt-1 block w-full rounded-md border-slate-300"></label>
                <label class="text-sm font-semibold">Motivo<textarea name="motivo" rows="2" required maxlength="2000" class="mt-1 block w-full rounded-md border-slate-300"></textarea></label>
                <button class="w-fit rounded-md bg-[#15529A] px-3 py-2 text-sm font-bold text-white">Crear borrador vinculado</button>
            </form>
        </details>
    @endif
    @if($guia->estado === 'publicada' && !$guia->tiene_historial)
        <x-confirmar-accion :accion="route('guias.borrador', $guia)" etiqueta="Regresar a borrador"
            metodo="PATCH" campo="confirmar_borrador" :peligro="false"
            mensaje="Confirmo que quiero suspender la publicación. Se requiere que la guía no tenga historial de entregas, cierre o PDF." />
    @endif
    <x-confirmar-accion :accion="route('guias.eliminar', $guia)" etiqueta="Eliminar guía"
        mensaje="Confirmo que quiero eliminar la guía y sus apartados si la guía no está asignada a ningún proyecto." />
</div>
