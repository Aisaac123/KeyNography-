<x-filament-panels::page>
    <div class="mx-auto min-w-[80rem] space-y-8">

        {{-- Header Principal --}}
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-slate-50 to-slate-100 dark:from-slate-900 dark:via-slate-800 dark:to-slate-900 p-8 border border-slate-200 dark:border-slate-700/50 shadow-sm dark:shadow-none">
            <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGRlZnM+PHBhdHRlcm4gaWQ9ImdyaWQiIHdpZHRoPSI2MCIgaGVpZ2h0PSI2MCIgcGF0dGVyblVuaXRzPSJ1c2VyU3BhY2VPblVzZSI+PHBhdGggZD0iTSAxMCAwIEwgMCAwIDAgMTAiIGZpbGw9Im5vbmUiIHN0cm9rZT0icmdiYSgwLDAsMCwwLjAzKSIgc3Ryb2tlLXdpZHRoPSIxIi8+PC9wYXR0ZXJuPjwvZGVmcz48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSJ1cmwoI2dyaWQpIi8+PC9zdmc+')] dark:bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGRlZnM+PHBhdHRlcm4gaWQ9ImdyaWQiIHdpZHRoPSI2MCIgaGVpZ2h0PSI2MCIgcGF0dGVyblVuaXRzPSJ1c2VyU3BhY2VPblVzZSI+PHBhdGggZD0iTSAxMCAwIEwgMCAwIDAgMTAiIGZpbGw9Im5vbmUiIHN0cm9rZT0icmdiYSgyNTUsMjU1LDI1NSwwLjAzKSIgc3Ryb2tlLXdpZHRoPSIxIi8+PC9wYXR0ZXJuPjwvZGVmcz48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSJ1cmwoI2dyaWQpIi8+PC9zdmc+')] opacity-40"></div>
            <div class="relative flex items-center gap-6">
                <div class="flex-shrink-0">
                    <div class="w-16 h-16 rounded-xl bg-gradient-to-br from-slate-200 to-slate-300 dark:from-slate-700 dark:to-slate-800 flex items-center justify-center border border-slate-300 dark:border-slate-600/50 shadow-lg shadow-black/5 dark:shadow-black/20">
                        <x-heroicon-o-lock-closed class="w-8 h-8 text-slate-700 dark:text-slate-300"/>
                    </div>
                </div>
                <div class="flex-1 text-center">
                    <h1 class="text-3xl font-bold pr-20 text-slate-900 dark:text-white mb-2 tracking-tight">Sistema de infección y extracción mediante Esteganografía LSB</h1>
                    <p class="text-slate-600 pr-20 dark:text-slate-400 text-base leading-relaxed">
                        Oculta y extrae mensajes secretos en imágenes y audio usando técnicas LSB.
                    </p>
                </div>
            </div>
        </div>

        {{-- ============================================ --}}
        {{-- SECCIÓN: INFECTAR --}}
        {{-- ============================================ --}}
        <div class="space-y-4">
            <div class="flex items-center gap-3 px-1">
                <div class="w-10 h-10 rounded-lg bg-slate-100 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 flex items-center justify-center">
                    <x-heroicon-o-lock-closed class="w-5 h-5 text-slate-600 dark:text-slate-400"/>
                </div>
                <div>
                    <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Ocultar Mensaje</h2>
                    <p class="text-xs text-slate-600 dark:text-slate-400">Inserta contenido secreto en archivos multimedia</p>
                </div>
            </div>

            <div>
                <form wire:submit="embedMessage">
                    {{ $this->embedForm }}
                </form>

                @if($embedResult)
                    @php
                        $result = json_decode($embedResult, true);
                    @endphp

                    <div class="border-t border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900/50 p-6">
                        <div class="flex justify-between items-center">
                            <div class="flex items-center gap-2 mb-4">
                                <div class="w-8 h-8 rounded-lg bg-emerald-100 dark:bg-emerald-500/10 flex items-center justify-center">
                                    <x-heroicon-m-check-circle class="w-5 h-5 text-emerald-600 dark:text-emerald-400"/>
                                </div>
                                <div>
                                    <h3 class="text-sm font-semibold text-slate-900 dark:text-white">Proceso Completado</h3>
                                    <p class="text-xs text-slate-600 dark:text-slate-400">El archivo ha sido procesado exitosamente</p>
                                </div>
                            </div>
                            {{-- ✅ Botón con descarga usando tag="a" --}}
                            <x-filament::button
                                tag="a"
                                href="{{ $result['file'] }}"
                                :download="$result['file_name'] ?? 'infected_file.' . ($result['file_type'] === 'audio' ? 'wav' : 'png')"
                                color="gray"
                                icon="heroicon-o-arrow-down-tray"
                                size="md"
                                class="w-72"
                            >
                                Descargar {{ $result['file_type'] === 'audio' ? 'Audio' : 'Imagen' }} {{ $result['file_type'] === 'audio' ? 'Procesado' : 'Procesada' }}
                            </x-filament::button>

                        </div>

                        <div class="mb-5">
                            {{-- ✅ Usar file_type en lugar de verificar la extensión --}}
                            @if($result['file_type'] === 'audio')
                                <div class="rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-4">
                                    <div class="flex items-center gap-2 mb-3 text-xs text-slate-600 dark:text-slate-400">
                                        <x-heroicon-o-musical-note class="w-4 h-4"/>
                                        <span class="font-medium">Audio con mensaje oculto</span>
                                    </div>
                                    <audio controls class="w-full" preload="metadata">
                                        <source src="{{ $result['file'] }}" type="audio/wav">
                                        Tu navegador no soporta el elemento de audio.
                                    </audio>
                                </div>
                            @else
                                <div class="rounded-lg border border-slate-200 dark:border-slate-800 overflow-hidden bg-slate-100 dark:bg-slate-900">
                                    <img
                                        src="{{ $result['file'] }}"
                                        alt="Imagen Infectada"
                                        class="h-auto max-w-[40rem] mx-auto"
                                        loading="lazy"
                                        onerror="this.onerror=null; this.src='data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 width=%22400%22 height=%22300%22><rect width=%22400%22 height=%22300%22 fill=%22%23f1f5f9%22/><text x=%2250%25%22 y=%2250%25%22 dominant-baseline=%22middle%22 text-anchor=%22middle%22 font-family=%22sans-serif%22 font-size=%2216%22 fill=%22%23475569%22>Error al cargar imagen</text></svg>';"
                                    >
                                </div>
                            @endif
                        </div>

                        <div class="grid grid-cols-2 gap-4 mb-5">
                            <div class="rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-4">
                                <div class="text-xs font-medium text-slate-600 dark:text-slate-400 mb-1">Tamaño del Payload</div>
                                <div class="text-2xl font-bold text-slate-900 dark:text-white">{{ number_format($result['payload_size']) }}</div>
                                <div class="text-xs text-slate-500 dark:text-slate-500">bytes</div>
                            </div>

                            <div class="rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-4">
                                <div class="text-xs font-medium text-slate-600 dark:text-slate-400 mb-1">Capacidad Utilizada</div>
                                <div class="text-2xl font-bold text-slate-900 dark:text-white">{{ number_format($result['capacity_used'], 2) }}%</div>
                                <div class="text-xs text-slate-500 dark:text-slate-500">del total disponible</div>
                            </div>
                        </div>

                        <div class="mb-5">
                            <div class="flex items-center justify-between text-xs mb-2">
                                <span class="text-slate-600 dark:text-slate-400 font-medium">Capacidad</span>
                                <span class="font-semibold text-slate-900 dark:text-white">{{ number_format($result['capacity_used'], 2) }}%</span>
                            </div>
                            <div class="w-full bg-slate-200 dark:bg-slate-800 rounded-full h-1.5 overflow-hidden">
                                <div class="h-full rounded-full transition-all duration-500
                            {{ $result['capacity_used'] < 50 ? 'bg-emerald-500' : ($result['capacity_used'] < 80 ? 'bg-yellow-500' : 'bg-red-500') }}"
                                     style="width: {{ min($result['capacity_used'], 100) }}%">
                                </div>
                            </div>
                            @if($result['capacity_used'] > 80)
                                <p class="text-xs text-amber-600 dark:text-amber-400 mt-2 flex items-center gap-1">
                                    <x-heroicon-o-exclamation-triangle class="w-4 h-4"/>
                                    Alta capacidad utilizada. El mensaje puede ser más detectable.
                                </p>
                            @endif
                        </div>

                        {{-- ✅ Información adicional --}}
                        <div class="mt-4 p-3 rounded-lg bg-blue-50 dark:bg-blue-500/10 border border-blue-200 dark:border-blue-800">
                            <div class="flex items-start gap-2">
                                <x-heroicon-o-information-circle class="w-5 h-5 text-blue-600 dark:text-blue-400 flex-shrink-0 mt-0.5"/>
                                <div class="text-xs text-blue-700 dark:text-blue-300">
                                    <p class="font-medium mb-1">Archivo procesado con éxito</p>
                                    <p>El mensaje ha sido ocultado usando técnica LSB (Least Significant Bit).
                                        El archivo se ve idéntico al original pero contiene información oculta.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- ============================================ --}}
        {{-- SECCIÓN: EXTRAER --}}
        {{-- ============================================ --}}
        <div class="space-y-4">
            <div class="flex items-center gap-3 px-1">
                <div class="w-10 h-10 rounded-lg bg-slate-100 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 flex items-center justify-center">
                    <x-heroicon-o-lock-open class="w-5 h-5 text-slate-600 dark:text-slate-400"/>
                </div>
                <div>
                    <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Extraer Mensaje</h2>
                    <p class="text-xs text-slate-600 dark:text-slate-400">Revela el contenido oculto en archivos</p>
                </div>
            </div>

            <div>
                    <form wire:submit="extractMessage">
                        {{ $this->extractForm }}
                    </form>

                @if($extractResult)
                    @php
                        $result = json_decode($extractResult, true);
                    @endphp

                    <div class="border-t border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900/50 p-6">
                        <div class="flex items-center gap-2 mb-4">
                            <div class="w-8 h-8 rounded-lg bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 flex items-center justify-center">
                                <x-heroicon-m-document-text class="w-5 h-5 text-slate-600 dark:text-slate-300"/>
                            </div>
                            <div>
                                <h3 class="text-sm font-semibold text-slate-900 dark:text-white">Mensaje Recuperado</h3>
                                <p class="text-xs text-slate-600 dark:text-slate-400">Contenido extraído del archivo</p>
                            </div>
                        </div>

                        <div class="relative mb-5">
                            <div class="absolute top-1 right-3 z-10">
                                <button
                                    type="button"
                                    wire:click="$dispatch('copy-to-clipboard', { text: '{{ addslashes($result['message']) }}' })"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 mb-3 text-xs font-medium text-slate-700 dark:text-slate-300 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700 transition"
                                >
                                    <x-heroicon-o-clipboard class="w-3.5 h-3.5"/>
                                    Copiar
                                </button>
                            </div>

                            <div class="rounded-lg bg-slate-900 dark:bg-slate-950 border border-slate-700 dark:border-slate-800 overflow-hidden">
                                <div class="flex items-center gap-2 px-4 py-3 border-b border-slate-700 dark:border-slate-800 bg-slate-800 dark:bg-slate-900">
                                    <div class="flex items-center gap-1.5">
                                        <div class="w-2.5 h-2.5 rounded-full bg-slate-600 dark:bg-slate-700"></div>
                                        <div class="w-2.5 h-2.5 rounded-full bg-slate-600 dark:bg-slate-700"></div>
                                        <div class="w-2.5 h-2.5 rounded-full bg-slate-600 dark:bg-slate-700"></div>
                                    </div>
                                    <span class="ml-2 text-xs text-slate-400 dark:text-slate-500 font-mono">mensaje-oculto.txt</span>
                                </div>

                                <div class="p-5 font-mono text-sm max-h-96 overflow-y-auto custom-scrollbar">
                                    @if($result['message'])
                                        <pre class="text-slate-200 dark:text-slate-300 whitespace-pre-wrap break-words leading-relaxed">{{ $result['message'] }}</pre>
                                    @else
                                        <p class="text-slate-400 dark:text-slate-500 italic">No se encontró ningún mensaje oculto en el archivo</p>
                                    @endif
                                </div>
                            </div>
                        </div>

                        @if($result['message'])
                            <div class="grid grid-cols-3 gap-3">
                                <div class="rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-3 text-center">
                                    <div class="text-lg font-bold text-slate-900 dark:text-white mb-0.5">{{ $result['length'] }}</div>
                                    <div class="text-xs text-slate-600 dark:text-slate-400">Caracteres</div>
                                </div>

                                <div class="rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-3 text-center">
                                    <div class="text-lg font-bold text-slate-900 dark:text-white mb-0.5">{{ str_word_count($result['message']) }}</div>
                                    <div class="text-xs text-slate-600 dark:text-slate-400">Palabras</div>
                                </div>

                                <div class="rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-3 text-center">
                                    <div class="text-lg font-bold text-slate-900 dark:text-white mb-0.5">{{ substr_count($result['message'], "\n") + 1 }}</div>
                                    <div class="text-xs text-slate-600 dark:text-slate-400">Líneas</div>
                                </div>
                            </div>
                        @endif
                    </div>
                @endif
            </div>
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
