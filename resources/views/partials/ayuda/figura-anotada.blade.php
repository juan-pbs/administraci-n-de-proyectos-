@php
    $imagen = $seccion['imagen'] ?? null;
    $marcas = collect($seccion['marcas'] ?? []);
    $resaltados = collect($seccion['resaltados'] ?? []);
    $caption = $seccion['caption'] ?? '';
    $tituloCaptura = $seccion['titulo_captura'] ?? null;
    $markerPrefix = 'ayuda-' . md5(($imagen ?? '') . ($seccion['id'] ?? ''));

    $palette = [
        'blue' => [
            'fill' => 'rgba(21, 82, 154, 0.12)',
            'stroke' => '#15529A',
            'label' => 'rgba(13, 55, 109, 0.96)',
            'ring' => '#B9D3F0',
        ],
        'green' => [
            'fill' => 'rgba(47, 99, 48, 0.13)',
            'stroke' => '#2F6330',
            'label' => 'rgba(36, 79, 39, 0.96)',
            'ring' => '#B7D7BD',
        ],
        'amber' => [
            'fill' => 'rgba(217, 119, 6, 0.14)',
            'stroke' => '#B45309',
            'label' => 'rgba(120, 53, 15, 0.96)',
            'ring' => '#F3D19B',
        ],
        'rose' => [
            'fill' => 'rgba(190, 18, 60, 0.12)',
            'stroke' => '#BE123C',
            'label' => 'rgba(136, 19, 55, 0.96)',
            'ring' => '#F2B8C6',
        ],
    ];
@endphp

@if($imagen)
    <figure class="mt-8" data-help-annotated-figure>
        @if($tituloCaptura)
            <p class="mb-3 text-sm font-bold text-slate-900">{{ $tituloCaptura }}</p>
        @endif

        <div class="relative overflow-hidden rounded-md border border-slate-200 bg-white shadow-sm">
            <img src="{{ $imagen }}" alt="{{ $seccion['titulo'] }}" class="block w-full">

            @if($marcas->isNotEmpty() || $resaltados->isNotEmpty())
                <svg class="pointer-events-none absolute inset-0 hidden h-full w-full md:block" viewBox="0 0 100 100" preserveAspectRatio="none" aria-hidden="true">
                    <defs>
                        @foreach(array_keys($palette) as $variant)
                            <marker id="{{ $markerPrefix }}-{{ $variant }}" markerWidth="5" markerHeight="5" refX="4.2" refY="2.5" orient="auto" markerUnits="strokeWidth">
                                <path d="M0,0 L5,2.5 L0,5 z" fill="{{ $palette[$variant]['stroke'] }}"></path>
                            </marker>
                        @endforeach
                    </defs>

                    @foreach($resaltados as $resaltado)
                        @php
                            $variant = $resaltado['variant'] ?? 'blue';
                            $colors = $palette[$variant] ?? $palette['blue'];
                        @endphp
                        <rect
                            x="{{ $resaltado['x'] ?? 0 }}"
                            y="{{ $resaltado['y'] ?? 0 }}"
                            width="{{ $resaltado['w'] ?? 0 }}"
                            height="{{ $resaltado['h'] ?? 0 }}"
                            rx="{{ $resaltado['rx'] ?? 1.4 }}"
                            fill="{{ $colors['fill'] }}"
                            stroke="{{ $colors['stroke'] }}"
                            stroke-width="0.42"
                            stroke-dasharray="{{ !empty($resaltado['dashed']) ? '1.2 1.2' : 'none' }}"
                        ></rect>
                    @endforeach

                    @foreach($marcas as $marca)
                        @php
                            $variant = $marca['variant'] ?? 'blue';
                            $colors = $palette[$variant] ?? $palette['blue'];
                        @endphp

                        @if(isset($marca['to_x'], $marca['to_y']))
                            <line
                                x1="{{ $marca['from_x'] ?? $marca['x'] ?? 0 }}"
                                y1="{{ $marca['from_y'] ?? $marca['y'] ?? 0 }}"
                                x2="{{ $marca['to_x'] }}"
                                y2="{{ $marca['to_y'] }}"
                                stroke="{{ $colors['stroke'] }}"
                                stroke-width="0.46"
                                marker-end="url(#{{ $markerPrefix }}-{{ $variant }})"
                            ></line>
                        @endif
                    @endforeach
                </svg>

                @foreach($marcas as $marca)
                    @php
                        $variant = $marca['variant'] ?? 'blue';
                        $colors = $palette[$variant] ?? $palette['blue'];
                        $translateX = $marca['translate_x'] ?? '-50%';
                        $translateY = $marca['translate_y'] ?? '-50%';
                    @endphp

                    <div
                        data-help-label
                        class="pointer-events-none absolute z-10 hidden rounded-md px-3 py-2 text-xs font-semibold text-white shadow-xl md:block"
                        style="
                            left: {{ $marca['x'] ?? 0 }}%;
                            top: {{ $marca['y'] ?? 0 }}%;
                            transform: translate({{ $translateX }}, {{ $translateY }});
                            width: {{ $marca['width'] ?? '11rem' }};
                            max-width: calc(100% - 1rem);
                            background: {{ $colors['label'] }};
                            border: 2px solid {{ $colors['ring'] }};
                            box-shadow: 0 14px 30px rgba(15, 23, 42, 0.22);
                        "
                    >
                        <div class="text-[0.62rem] font-black uppercase tracking-[0.16em] text-white/75">
                            {{ $marca['title'] ?? 'Punto clave' }}
                        </div>
                        <div class="mt-1 leading-relaxed">
                            {{ $marca['text'] ?? '' }}
                        </div>
                    </div>
                @endforeach
            @endif
        </div>

        @if($caption)
            <figcaption class="mt-2 text-xs text-slate-500">{{ $caption }}</figcaption>
        @endif

        @if($marcas->isNotEmpty())
            <div class="mt-3 grid gap-2 md:hidden">
                @foreach($marcas as $marca)
                    @php
                        $variant = $marca['variant'] ?? 'blue';
                        $colors = $palette[$variant] ?? $palette['blue'];
                    @endphp

                    <div class="rounded-md border bg-white px-4 py-3 text-sm leading-6 text-slate-700" style="border-color: {{ $colors['ring'] }};">
                        <div class="text-xs font-black uppercase tracking-[0.14em]" style="color: {{ $colors['stroke'] }};">
                            {{ $marca['title'] ?? 'Punto clave' }}
                        </div>
                        <div class="mt-1">{{ $marca['text'] ?? '' }}</div>
                    </div>
                @endforeach
            </div>
        @endif
    </figure>
@endif
