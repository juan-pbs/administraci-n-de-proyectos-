<section class="mt-4 w-full rounded-md border border-slate-200 bg-slate-50 p-4" aria-labelledby="resultado-recordatorios">
    <div class="flex flex-wrap items-center justify-between gap-2">
        <h4 id="resultado-recordatorios" class="font-bold text-[#0D376D]">Resultado del último envío solicitado</h4>
        <form method="GET" action="{{ route('docente-lider.recordatorios') }}#resultado-recordatorios">
            <input type="hidden" name="page" value="{{ $proyectos->currentPage() }}">
            <button type="submit" class="boton-secundario">Actualizar estado</button>
        </form>
    </div>
    <div class="mt-2 overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead><tr class="border-b border-slate-200"><th scope="col" class="p-2">Destinatarios</th><th scope="col" class="p-2">Enviados</th><th scope="col" class="p-2">Sin enviar</th><th scope="col" class="p-2">En cola</th></tr></thead>
            <tbody>
                @foreach($resultadoEnvio as $destinatarios => $conteos)
                    <tr><th scope="row" class="p-2 font-medium">{{ $destinatarios }}</th><td class="p-2">{{ $conteos['enviados'] }}</td><td class="p-2">{{ $conteos['no_enviados'] }}</td><td class="p-2">{{ $conteos['pendientes'] }}</td></tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="border-t border-slate-200 font-semibold"><th scope="row" class="p-2">Total</th><td class="p-2">{{ collect($resultadoEnvio)->sum('enviados') }}</td><td class="p-2">{{ collect($resultadoEnvio)->sum('no_enviados') }}</td><td class="p-2">{{ collect($resultadoEnvio)->sum('pendientes') }}</td></tr>
            </tfoot>
        </table>
    </div>
    @if(collect($resultadoEnvio)->flatten()->sum() === 0)
        <p class="mt-2 text-sm text-slate-700">No se generaron avisos nuevos. Solo se incluyen pendientes vigentes que aún no se han solicitado hoy.</p>
    @elseif(collect($resultadoEnvio)->sum('no_enviados') > 0)
        <p class="mt-2 text-sm text-slate-700">Los avisos sin enviar fallaron o fueron cancelados porque dejaron de aplicar.</p>
    @endif
</section>
