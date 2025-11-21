<x-filament-panels::page>
    <div class="mx-auto xl:min-w-[80rem] space-y-8">
        {{-- Header Principal --}}
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-slate-50 to-slate-100 dark:from-slate-900 dark:via-slate-800 dark:to-slate-900 p-8 border border-slate-200 dark:border-slate-700/50 shadow-sm dark:shadow-none">
            <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGRlZnM+PHBhdHRlcm4gaWQ9ImdyaWQiIHdpZHRoPSI2MCIgaGVpZ2h0PSI2MCIgcGF0dGVyblVuaXRzPSJ1c2VyU3BhY2VPblVzZSI+PHBhdGggZD0iTSAxMCAwIEwgMCAwIDAgMTAiIGZpbGw9Im5vbmUiIHN0cm9rZT0icmdiYSgwLDAsMCwwLjAzKSIgc3Ryb2tlLXdpZHRoPSIxIi8+PC9wYXR0ZXJuPjwvZGVmcz48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSJ1cmwoI2dyaWQpIi8+PC9zdmc+')] dark:bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGRlZnM+PHBhdHRlcm4gaWQ9ImdyaWQiIHdpZHRoPSI2MCIgaGVpZ2h0PSI2MCIgcGF0dGVyblVuaXRzPSJ1c2VyU3BhY2VPblVzZSI+PHBhdGggZD0iTSAxMCAwIEwgMCAwIDAgMTAiIGZpbGw9Im5vbmUiIHN0cm9rZT0icmdiYSgyNTUsMjU1LDI1NSwwLjAzKSIgc3Ryb2tlLXdpZHRoPSIxIi8+PC9wYXR0ZXJuPjwvZGVmcz48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSJ1cmwoI2dyaWQpIi8+PC9zdmc+')] opacity-40"></div>
            <div class="flex gap-6">
                <div class="flex-shrink-0">
                    <div class="w-16 h-16 rounded-xl bg-gradient-to-br from-slate-200 to-slate-300 dark:from-slate-700 dark:to-slate-800 flex items-center justify-center border border-slate-300 dark:border-slate-600/50 shadow-lg shadow-black/5 dark:shadow-black/20">
                        <x-heroicon-o-eye class="w-8 h-8 text-slate-700 dark:text-slate-300"/>
                    </div>
                </div>
                <div class="flex-1 text-center ">
                    <h1 class="text-3xl font-bold pr-20 text-slate-900 dark:text-white mb-2 tracking-tight">Sistema de Análisis Esteganografía</h1>
                    <p class="text-slate-600 pr-20 dark:text-slate-400 text-base leading-relaxed">
                        Analiza archivos utilizando patrones complejos para detectar contenido oculto.
                    </p>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-3 px-1">
            <div class="w-10 h-10 rounded-lg bg-slate-100 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 flex items-center justify-center">
                <x-heroicon-o-magnifying-glass class="w-5 h-5 text-slate-600 dark:text-slate-400"/>
            </div>
            <div>
                <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Análisis Forense</h2>
                <p class="text-xs text-slate-600 dark:text-slate-400">Detecta presencia de esteganografía LSB</p>
            </div>
        </div>

        <div>
            <form wire:submit="analyzeFile">
                {{ $this->analyzeForm }}
            </form>

            @if($analyzeResult)
                <div class="border-t border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900/50 p-6">
                    {{-- Veredicto Principal --}}
                    <div class="rounded-xl border-2 @if($analyzeResult['is_infected']) border-red-200 dark:border-red-900/50 bg-gradient-to-br from-red-50 to-rose-50 dark:from-red-950/30 dark:to-rose-950/30 @else border-emerald-200 dark:border-emerald-900/50 bg-gradient-to-br from-emerald-50 to-green-50 dark:from-emerald-950/30 dark:to-green-950/30 @endif p-6 mb-6">
                        <div class="flex items-start gap-4">
                            <div class="flex-shrink-0">
                                <div class="w-14 h-14 rounded-xl @if($analyzeResult['is_infected']) bg-red-100 dark:bg-red-500/10 border border-red-200 dark:border-red-500/20 @else bg-emerald-100 dark:bg-emerald-500/10 border border-emerald-200 dark:border-emerald-500/20 @endif flex items-center justify-center">
                                    @if($analyzeResult['is_infected'])
                                        <x-heroicon-o-exclamation-triangle class="w-7 h-7 text-red-600 dark:text-red-400"/>
                                    @else
                                        <x-heroicon-o-shield-check class="w-7 h-7 text-emerald-600 dark:text-emerald-400"/>
                                    @endif
                                </div>
                            </div>
                            <div class="flex-1">
                                <h3 class="text-xl font-bold @if($analyzeResult['is_infected']) text-red-900 dark:text-red-100 @else text-emerald-900 dark:text-emerald-100 @endif mb-1">
                                    {{ $analyzeResult['verdict'] }}
                                </h3>
                                <p class="text-sm @if($analyzeResult['is_infected']) text-red-700 dark:text-red-300 @else text-emerald-700 dark:text-emerald-300 @endif mb-4">
                                    Estado del archivo: <span class="font-semibold">{{ $analyzeResult['is_infected'] ? 'INFECTADO' : 'LIMPIO' }}</span>
                                </p>

                                <div class="grid grid-cols-2 gap-3">
                                    <div class="rounded-lg bg-white/60 dark:bg-black/20 border @if($analyzeResult['is_infected']) border-red-200/50 dark:border-red-800/30 @else border-emerald-200/50 dark:border-emerald-800/30 @endif p-3">
                                        <div class="text-xs font-medium @if($analyzeResult['is_infected']) text-red-700 dark:text-red-300 @else text-emerald-700 dark:text-emerald-300 @endif mb-1">Confianza</div>
                                        <div class="text-2xl font-bold @if($analyzeResult['is_infected']) text-red-900 dark:text-red-100 @else text-emerald-900 dark:text-emerald-100 @endif">
                                            {{ round($analyzeResult['confidence'], 1) }}%
                                        </div>
                                    </div>

                                    <div class="rounded-lg bg-white/60 dark:bg-black/20 border @if($analyzeResult['is_infected']) border-red-200/50 dark:border-red-800/30 @else border-emerald-200/50 dark:border-emerald-800/30 @endif p-3">
                                        <div class="text-xs font-medium @if($analyzeResult['is_infected']) text-red-700 dark:text-red-300 @else text-emerald-700 dark:text-emerald-300 @endif mb-1">Probabilidad LSB</div>
                                        <div class="text-2xl font-bold @if($analyzeResult['is_infected']) text-red-900 dark:text-red-100 @else text-emerald-900 dark:text-emerald-100 @endif">
                                            {{ round($analyzeResult['lsb_probability'], 1) }}%
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Método de Detección --}}
                    @if(isset($analyzeResult['summary']['detection_method']))
                        <div class="rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-4 mb-6">
                            <div class="flex items-center gap-2 mb-2">
                                <x-heroicon-o-cpu-chip class="w-4 h-4 text-slate-600 dark:text-slate-400"/>
                                <h4 class="text-sm font-semibold text-slate-900 dark:text-white">Método de Detección</h4>
                            </div>
                            <p class="text-sm text-slate-600 dark:text-slate-400">
                                {{ $analyzeResult['summary']['detection_method'] }}
                            </p>

                            @if(isset($analyzeResult['summary']['message_found']) && $analyzeResult['summary']['message_found'])
                                <div class="mt-3 p-3 rounded-lg bg-red-50 dark:bg-red-950/30 border border-red-200 dark:border-red-900/50">
                                    <div class="text-xs font-medium text-red-700 dark:text-red-300 mb-1">Mensaje Extraído</div>
                                    @if(isset($analyzeResult['summary']['message_preview']))
                                        <p class="text-sm font-mono text-red-900 dark:text-red-100 break-words">
                                            "{{ $analyzeResult['summary']['message_preview'] }}"
                                        </p>
                                    @endif
                                    @if(isset($analyzeResult['summary']['message_length']))
                                        <p class="text-xs text-red-600 dark:text-red-400 mt-1">
                                            Longitud: {{ $analyzeResult['summary']['message_length'] }} caracteres
                                        </p>
                                    @endif
                                </div>
                            @endif
                        </div>
                    @endif

                    {{-- Métricas de Detección --}}
                    @if(isset($analyzeResult['metrics']) && count($analyzeResult['metrics']) > 0)
                        <div class="mb-6">
                            <div class="flex items-center justify-between mb-3">
                                <h4 class="text-sm font-semibold text-slate-900 dark:text-white flex items-center gap-2">
                                    <x-heroicon-o-beaker class="w-4 h-4 text-slate-600 dark:text-slate-400"/>
                                    Métricas de Análisis
                                </h4>
                                <span class="text-xs font-medium text-slate-600 dark:text-slate-400 bg-slate-100 dark:bg-slate-800 px-2 py-1 rounded">
                                            {{ count($analyzeResult['metrics']) }} métricas
                                        </span>
                            </div>

                            @php
                                $criticalMetrics = collect($analyzeResult['metrics'])->filter(fn($m) => isset($m['category']) && in_array($m['category'], ['critical', 'confirmation']));
                                $secondaryMetrics = collect($analyzeResult['metrics'])->filter(fn($m) => isset($m['category']) && $m['category'] === 'secondary');
                                $supportMetrics = collect($analyzeResult['metrics'])->filter(fn($m) => !isset($m['category']) || $m['category'] === 'support');
                            @endphp

                            {{-- Métricas Críticas --}}
                            @if($criticalMetrics->isNotEmpty())
                                <div class="mb-4">
                                    <div class="text-xs font-semibold text-slate-700 dark:text-slate-300 mb-2 uppercase tracking-wide">Métricas Críticas</div>
                                    <div class="space-y-2">
                                        @foreach($criticalMetrics as $metric)
                                            <details class="group rounded-lg border @if($metric['is_suspicious']) border-red-200 dark:border-red-900/50 bg-red-50 dark:bg-red-950/20 @else border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 @endif overflow-hidden">
                                                <summary class="flex items-center justify-between p-3.5 cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-800/50 transition select-none">
                                                    <div class="flex items-center gap-2.5 flex-1 min-w-0">
                                                        @if($metric['is_suspicious'])
                                                            <x-heroicon-m-exclamation-circle class="w-4 h-4 text-red-600 dark:text-red-400 flex-shrink-0"/>
                                                        @else
                                                            <x-heroicon-m-check-circle class="w-4 h-4 text-slate-400 dark:text-slate-500 flex-shrink-0"/>
                                                        @endif
                                                        <span class="text-sm font-medium @if($metric['is_suspicious']) text-red-900 dark:text-red-100 @else text-slate-900 dark:text-white @endif truncate">
                                                                    {{ $metric['name'] }}
                                                                </span>
                                                    </div>
                                                    <div class="flex items-center gap-2 flex-shrink-0 ml-2">
                                                        @if(isset($metric['severity']))
                                                            <span class="text-xs px-2 py-0.5 rounded font-medium
                                                                        @if($metric['severity'] === 'high') bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300
                                                                        @elseif($metric['severity'] === 'medium') bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300
                                                                        @else bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 @endif">
                                                                        {{ strtoupper($metric['severity']) }}
                                                                    </span>
                                                        @endif
                                                        <x-heroicon-m-chevron-down class="w-4 h-4 text-slate-400 dark:text-slate-500 transition-transform group-open:rotate-180"/>
                                                    </div>
                                                </summary>

                                                <div class="px-3.5 pb-3.5 pt-0 border-t @if($metric['is_suspicious']) border-red-200 dark:border-red-900/50 @else border-slate-200 dark:border-slate-800 @endif">
                                                    <p class="text-xs @if($metric['is_suspicious']) text-red-700 dark:text-red-300 @else text-slate-600 dark:text-slate-400 @endif leading-relaxed mb-2">
                                                        {{ $metric['explanation'] }}
                                                    </p>
                                                    @if(isset($metric['value']))
                                                        <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700">
                                                            <span class="text-xs font-medium text-slate-600 dark:text-slate-400">Valor:</span>
                                                            <span class="text-xs font-mono font-bold text-slate-900 dark:text-white">
                                                                        {{ is_numeric($metric['value']) ? round($metric['value'], 2) : $metric['value'] }}
                                                                    </span>
                                                        </div>
                                                    @endif
                                                </div>
                                            </details>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            {{-- Métricas Secundarias --}}
                            @if($secondaryMetrics->isNotEmpty())
                                <div class="mb-4">
                                    <div class="text-xs font-semibold text-slate-700 dark:text-slate-300 mb-2 uppercase tracking-wide">Métricas Secundarias</div>
                                    <div class="space-y-2">
                                        @foreach($secondaryMetrics as $metric)
                                            <details class="group rounded-lg border @if($metric['is_suspicious']) border-amber-200 dark:border-amber-900/50 bg-amber-50 dark:bg-amber-950/20 @else border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 @endif overflow-hidden">
                                                <summary class="flex items-center justify-between p-3.5 cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-800/50 transition select-none">
                                                    <div class="flex items-center gap-2.5 flex-1 min-w-0">
                                                        @if($metric['is_suspicious'])
                                                            <x-heroicon-m-exclamation-circle class="w-4 h-4 text-amber-600 dark:text-amber-400 flex-shrink-0"/>
                                                        @else
                                                            <x-heroicon-m-check-circle class="w-4 h-4 text-slate-400 dark:text-slate-500 flex-shrink-0"/>
                                                        @endif
                                                        <span class="text-sm font-medium @if($metric['is_suspicious']) text-amber-900 dark:text-amber-100 @else text-slate-900 dark:text-white @endif truncate">
                                                                    {{ $metric['name'] }}
                                                                </span>
                                                    </div>
                                                    <div class="flex items-center gap-2 flex-shrink-0 ml-2">
                                                        @if(isset($metric['severity']))
                                                            <span class="text-xs px-2 py-0.5 rounded font-medium
                                                                        @if($metric['severity'] === 'high') bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300
                                                                        @elseif($metric['severity'] === 'medium') bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300
                                                                        @else bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 @endif">
                                                                        {{ strtoupper($metric['severity']) }}
                                                                    </span>
                                                        @endif
                                                        <x-heroicon-m-chevron-down class="w-4 h-4 text-slate-400 dark:text-slate-500 transition-transform group-open:rotate-180"/>
                                                    </div>
                                                </summary>

                                                <div class="px-3.5 pb-3.5 pt-0 border-t @if($metric['is_suspicious']) border-amber-200 dark:border-amber-900/50 @else border-slate-200 dark:border-slate-800 @endif">
                                                    <p class="text-xs @if($metric['is_suspicious']) text-amber-700 dark:text-amber-300 @else text-slate-600 dark:text-slate-400 @endif leading-relaxed mb-2">
                                                        {{ $metric['explanation'] }}
                                                    </p>
                                                    @if(isset($metric['value']))
                                                        <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700">
                                                            <span class="text-xs font-medium text-slate-600 dark:text-slate-400">Valor:</span>
                                                            <span class="text-xs font-mono font-bold text-slate-900 dark:text-white">
                                                                        {{ is_numeric($metric['value']) ? round($metric['value'], 2) : $metric['value'] }}
                                                                    </span>
                                                        </div>
                                                    @endif
                                                </div>
                                            </details>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            {{-- Métricas de Soporte --}}
                            @if($supportMetrics->isNotEmpty())
                                <div>
                                    <details class="group">
                                        <summary class="text-xs font-semibold text-slate-700 dark:text-slate-300 mb-2 uppercase tracking-wide cursor-pointer hover:text-slate-900 dark:hover:text-white transition flex items-center gap-2">
                                            <x-heroicon-m-chevron-right class="w-3 h-3 transition-transform group-open:rotate-90"/>
                                            Métricas de Soporte ({{ $supportMetrics->count() }})
                                        </summary>
                                        <div class="space-y-2 mt-2">
                                            @foreach($supportMetrics as $metric)
                                                <details class="group/item rounded-lg border @if($metric['is_suspicious']) border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/50 @else border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 @endif overflow-hidden">
                                                    <summary class="flex items-center justify-between p-3 cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-800/50 transition select-none">
                                                        <div class="flex items-center gap-2.5 flex-1 min-w-0">
                                                            @if($metric['is_suspicious'])
                                                                <x-heroicon-m-information-circle class="w-4 h-4 text-slate-600 dark:text-slate-400 flex-shrink-0"/>
                                                            @else
                                                                <x-heroicon-m-check-circle class="w-4 h-4 text-slate-400 dark:text-slate-500 flex-shrink-0"/>
                                                            @endif
                                                            <span class="text-sm font-medium text-slate-900 dark:text-white truncate">
                                                                        {{ $metric['name'] }}
                                                                    </span>
                                                        </div>
                                                        <x-heroicon-m-chevron-down class="w-4 h-4 text-slate-400 dark:text-slate-500 transition-transform group-open/item:rotate-180"/>
                                                    </summary>

                                                    <div class="px-3 pb-3 pt-0 border-t border-slate-200 dark:border-slate-800">
                                                        <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed mb-2">
                                                            {{ $metric['explanation'] }}
                                                        </p>
                                                        @if(isset($metric['value']))
                                                            <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700">
                                                                <span class="text-xs font-medium text-slate-600 dark:text-slate-400">Valor:</span>
                                                                <span class="text-xs font-mono font-bold text-slate-900 dark:text-white">
                                                                            {{ is_numeric($metric['value']) ? round($metric['value'], 2) : $metric['value'] }}
                                                                        </span>
                                                            </div>
                                                        @endif
                                                    </div>
                                                </details>
                                            @endforeach
                                        </div>
                                    </details>
                                </div>
                            @endif
                        </div>
                    @endif

                    {{-- Resumen Técnico Completo --}}
                    @if(isset($analyzeResult['summary']))
                        <div class="rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5">
                            <h4 class="text-sm font-semibold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
                                <x-heroicon-o-information-circle class="w-4 h-4 text-slate-600 dark:text-slate-400"/>
                                Resumen Técnico del Análisis
                            </h4>

                            <div class="space-y-3">
                                @foreach($analyzeResult['summary'] as $key => $value)
                                    @if(!is_array($value) && !in_array($key, ['message_preview', 'message_found', 'detection_method', 'message_length']))
                                        <div class="flex justify-between items-start py-2 border-b border-slate-100 dark:border-slate-800 last:border-0">
                                            <dt class="text-xs font-medium text-slate-600 dark:text-slate-400 flex-shrink-0 mr-4">
                                                {{ ucwords(str_replace('_', ' ', $key)) }}
                                            </dt>
                                            <dd class="text-xs font-semibold text-slate-900 dark:text-white text-right">
                                                @if(is_bool($value))
                                                    <span class="inline-flex items-center gap-1">
                                                                @if($value)
                                                            <x-heroicon-m-check-circle class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400"/>
                                                            <span class="text-emerald-700 dark:text-emerald-300">Sí</span>
                                                        @else
                                                            <x-heroicon-m-x-circle class="w-3.5 h-3.5 text-slate-400"/>
                                                            <span class="text-slate-600 dark:text-slate-400">No</span>
                                                        @endif
                                                            </span>
                                                @else
                                                    <span class="font-mono">{{ $value }}</span>
                                                @endif
                                            </dd>
                                        </div>
                                    @endif
                                @endforeach

                                {{-- Recomendación especial --}}
                                @if(isset($analyzeResult['summary']['recommendation']))
                                    <div class="mt-4 pt-4 border-t border-slate-200 dark:border-slate-700">
                                        <div class="flex items-start gap-2">
                                            <x-heroicon-o-light-bulb class="w-4 h-4 text-amber-600 dark:text-amber-400 flex-shrink-0 mt-0.5"/>
                                            <div>
                                                <div class="text-xs font-semibold text-slate-900 dark:text-white mb-1">Recomendación</div>
                                                <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">
                                                    {{ $analyzeResult['summary']['recommendation'] }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                @endif

                                {{-- Nivel de confianza visual --}}
                                @if(isset($analyzeResult['summary']['confidence_level']))
                                    <div class="mt-4 pt-4 border-t border-slate-200 dark:border-slate-700">
                                        <div class="text-xs font-medium text-slate-600 dark:text-slate-400 mb-2">Nivel de Confianza</div>
                                        <div class="flex items-center gap-2">
                                            @php
                                                $confidenceLevel = $analyzeResult['summary']['confidence_level'];
                                                $confidenceColor = match($confidenceLevel) {
                                                    'Very High' => 'bg-emerald-500 dark:bg-emerald-600',
                                                    'High' => 'bg-blue-500 dark:bg-blue-600',
                                                    'Medium' => 'bg-amber-500 dark:bg-amber-600',
                                                    default => 'bg-slate-500 dark:bg-slate-600'
                                                };
                                            @endphp
                                            <div class="flex-1 bg-slate-200 dark:bg-slate-800 rounded-full h-2">
                                                <div class="{{ $confidenceColor }} h-full rounded-full transition-all duration-500"
                                                     style="width: {{ $analyzeResult['confidence'] }}%">
                                                </div>
                                            </div>
                                            <span class="text-xs font-bold text-slate-900 dark:text-white">
                                                        {{ $confidenceLevel }}
                                                    </span>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </div>
    {{-- Script para copiar al portapapeles --}}
    @script
    <script>
        $wire.on('copy-to-clipboard', (data) => {
            navigator.clipboard.writeText(data.text).then(() => {
                new FilamentNotification()
                    .title('Copiado al portapapeles')
                    .success()
                    .send();
            });
        });
    </script>
    @endscript

    {{-- Estilos personalizados --}}
    <style>
        .custom-scrollbar::-webkit-scrollbar {
            width: 5px;
            height: 5px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: rgba(148, 163, 184, 0.3);
            border-radius: 10px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: rgba(148, 163, 184, 0.5);
        }
        @media (prefers-color-scheme: dark) {
            .custom-scrollbar::-webkit-scrollbar-thumb {
                background: rgba(71, 85, 105, 0.5);
            }
            .custom-scrollbar::-webkit-scrollbar-thumb:hover {
                background: rgba(71, 85, 105, 0.7);
            }
        }

        details > summary {
            list-style: none;
        }
        details > summary::-webkit-details-marker {
            display: none;
        }
    </style>
</x-filament-panels::page>
