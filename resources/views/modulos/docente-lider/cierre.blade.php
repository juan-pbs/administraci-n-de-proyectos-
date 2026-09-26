<x-contenedor-aplicacion title="Decisión de cierre" active="cierres" :navegacion="$navegacion" :role-name="$roleName">
    <a href="{{ route('docente-lider.cierres') }}" class="text-sm text-[#155AA3] underline">Volver a cierres y prórrogas</a>
    <h2 class="mt-3 text-2xl font-bold text-[#0D376D]">{{ $proyecto->titulo }}</h2>
    <p class="mt-2 text-sm text-slate-600">{{ $proyecto->equipo->nombre }} · {{ $proyecto->guiaIntegradora->nombre }} · Estado: {{ ucfirst($cierre->estado) }}</p>
    <x-mensajes-formulario />
    <section class="mt-5 rounded-lg border bg-white p-5">
        <h3 class="font-bold">{{ $cierre->estado === 'cerrado' ? 'Pendientes conservados al cerrar' : 'Pendientes del formato final' }}</h3>
        <ul class="mt-3 space-y-3">@forelse($pendientes as $pendiente)<li class="rounded bg-amber-50 p-3 text-sm"><strong>{{ $pendiente['apartado'] }}</strong><span class="ml-2 text-xs uppercase">{{ $pendiente['tipo'] }}</span><p class="mt-1">{{ $pendiente['detalle'] }}</p></li>@empty<li class="text-sm text-emerald-800">Sin entregas ni firmas pendientes.</li>@endforelse</ul>
        <a href="{{ route('documentos.mostrar', ['proyecto' => $proyecto, 'origen' => 'docente-lider.cierres.mostrar']) }}" class="mt-4 inline-block font-bold text-[#155AA3] underline">Consultar formato y PDF emitidos</a>
    </section>
    @if($cierre->estado === 'prorroga')
    <section class="mt-5 rounded-lg border border-blue-200 bg-blue-50 p-5"><h3 class="font-bold">Apartados de la prórroga actual</h3><ul class="mt-3 space-y-2 text-sm">@foreach($cierre->prorrogas->where('ronda', $cierre->ronda) as $prorroga)<li>{{ $proyecto->guiaIntegradora->apartados->firstWhere('id', $prorroga->apartado_guia_id)?->titulo }}: {{ $prorroga->fecha_limite->format('d/m/Y H:i') }} · {{ $prorroga->fecha_limite->isFuture() ? 'Abierto' : 'Vencido' }}</li>@endforeach</ul></section>
    @endif
    @if(in_array($cierre->estado, ['pendiente', 'prorroga'], true))
    <div class="mt-5 grid gap-5 lg:grid-cols-2">
        <form method="POST" action="{{ route('docente-lider.cierres.decidir', $proyecto) }}" class="rounded-lg border bg-white p-5">@csrf
            <input type="hidden" name="decision" value="prorroga"><input type="hidden" name="ronda" value="{{ $cierre->ronda }}">
            <h3 class="font-bold">Dar una prórroga</h3><p class="mt-2 text-sm text-slate-600">Se abrirán estos apartados para entregas y revisiones de este equipo. Los demás conservarán su estado. Esta selección sustituye la prórroga actual.</p>
            <fieldset class="mt-4 space-y-2"><legend class="mb-2 text-sm font-bold">Apartados que se abrirán</legend>@foreach($proyecto->guiaIntegradora->apartados->sortBy('orden') as $apartado)<label class="flex gap-2 text-sm"><input type="checkbox" name="apartados[]" value="{{ $apartado->id }}" @checked(old('decision') === 'prorroga' && in_array($apartado->id, old('apartados', [])))><span>{{ $apartado->orden }}. {{ $apartado->titulo }}</span></label>@endforeach</fieldset>
            <label class="mt-4 block text-sm font-bold">Nueva fecha y hora límite ({{ config('app.timezone') }})<input required name="fecha_limite" type="datetime-local" value="{{ old('decision') === 'prorroga' ? old('fecha_limite') : '' }}" class="mt-2 block w-full rounded border-slate-300"></label>
            <label class="mt-4 block text-sm font-bold">Motivo<textarea required maxlength="2000" name="motivo" class="mt-2 block w-full rounded border-slate-300">{{ old('decision') === 'prorroga' ? old('motivo') : '' }}</textarea></label>
            <button class="mt-4 rounded bg-[#155AA3] px-4 py-2 font-bold text-white">Guardar prórroga</button>
        </form>
        <form method="POST" action="{{ route('docente-lider.cierres.decidir', $proyecto) }}" class="rounded-lg border bg-white p-5">@csrf
            <input type="hidden" name="decision" value="cerrar"><input type="hidden" name="ronda" value="{{ $cierre->ronda }}">
            <h3 class="font-bold">Cerrar en su estado actual</h3><p class="mt-2 text-sm text-slate-600">Se guardará una constancia de los pendientes y se bloquearán nuevas entregas y revisiones. El PDF final con todas las firmas solo se puede emitir cuando cumple todos los requisitos.</p>
            <label class="mt-4 block text-sm font-bold">Motivo de la decisión<textarea required maxlength="2000" name="motivo" class="mt-2 block w-full rounded border-slate-300">{{ old('decision') === 'cerrar' ? old('motivo') : '' }}</textarea></label>
            <label class="mt-4 flex gap-2 text-sm"><input required type="checkbox" name="confirmar_cierre" value="1"><span>Confirmo el cierre del proyecto con los pendientes indicados.</span></label>
            <button class="mt-4 rounded bg-slate-700 px-4 py-2 font-bold text-white">Registrar cierre</button>
        </form>
    </div>
    @endif
    <section class="mt-5 rounded-lg border bg-white p-5"><h3 class="font-bold">Historial de decisiones</h3>@forelse($decisiones as $decision)<article class="mt-3 border-t pt-3 text-sm"><p><strong>{{ $decision->decision === 'prorroga' ? 'Prórroga' : 'Cierre' }}</strong> · {{ \Illuminate\Support\Carbon::parse($decision->created_at)->format('d/m/Y H:i') }}</p><p class="mt-1 whitespace-pre-line">{{ $decision->motivo }}</p>@if($decision->fecha_limite)<p class="mt-1">Hasta {{ \Illuminate\Support\Carbon::parse($decision->fecha_limite)->format('d/m/Y H:i') }}</p>@endif</article>@empty<p class="mt-3 text-sm text-slate-500">Todavía no se ha registrado una decisión.</p>@endforelse</section>
</x-contenedor-aplicacion>
