@props(['accion', 'comentario'])

<div class="mt-3 grid gap-2">
    <details class="rounded-md border border-slate-200 p-3 text-sm">
        <summary class="cursor-pointer font-bold text-[#15529A]">Editar comentario</summary>
        <form method="POST" action="{{ $accion }}" class="mt-2 space-y-2">
            @csrf @method('PATCH')
            <label class="block font-semibold">Comentario<textarea class="mt-1 block w-full rounded-md border-slate-300" name="comentario" required maxlength="1500" rows="2">{{ $comentario->comentario }}</textarea></label>
            <button class="rounded-md bg-[#15529A] px-3 py-2 font-bold text-white">Guardar comentario</button>
        </form>
    </details>
    <x-confirmar-accion :accion="$accion" etiqueta="Eliminar comentario" mensaje="Confirmo que deseo eliminar este comentario." />
</div>
