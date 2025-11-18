<x-filament::page>
    <div class="grid gap-6 md:grid-cols-1 lg:grid-cols-3">

        {{-- SECCIÓN 1: INFECTAR --}}
        <div class="space-y-6">
            <form wire:submit="embedMessage">
                {{ $this->embedForm }}
            </form>

            @if($embedResult)
                <x-filament::section>
                    <x-slot name="heading">
                        <div class="flex items-center gap-2">
                            <x-heroicon-o-check-circle class="w-6 h-6 text-success-500"/>
                            <span>Archivo Infectado</span>
                        </div>
                    </x-slot>

                    @php
                        $result = json_decode($embedResult, true);
                    @endphp

                    <div class="space-y-4">
                        {{-- Preview del archivo --}}
                        <div class="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700">
                            @if(str_ends_with($result['file'], '.wav'))
                                <audio controls class="w-full">
                                    <source src="{{ $result['file'] }}" type="audio/wav">
                                </audio>
                            @else
                                <img src="{{ $result['file'] }}" alt="Infected File" class="w-full h-auto">
                            @endif
                        </div>

                        {{-- Estadísticas --}}
                        <dl class="grid grid-cols-2 gap-4">
                            <div class="rounded-lg bg-gray-50 dark:bg-gray-800 p-3">
                                <dt class="text-xs text-gray-500 dark:text-gray-400">Tamaño del Mensaje</dt>
                                <dd class="text-lg font-semibold text-gray-900 dark:text-white">{{ $result['payload_size'] }} bytes</dd>
                            </div>
                            <div class="rounded-lg bg-gray-50 dark:bg-gray-800 p-3">
                                <dt class="text-xs text-gray-500 dark:text-gray-400">Capacidad Usada</dt>
                                <dd class="text-lg font-semibold text-gray-900 dark:text-white">{{ $result['capacity_used'] }}%</dd>
                            </div>
                        </dl>

                        {{-- Botón de descarga --}}
                        <x-filament::button
                            href="{{ $result['file'] }}"
                            download
                            color="success"
                            icon="heroicon-o-arrow-down-tray"
                            class="w-full"
                        >
                            Descargar Archivo Infectado
                        </x-filament::button>
                    </div>
                </x-filament::section>
            @endif
        </div>

        {{-- SECCIÓN 2: EXTRAER --}}
        <div class="space-y-6">
            <form wire:submit="extractMessage">
                {{ $this->extractForm }}
            </form>

            @if($extractResult)
                <x-filament::section>
                    <x-slot name="heading">
                        <div class="flex items-center gap-2">
                            <x-heroicon-o-eye class="w-6 h-6 text-success-500"/>
                            <span>Mensaje Extraído</span>
                        </div>
                    </x-slot>

                    @php
                        $result = json_decode($extractResult, true);
                    @endphp

                    <div class="space-y-4">
                        <div class="rounded-lg bg-gray-50 dark:bg-gray-800 p-4">
                            <p class="text-sm font-mono text-gray-700 dark:text-gray-300 whitespace-pre-wrap break-words">
                                {{ $result['message'] ?: 'No se encontró mensaje' }}
                            </p>
                        </div>

                        <div class="flex items-center justify-between text-sm text-gray-500 dark:text-gray-400">
                            <span>Longitud: {{ $result['length'] }} caracteres</span>
                            <x-filament::button
                                size="sm"
                                color="gray"
                                icon="heroicon-o-clipboard"
                                wire:click="$dispatch('copy-to-clipboard', { text: '{{ addslashes($result['message']) }}' })"
                            >
                                Copiar
                            </x-filament::button>
                        </div>
                    </div>
                </x-filament::section>
            @endif
        </div>

        {{-- SECCIÓN 3: ANALIZAR --}}
        <div class="space-y-6">
            <form wire:submit="analyzeFile">
                {{ $this->analyzeForm }}
            </form>

            @if($analyzeResult)
                <x-filament::section>
                    <x-slot name="heading">
                        <div class="flex items-center gap-2">
                            <x-heroicon-o-magnifying-glass class="w-6 h-6 text-warning-500"/>
                            <span>Resultado del Análisis</span>
                        </div>
                    </x-slot>

                    <div class="space-y-4">
                        {{-- Veredicto --}}
                        <div class="rounded-lg p-4 @if($analyzeResult['is_infected']) bg-danger-50 dark:bg-danger-950 @else bg-success-50 dark:bg-success-950 @endif">
                            <div class="flex items-center gap-3 mb-2">
                                @if($analyzeResult['is_infected'])
                                    <x-heroicon-o-exclamation-triangle class="w-8 h-8 text-danger-600 dark:text-danger-400"/>
                                @else
                                    <x-heroicon-o-shield-check class="w-8 h-8 text-success-600 dark:text-success-400"/>
                                @endif
                                <div>
                                    <h3 class="text-lg font-bold @if($analyzeResult['is_infected']) text-danger-700 dark:text-danger-300 @else text-success-700 dark:text-success-300 @endif">
                                        {{ $analyzeResult['verdict'] }}
                                    </h3>
                                    <p class="text-sm @if($analyzeResult['is_infected']) text-danger-600 dark:text-danger-400 @else text-success-600 dark:text-success-400 @endif">
                                        Estado: {{ $analyzeResult['is_infected'] ? 'INFECTADA' : 'LIMPIA' }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        {{-- Métricas principales --}}
                        <div class="grid grid-cols-2 gap-3">
                            <div class="rounded-lg bg-gray-50 dark:bg-gray-800 p-3 text-center">
                                <dt class="text-xs text-gray-500 dark:text-gray-400 mb-1">Confianza</dt>
                                <dd class="text-2xl font-bold text-gray-900 dark:text-white">{{ round($analyzeResult['confidence'], 1) }}%</dd>
                            </div>
                            <div class="rounded-lg bg-gray-50 dark:bg-gray-800 p-3 text-center">
                                <dt class="text-xs text-gray-500 dark:text-gray-400 mb-1">Prob. LSB</dt>
                                <dd class="text-2xl font-bold text-gray-900 dark:text-white">{{ round($analyzeResult['lsb_probability'], 1) }}%</dd>
                            </div>
                        </div>

                        {{-- Métricas detalladas --}}
                        @if(isset($analyzeResult['metrics']) && count($analyzeResult['metrics']) > 0)
                            <div class="space-y-2">
                                <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Métricas de Detección</h4>
                                <div class="space-y-2 max-h-96 overflow-y-auto">
                                    @foreach($analyzeResult['metrics'] as $metric)
                                        <div class="rounded-lg border @if($metric['is_suspicious']) border-danger-200 dark:border-danger-800 bg-danger-50 dark:bg-danger-950 @else border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 @endif p-3">
                                            <div class="flex items-start justify-between mb-1">
                                                <span class="text-sm font-medium @if($metric['is_suspicious']) text-danger-700 dark:text-danger-300 @else text-gray-700 dark:text-gray-300 @endif">
                                                    {{ $metric['name'] }}
                                                </span>
                                                @if(isset($metric['severity']))
                                                    <span class="text-xs px-2 py-0.5 rounded-full
                                                        @if($metric['severity'] === 'high') bg-danger-100 text-danger-700 dark:bg-danger-900 dark:text-danger-300
                                                        @elseif($metric['severity'] === 'medium') bg-warning-100 text-warning-700 dark:bg-warning-900 dark:text-warning-300
                                                        @else bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300 @endif">
                                                        {{ strtoupper($metric['severity']) }}
                                                    </span>
                                                @endif
                                            </div>
                                            <p class="text-xs text-gray-600 dark:text-gray-400">{{ $metric['explanation'] }}</p>
                                            @if(isset($metric['value']))
                                                <div class="mt-2 text-xs font-mono text-gray-500 dark:text-gray-500">
                                                    Valor: {{ is_numeric($metric['value']) ? round($metric['value'], 2) : $metric['value'] }}
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        {{-- Resumen --}}
                        @if(isset($analyzeResult['summary']))
                            <div class="rounded-lg bg-blue-50 dark:bg-blue-950 border border-blue-200 dark:border-blue-800 p-3">
                                <h4 class="text-sm font-semibold text-blue-700 dark:text-blue-300 mb-2">📊 Resumen</h4>
                                <dl class="space-y-1 text-xs text-blue-600 dark:text-blue-400">
                                    @foreach($analyzeResult['summary'] as $key => $value)
                                        @if(!is_array($value))
                                            <div class="flex justify-between">
                                                <dt class="font-medium">{{ ucfirst(str_replace('_', ' ', $key)) }}:</dt>
                                                <dd class="font-mono">{{ is_bool($value) ? ($value ? 'Sí' : 'No') : $value }}</dd>
                                            </div>
                                        @endif
                                    @endforeach
                                </dl>
                            </div>
                        @endif
                    </div>
                </x-filament::section>
            @endif
        </div>
    </div>

    @script
    <script>
        $wire.on('copy-to-clipboard', (data) => {
            navigator.clipboard.writeText(data.text);
            new FilamentNotification()
                .title('Copiado al portapapeles')
                .success()
                .send();
        });
    </script>
    @endscript
</x-filament::page>
