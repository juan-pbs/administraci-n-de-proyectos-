<div data-async-region="mensajes">
    @if ($mensaje = session('estado') ?? session('status'))
        <div role="status" tabindex="-1" class="mt-3 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">
            {{ $mensaje }}
        </div>
    @endif

    @if ($errors->any())
        <div role="alert" tabindex="-1" class="mt-3 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            <p class="font-bold">No se completó la acción</p>
            <ul class="mt-2 list-inside list-disc">
                @foreach ($errors->getBag('default')->getMessages() as $campo => $mensajes)
                    @foreach ($mensajes as $error)
                        <li data-campo-error="{{ $campo }}">{{ $error }}</li>
                    @endforeach
                @endforeach
            </ul>
        </div>
    @endif
</div>
