<x-filament-panels::page>
    <div class="mx-auto xl:min-w-[80rem] max-w-[60rem] space-y-8">
        {{-- Header Principal --}}
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-slate-50 to-slate-100 dark:from-slate-900 dark:via-slate-800 dark:to-slate-900 p-8 border border-slate-200 dark:border-slate-700/50 shadow-sm dark:shadow-none">
            <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGRlZnM+PHBhdHRlcm4gaWQ9ImdyaWQiIHdpZHRoPSI2MCIgaGVpZ2h0PSI2MCIgcGF0dGVyblVuaXRzPSJ1c2VyU3BhY2VPblVzZSI+PHBhdGggZD0iTSAxMCAwIEwgMCAwIDAgMTAiIGZpbGw9Im5vbmUiIHN0cm9rZT0icmdiYSgwLDAsMCwwLjAzKSIgc3Ryb2tlLXdpZHRoPSIxIi8+PC9wYXR0ZXJuPjwvZGVmcz48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSJ1cmwoI2dyaWQpIi8+PC9zdmc+')] dark:bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGRlZnM+PHBhdHRlcm4gaWQ9ImdyaWQiIHdpZHRoPSI2MCIgaGVpZ2h0PSI2MCIgcGF0dGVyblVuaXRzPSJ1c2VyU3BhY2VPblVzZSI+PHBhdGggZD0iTSAxMCAwIEwgMCAwIDAgMTAiIGZpbGw9Im5vbmUiIHN0cm9rZT0icmdiYSgyNTUsMjU1LDI1NSwwLjAzKSIgc3Ryb2tlLXdpZHRoPSIxIi8+PC9wYXR0ZXJuPjwvZGVmcz48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSJ1cmwoI2dyaWQpIi8+PC9zdmc+')] opacity-40"></div>
            <div class="flex gap-6">
                <div class="flex-shrink-0">
                    <div class="w-16 h-16 rounded-xl bg-gradient-to-br from-slate-200 to-slate-300 dark:from-slate-700 dark:to-slate-800 flex items-center justify-center border border-slate-300 dark:border-slate-600/50 shadow-lg shadow-black/5 dark:shadow-black/20">
                        <x-heroicon-o-eye class="w-8 h-8 text-slate-700 dark:text-slate-300"/>
                    </div>
                </div>
                <div class="flex-1 text-center">
                    <h1 class="text-3xl font-bold pr-20 text-slate-900 dark:text-white mb-2 tracking-tight">Sistema de Análisis Esteganografía</h1>
                    <p class="text-slate-600 pr-20 dark:text-slate-400 text-base leading-relaxed">
                        Detecta mensajes de texto y documentos ocultos en imágenes y audio
                    </p>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-3 px-1">
            <div class="w-10 h-10 rounded-lg bg-slate-100 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 flex items-center justify-center">
                <x-heroicon-o-magnifying-glass class="w-5 h-5 text-slate-600 dark:text-slate-400"/>
            </div>
            <div>
                <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Análisis Forense Avanzado</h2>
                <p class="text-xs text-slate-600 dark:text-slate-400">Detecta mensajes de texto y documentos completos ocultos</p>
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

                    {{-- NUEVO: Información del Documento Encontrado --}}
                    @if(isset($analyzeResult['summary']['document_found']) && $analyzeResult['summary']['document_found'])
                        <div class="rounded-lg border-2 border-purple-200 dark:border-purple-900/50 bg-gradient-to-br from-purple-50 to-violet-50 dark:from-purple-950/30 dark:to-violet-950/30 p-5 mb-6">
                            <div class="flex items-start gap-3 mb-4">
                                <x-heroicon-o-document-text class="w-6 h-6 text-purple-600 dark:text-purple-400 flex-shrink-0"/>
                                <div class="flex-1">
                                    <h4 class="text-lg font-bold text-purple-900 dark:text-purple-100 mb-1">
                                        📄 Documento Oculto Detectado
                                    </h4>
                                    @if(isset($analyzeResult['summary']['is_password_protected']) && $analyzeResult['summary']['is_password_protected'])
                                        <p class="text-sm text-purple-700 dark:text-purple-300 mb-3">
                                            🔒 Este documento está protegido con contraseña
                                        </p>
                                    @else
                                        <p class="text-sm text-purple-700 dark:text-purple-300 mb-3">
                                            El archivo contiene un documento completo embebido
                                        </p>
                                    @endif
                                </div>
                            </div>

                            @if(!isset($analyzeResult['summary']['is_password_protected']) || !$analyzeResult['summary']['is_password_protected'])
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
                                    @if(isset($analyzeResult['summary']['document_filename']))
                                        <div class="rounded-lg bg-white/60 dark:bg-black/20 border border-purple-200/50 dark:border-purple-800/30 p-3">
                                            <div class="text-xs font-medium text-purple-700 dark:text-purple-300 mb-1">Nombre del Archivo</div>
                                            <div class="text-sm font-bold text-purple-900 dark:text-purple-100 truncate" title="{{ $analyzeResult['summary']['document_filename'] }}">
                                                {{ $analyzeResult['summary']['document_filename'] }}
                                            </div>
                                        </div>
                                    @endif

                                    @if(isset($analyzeResult['summary']['document_size']))
                                        <div class="rounded-lg bg-white/60 dark:bg-black/20 border border-purple-200/50 dark:border-purple-800/30 p-3">
                                            <div class="text-xs font-medium text-purple-700 dark:text-purple-300 mb-1">Tamaño</div>
                                            <div class="text-sm font-bold text-purple-900 dark:text-purple-100">
                                                {{ number_format($analyzeResult['summary']['document_size'] / 1024, 2) }} KB
                                            </div>
                                        </div>
                                    @endif

                                    @if(isset($analyzeResult['summary']['document_mime_type']))
                                        <div class="rounded-lg bg-white/60 dark:bg-black/20 border border-purple-200/50 dark:border-purple-800/30 p-3">
                                            <div class="text-xs font-medium text-purple-700 dark:text-purple-300 mb-1">Tipo MIME</div>
                                            <div class="text-sm font-bold text-purple-900 dark:text-purple-100 truncate" title="{{ $analyzeResult['summary']['document_mime_type'] }}">
                                                {{ $analyzeResult['summary']['document_mime_type'] }}
                                            </div>
                                        </div>
                                    @endif

                                    @if(isset($analyzeResult['summary']['user_id']))
                                        <div class="rounded-lg bg-white/60 dark:bg-black/20 border border-purple-200/50 dark:border-purple-800/30 p-3">
                                            <div class="text-xs font-medium text-purple-700 dark:text-purple-300 mb-1">Usuario</div>
                                            <div class="text-sm font-bold text-purple-900 dark:text-purple-100 truncate" title="{{ $analyzeResult['summary']['user_id'] }}">
                                                {{ $analyzeResult['summary']['user_id'] }}
                                            </div>
                                        </div>
                                    @endif
                                </div>

                                @if(isset($analyzeResult['summary']['embedded_at']))
                                    <div class="text-xs text-purple-600 dark:text-purple-400 mb-3">
                                        📅 Fecha de embebido: <span class="font-semibold">{{ $analyzeResult['summary']['embedded_at'] }}</span>
                                    </div>
                                @endif
                            @endif

                            <div class="mt-4 p-3 rounded-lg bg-purple-100 dark:bg-purple-900/30 border border-purple-200 dark:border-purple-800">
                                <p class="text-xs text-purple-800 dark:text-purple-200 leading-relaxed">
                                    💡 <strong>Cómo extraer:</strong> Utilice el sistema de extracción para documentos. En la api utilice el endpoint:
                                    <code class="bg-purple-200 dark:bg-purple-800 px-1 py-0.5 rounded text-purple-900 dark:text-purple-100">
                                        {{ isset($analyzeResult['summary']['file_type']) && $analyzeResult['summary']['file_type'] === 'audio' ? '/audio/stego/extract-document' : '/image/stego/extract-document' }}
                                    </code>
                                    @if(isset($analyzeResult['summary']['is_password_protected']) && $analyzeResult['summary']['is_password_protected'])
                                        y proporcione la contraseña correcta en el campo <code class="bg-purple-200 dark:bg-purple-800 px-1 py-0.5 rounded">password</code>
                                    @endif
                                </p>
                            </div>
                        </div>
                    @endif

                    {{-- MEJORADO: Información del Mensaje de Texto --}}
                    @if(isset($analyzeResult['summary']['message_found']) && $analyzeResult['summary']['message_found'] && isset($analyzeResult['summary']['content_type']) && $analyzeResult['summary']['content_type'] === 'text')
                        <div class="rounded-lg border-2 border-blue-200 dark:border-blue-900/50 bg-gradient-to-br from-blue-50 to-cyan-50 dark:from-blue-950/30 dark:to-cyan-950/30 p-5 mb-6">
                            <div class="flex items-start gap-3 mb-3">
                                <x-heroicon-o-chat-bubble-left-right class="w-6 h-6 text-blue-600 dark:text-blue-400 flex-shrink-0"/>
                                <div class="flex-1">
                                    <h4 class="text-lg font-bold text-blue-900 dark:text-blue-100 mb-1">💬 Mensaje de Texto Extraído</h4>
                                    <p class="text-sm text-blue-700 dark:text-blue-300">
                                        Se encontró un mensaje de texto oculto en el archivo
                                    </p>
                                </div>
                            </div>

                            @if(isset($analyzeResult['summary']['message_preview']))
                                <div class="rounded-lg bg-white/60 dark:bg-black/20 border border-blue-200/50 dark:border-blue-800/30 p-4 mb-3">
                                    <div class="text-xs font-medium text-blue-700 dark:text-blue-300 mb-2 uppercase tracking-wide">Contenido del Mensaje</div>
                                    <div class="bg-blue-100 dark:bg-blue-900/30 rounded p-3 border border-blue-200 dark:border-blue-800">
                                        <p class="text-sm font-mono text-blue-900 dark:text-blue-100 break-words whitespace-pre-wrap leading-relaxed">{{ $analyzeResult['summary']['message_preview'] }}</p>
                                    </div>
                                    @if(isset($analyzeResult['summary']['message_length']))
                                        <div class="mt-2 flex items-center gap-2 text-xs text-blue-600 dark:text-blue-400">
                                            <x-heroicon-m-information-circle class="w-4 h-4"/>
                                            <span>Longitud total: <strong>{{ $analyzeResult['summary']['message_length'] }}</strong> caracteres</span>
                                        </div>
                                    @endif
                                </div>
                            @endif

                            <div class="p-3 rounded-lg bg-blue-100 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-800">
                                <p class="text-xs text-blue-800 dark:text-blue-200 leading-relaxed">
                                    💡 <strong>Cómo extraer:</strong> Utilice el sistema de extracción para documentos. En la api utilice el endpoint:
                                    <code class="bg-blue-200 dark:bg-blue-800 px-1 py-0.5 rounded text-blue-900 dark:text-blue-100">
                                        {{ isset($analyzeResult['summary']['file_type']) && $analyzeResult['summary']['file_type'] === 'audio' ? '/audio/stego/extract' : '/image/stego/extract' }}
                                    </code>
                                    para obtener el mensaje completo
                                </p>
                            </div>
                        </div>
                    @endif

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
                        </div>
                    @endif

                    {{-- Métricas (código existente sin cambios) --}}
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
                                                    <p class="text-xs @if($metric['is_suspicious']) text-red-700 dark:text-red-300 @else text-slate-600 dark:text-slate-400 @endif leading-relaxed mb-2 whitespace-pre-line">
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
                                                    <p class="text-xs @if($metric['is_suspicious']) text-amber-700 dark:text-amber-300 @else text-slate-600 dark:text-slate-400 @endif leading-relaxed mb-2 whitespace-pre-line">
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
                                                        <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed mb-2 whitespace-pre-line">
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

                    {{-- Resumen Técnico --}}
                    @if(isset($analyzeResult['summary']))
                        <div class="rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5">
                            <h4 class="text-sm font-semibold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
                                <x-heroicon-o-information-circle class="w-4 h-4 text-slate-600 dark:text-slate-400"/>
                                Resumen Técnico del Análisis
                            </h4>

                            <div class="space-y-3">
                                @foreach($analyzeResult['summary'] as $key => $value)
                                    @if(!is_array($value) && !in_array($key, ['message_preview', 'message_found', 'detection_method', 'message_length', 'content_type', 'document_found', 'is_password_protected', 'document_filename', 'document_size', 'document_mime_type', 'user_id', 'embedded_at']))
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

                                {{-- Recomendación --}}
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
                            </div>
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </div>
</x-filament-panels::page>
