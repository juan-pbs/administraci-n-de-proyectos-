<x-contenedor-aplicacion
    title="{{ $panel['title'] }} | Administración de proyectos"
    active="dashboard"
    :navegacion="$navegacion"
    :role-name="$roleName"
>
    <section class="border-b border-slate-200 pb-5">
        <p class="text-sm font-semibold uppercase tracking-[0.18em] text-[#21A366]">{{ $panel['eyebrow'] }}</p>
        <div class="mt-3 flex flex-col gap-3 xl:flex-row xl:items-end xl:justify-between">
            <div class="min-w-0">
                <h2 class="text-2xl font-bold text-[#0D376D]">{{ $panel['title'] }}</h2>
                <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">{{ $panel['description'] }}</p>
            </div>
            <span class="inline-flex w-fit rounded-md bg-[#EAF7EF] px-3 py-2 text-sm font-semibold text-[#0F7D47]">{{ $panel['badge'] }}</span>
        </div>
    </section>

    <section class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($panel['stats'] as $stat)
            <article class="rounded-md border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">{{ $stat['label'] }}</p>
                <p class="mt-2 text-2xl font-bold text-[#0D376D]">{{ $stat['value'] }}</p>
            </article>
        @endforeach
    </section>

    <section class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        @foreach ($panel['modules'] as $module)
            <a href="{{ route('modulos.show', $module['route']) }}" class="rounded-md border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-[#15529A] hover:shadow-md">
                <div class="flex items-start justify-between gap-3">
                    <h3 class="font-bold text-slate-900">{{ $module['title'] }}</h3>
                    <span class="rounded-md bg-slate-100 px-2 py-1 text-[11px] font-semibold uppercase tracking-wide text-slate-500">Módulo</span>
                </div>
                <p class="mt-3 text-sm leading-6 text-slate-600">{{ $module['description'] }}</p>
                <p class="mt-5 text-xs font-semibold text-[#15529A]">{{ $module['status'] }}</p>
            </a>
        @endforeach
    </section>

    <section class="mt-5 grid gap-4 lg:grid-cols-3">
        <article class="rounded-md border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Matrícula</p>
            <p class="mt-2 text-xl font-bold text-[#0D376D]">{{ auth()->user()->matricula }}</p>
        </article>

        <article class="rounded-md border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Rol activo</p>
            <p class="mt-2 text-xl font-bold text-[#0D376D]">{{ $roleName }}</p>
        </article>

        <article class="rounded-md border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Estado</p>
            <p class="mt-2 text-xl font-bold text-[#0F7D47]">{{ ucfirst(auth()->user()->estado) }}</p>
        </article>
    </section>
</x-contenedor-aplicacion>
