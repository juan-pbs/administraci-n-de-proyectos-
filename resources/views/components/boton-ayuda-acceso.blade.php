@props([
    'seccion' => 'iniciar-sesion',
])

<a
    href="{{ route('ayuda.acceso', ['seccion' => $seccion]) }}"
    target="_blank"
    rel="noopener noreferrer"
    onclick="window.open(this.href, '_blank', 'width=980,height=760,scrollbars=yes,resizable=yes'); return false;"
    class="inline-flex items-center gap-2 rounded-md border border-[#2F6330] bg-[#2F6330] px-3 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-[#244F27] focus:outline-none focus:ring-2 focus:ring-[#2F6330]/30"
>
    <span class="grid h-5 w-5 place-items-center rounded bg-white/95 text-xs font-black text-[#2F6330]">?</span>
    <span>Ayuda</span>
</a>
