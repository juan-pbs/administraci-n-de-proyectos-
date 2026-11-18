@props(['paneles', 'activo' => null])

<div class="flex gap-2 sm:gap-4" data-controles-formularios>
    @foreach ($paneles as $panel => $titulo)
        <button type="button" aria-controls="{{ $panel }}" aria-expanded="{{ $panel === $activo ? 'true' : 'false' }}"
            class="group flex min-w-0 flex-1 items-center justify-between gap-2 rounded-lg border border-slate-200 bg-white px-3 py-3 text-left text-sm font-bold text-[#0D376D] shadow-sm hover:bg-[#EAF2FB] aria-expanded:flex-[2] aria-expanded:border-[#15529A] aria-expanded:bg-[#EAF2FB] sm:px-5 sm:py-4 sm:text-base">
            <span>{{ $titulo }}</span>
            <span class="shrink-0 text-[#15529A] transition-transform group-aria-expanded:rotate-180" aria-hidden="true">▾</span>
        </button>
    @endforeach
</div>

@once
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('[data-controles-formularios]').forEach(grupo => {
                const botones = grupo.querySelectorAll('button[aria-controls]');
                botones.forEach(boton => {
                    boton.addEventListener('click', () => {
                        const abrir = boton.getAttribute('aria-expanded') === 'false';
                        botones.forEach(control => {
                            const abierto = abrir && control === boton;
                            control.setAttribute('aria-expanded', String(abierto));
                            document.getElementById(control.getAttribute('aria-controls')).hidden = !abierto;
                        });
                    });
                });
            });
        });
    </script>
@endonce
