@props(['accion', 'etiqueta', 'mensaje', 'metodo' => 'DELETE', 'campo' => 'confirmar_eliminacion', 'peligro' => true])

<details {{ $attributes->merge(['class' => 'rounded-md border border-slate-200 bg-white p-3 text-sm']) }}>
    <summary class="boton-secundario {{ $peligro ? 'boton-peligro' : '' }}">{{ $etiqueta }}</summary>
    <form method="POST" action="{{ $accion }}" class="mt-3 space-y-3">
        @csrf @method($metodo)
        {{ $slot }}
        <label class="flex items-start gap-2 text-slate-700">
            <input class="mt-1" type="checkbox" name="{{ $campo }}" value="1" required>
            <span>{{ $mensaje }}</span>
        </label>
        <button type="submit" class="rounded-md px-3 py-2 font-bold text-white {{ $peligro ? 'bg-red-700' : 'bg-[#15529A]' }}">{{ $etiqueta }}</button>
    </form>
</details>
