<x-filament-panels::page>
    <div class="mx-auto xl:min-w-[80rem] space-y-8 pb-20 md:pb-8">

        {{-- Header Principal --}}
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-slate-50 to-slate-100 dark:from-slate-900 dark:via-slate-800 dark:to-slate-900 p-8 border border-slate-200 dark:border-slate-700/50 shadow-sm">
            <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGRlZnM+PHBhdHRlcm4gaWQ9ImdyaWQiIHdpZHRoPSI2MCIgaGVpZ2h0PSI2MCIgcGF0dGVyblVuaXRzPSJ1c2VyU3BhY2VPblVzZSI+PHBhdGggZD0iTSAxMCAwIEwgMCAwIDAgMTAiIGZpbGw9Im5vbmUiIHN0cm9rZT0icmdiYSgwLDAsMCwwLjAzKSIgc3Ryb2tlLXdpZHRoPSIxIi8+PC9wYXR0ZXJuPjwvZGVmcz48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSJ1cmwoI2dyaWQpIi8+PC9zdmc+')] opacity-40"></div>
            <div class="relative flex items-center gap-6">
                <div class="flex-shrink-0">
                    <div class="w-16 h-16 rounded-xl bg-gradient-to-br from-slate-200 to-slate-300 dark:from-slate-700 dark:to-slate-800 flex items-center justify-center border border-slate-300 dark:border-slate-600/50 shadow-lg">
                        <x-heroicon-o-lock-closed class="w-8 h-8 text-slate-700 dark:text-slate-300"/>
                    </div>
                </div>
                <div class="flex-1 text-center pr-20">
                    <h1 class="text-3xl font-bold text-slate-900 dark:text-white mb-2 tracking-tight">Sistema de Esteganografía LSB</h1>
                    <p class="text-slate-600 dark:text-slate-400 text-base leading-relaxed">
                        Oculta y extrae mensajes de texto o documentos completos en imágenes y audio
                    </p>
                </div>
            </div>
        </div>

        {{-- ============================================ --}}
        {{-- SECCIÓN: OCULTAR CONTENIDO --}}
        {{-- ============================================ --}}
        <div class="space-y-4">
            <div class="flex items-center gap-3 px-1">
                <div class="w-10 h-10 rounded-lg bg-slate-100 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 flex items-center justify-center">
                    <x-heroicon-o-lock-closed class="w-5 h-5 text-slate-600 dark:text-slate-400"/>
                </div>
                <div>
                    <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Ocultar Contenido</h2>
                    <p class="text-xs text-slate-600 dark:text-slate-400">Inserta información secreta en archivos multimedia</p>
                </div>
            </div>

            <div>
                <form wire:submit="embedContent">
                    {{ $this->embedForm }}
                </form>

                @if($embedResult)
                    @php
                        $result = json_decode($embedResult, true);
                    @endphp

                    <div class="border-t border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900/50 p-6">
                        <div class="flex justify-between items-center mb-4">
                            <div class="flex items-center gap-2">
                                <div class="w-8 h-8 rounded-lg bg-emerald-100 dark:bg-emerald-500/10 flex items-center justify-center">
                                    <x-heroicon-m-check-circle class="w-5 h-5 text-emerald-600 dark:text-emerald-400"/>
                                </div>
                                <div>
                                    <h3 class="text-sm font-semibold text-slate-900 dark:text-white">
                                        {{ $result['mode'] === 'message' ? 'Mensaje Ocultado' : 'Documento Ocultado' }}
                                    </h3>
                                    <p class="text-xs text-slate-600 dark:text-slate-400">
                                        {{ $result['mode'] === 'message' ? 'El mensaje ha sido embebido exitosamente' : 'El documento ha sido comprimido, cifrado y embebido' }}
                                    </p>
                                </div>
                            </div>

                            <x-filament::button
                                tag="a"
                                href="{{ $result['file'] }}"
                                :download="$result['file_name']"
                                color="gray"
                                icon="heroicon-o-arrow-down-tray"
                                size="md"
                            >
                                Descargar {{ $result['file_type'] === 'audio' ? 'Audio' : 'Imagen' }}
                            </x-filament::button>
                        </div>

                        {{-- Preview del archivo --}}
                        <div class="mb-5">
                            @if($result['file_type'] === 'audio')
                                <div class="rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-4">
                                    <div class="flex items-center gap-2 mb-3 text-xs text-slate-600 dark:text-slate-400">
                                        <x-heroicon-o-musical-note class="w-4 h-4"/>
                                        <span class="font-medium">Audio con contenido oculto</span>
                                    </div>
                                    <audio controls class="w-full" preload="metadata">
                                        <source src="{{ $result['file'] }}" type="audio/wav">
                                    </audio>
                                </div>
                            @else
                                <div class="rounded-lg border border-slate-200 dark:border-slate-800 overflow-hidden bg-slate-100 dark:bg-slate-900">
                                    <img src="{{ $result['file'] }}" alt="Archivo Procesado" class="h-auto max-w-[40rem] mx-auto" loading="lazy">
                                </div>
                            @endif
                        </div>

                        {{-- Estadísticas --}}
                        <div class="grid grid-cols-2 gap-4 mb-5">
                            <div class="rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-4">
                                <div class="text-xs font-medium text-slate-600 dark:text-slate-400 mb-1">Tamaño del Payload</div>
                                <div class="text-2xl font-bold text-slate-900 dark:text-white">{{ number_format($result['payload_size']) }}</div>
                                <div class="text-xs text-slate-500">bytes</div>
                            </div>

                            <div class="rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-4">
                                <div class="text-xs font-medium text-slate-600 dark:text-slate-400 mb-1">Capacidad Utilizada</div>
                                <div class="text-2xl font-bold text-slate-900 dark:text-white">{{ number_format($result['capacity_used'], 2) }}%</div>
                                <div class="text-xs text-slate-500">del total disponible</div>
                            </div>
                        </div>

                        {{-- Barra de progreso --}}
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
                        </div>

                        {{-- Info adicional según modo --}}
                        @if($result['mode'] === 'document')
                            <div class="grid grid-cols-3 gap-3 mb-5">
                                <div class="rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-3 text-center">
                                    <div class="text-sm font-medium text-slate-600 dark:text-slate-400 mb-1">Archivo Original</div>
                                    <div class="text-xs text-slate-900 dark:text-white font-mono truncate">{{ $result['original_filename'] }}</div>
                                </div>

                                <div class="rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-3 text-center">
                                    <div class="text-sm font-medium text-slate-600 dark:text-slate-400 mb-1">Compresión</div>
                                    <div class="text-lg font-bold text-slate-900 dark:text-white">
                                        {{ round((1 - $result['compressed_size'] / $result['original_size']) * 100, 1) }}%
                                    </div>
                                </div>

                                <div class="rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-3 text-center">
                                    <div class="text-sm font-medium text-slate-600 dark:text-slate-400 mb-1">Protección</div>
                                    <div class="text-sm font-semibold {{ $result['is_password_protected'] ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-600 dark:text-slate-400' }}">
                                        {{ $result['is_password_protected'] ? '🔒 Cifrado' : '🔓 Sin cifrar' }}
                                    </div>
                                </div>
                            </div>
                        @endif

                        {{-- Información técnica --}}
                        <div class="p-3 rounded-lg bg-blue-50 dark:bg-blue-500/10 border border-blue-200 dark:border-blue-800">
                            <div class="flex items-start gap-2">
                                <x-heroicon-o-information-circle class="w-5 h-5 text-blue-600 dark:text-blue-400 flex-shrink-0 mt-0.5"/>
                                <div class="text-xs text-blue-700 dark:text-blue-300">
                                    <p class="font-medium mb-1">
                                        {{ $result['mode'] === 'message' ? 'Mensaje ocultado con LSB' : 'Documento ocultado con LSB + AES-256-GCM' }}
                                    </p>
                                    <p>
                                        {{ $result['mode'] === 'message'
                                            ? 'El mensaje ha sido insertado en los bits menos significativos. El archivo se ve idéntico al original.'
                                            : 'El documento ha sido comprimido con GZIP y cifrado con AES-256-GCM antes de ser embebido. ' . ($result['is_password_protected'] ? 'Se requiere contraseña para extraer.' : 'Sin contraseña de protección.')
                                        }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- ============================================ --}}
        {{-- SECCIÓN: EXTRAER CONTENIDO --}}
        {{-- ============================================ --}}
        <div class="space-y-4">
            <div class="flex items-center gap-3 px-1">
                <div class="w-10 h-10 rounded-lg bg-slate-100 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 flex items-center justify-center">
                    <x-heroicon-o-lock-open class="w-5 h-5 text-slate-600 dark:text-slate-400"/>
                </div>
                <div>
                    <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Extraer Contenido</h2>
                    <p class="text-xs text-slate-600 dark:text-slate-400">Revela información oculta en archivos</p>
                </div>
            </div>

            <div>
                <form wire:submit="extractContent">
                    {{ $this->extractForm }}
                </form>

                @if($extractResult)
                    @php
                        $result = json_decode($extractResult, true);
                    @endphp

                    <div class="border-t border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900/50 p-6">

                        {{-- RESULTADO: MENSAJE --}}
                        @if($result['type'] === 'message')
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
                                            <div class="w-2.5 h-2.5 rounded-full bg-slate-600"></div>
                                            <div class="w-2.5 h-2.5 rounded-full bg-slate-600"></div>
                                            <div class="w-2.5 h-2.5 rounded-full bg-slate-600"></div>
                                        </div>
                                        <span class="ml-2 text-xs text-slate-400 font-mono">mensaje-oculto.txt</span>
                                    </div>

                                    <div class="p-5 font-mono text-sm max-h-96 overflow-y-auto custom-scrollbar">
                                        @if($result['message'])
                                            <pre class="text-slate-200 dark:text-slate-300 whitespace-pre-wrap break-words leading-relaxed">{{ $result['message'] }}</pre>
                                        @else
                                            <p class="text-slate-400 dark:text-slate-500 italic">No se encontró ningún mensaje oculto</p>
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
                        @endif

                        {{-- RESULTADO: DOCUMENTO --}}
                        @if($result['type'] === 'document')
                            <div class="flex justify-between items-center mb-4">
                                <div class="flex items-center gap-2">
                                    <div class="w-8 h-8 rounded-lg bg-emerald-100 dark:bg-emerald-500/10 flex items-center justify-center">
                                        <x-heroicon-m-document-arrow-down class="w-5 h-5 text-emerald-600 dark:text-emerald-400"/>
                                    </div>
                                    <div>
                                        <h3 class="text-sm font-semibold text-slate-900 dark:text-white">Documento Recuperado</h3>
                                        <p class="text-xs text-slate-600 dark:text-slate-400">Archivo extraído y descifrado exitosamente</p>
                                    </div>
                                </div>

                                <x-filament::button
                                    tag="a"
                                    href="{{ $result['file_url'] }}"
                                    :download="$result['original_filename']"
                                    color="primary"
                                    icon="heroicon-o-arrow-down-tray"
                                    size="md"
                                >
                                    Descargar {{ $result['original_filename'] }}
                                </x-filament::button>
                            </div>

                            <div class="grid grid-cols-2 gap-4 mb-5">
                                <div class="rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-4">
                                    <div class="text-xs font-medium text-slate-600 dark:text-slate-400 mb-1">Nombre Original</div>
                                    <div class="text-sm font-mono text-slate-900 dark:text-white truncate">{{ $result['original_filename'] }}</div>
                                </div>

                                <div class="rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-4">
                                    <div class="text-xs font-medium text-slate-600 dark:text-slate-400 mb-1">Tamaño del Archivo</div>
                                    <div class="text-sm font-semibold text-slate-900 dark:text-white">{{ number_format($result['document_size']) }} bytes</div>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4 mb-5">
                                <div class="rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-4">
                                    <div class="text-xs font-medium text-slate-600 dark:text-slate-400 mb-1">Tipo MIME</div>
                                    <div class="text-sm font-mono text-slate-900 dark:text-white">{{ $result['mime_type'] }}</div>
                                </div>

                                <div class="rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-4">
                                    <div class="text-xs font-medium text-slate-600 dark:text-slate-400 mb-1">Usuario</div>
                                    <div class="text-sm font-semibold text-slate-900 dark:text-white">{{ $result['user_id'] }}</div>
                                </div>
                            </div>

                            <div class="p-3 rounded-lg bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-200 dark:border-emerald-800">
                                <div class="flex items-start gap-2">
                                    <x-heroicon-o-shield-check class="w-5 h-5 text-emerald-600 dark:text-emerald-400 flex-shrink-0 mt-0.5"/>
                                    <div class="text-xs text-emerald-700 dark:text-emerald-300">
                                        <p class="font-medium mb-1">Documento extraído y verificado</p>
                                        <p>El archivo fue descomprimido y descifrado exitosamente. Embebido el: {{ \Carbon\Carbon::parse($result['embedded_at'])->format('d/m/Y H:i:s') }}</p>
                                    </div>
                                </div>
                            </div>
                        @endif

                    </div>
                @endif
            </div>
        </div>

    </div>

    {{-- ============================================ --}}
    {{-- SELECTOR FLOTANTE (FIXED BOTTOM RIGHT) - RESPONSIVO Y MINIMALISTA --}}
    {{-- ============================================ --}}
    <div class="fixed bottom-4 right-4 md:bottom-6 md:right-6 z-50">
        <div class="relative group">
            {{-- Efecto de glow en hover --}}
            <div class="absolute -inset-2 bg-gradient-to-r from-primary-200 to-primary-700 rounded-lg opacity-0 group-hover:opacity-30 blur transition duration-300"></div>

            {{-- Contenedor principal --}}
            <div class="relative bg-white/95 dark:bg-slate-900/95 backdrop-blur-xl rounded-lg shadow-2xl border border-slate-200/50 dark:border-slate-700/50 overflow-hidden">
                {{-- Barra superior decorativa --}}
                <div class="h-1 bg-gradient-to-r from-primary-600 via-primary-400 to-primary-200"></div>

                <div class="p-2 md:p-3">
                    <div class="flex md:flex-col gap-2">
                        {{-- Botón Mensaje Oculto --}}
                        <button
                            wire:click="$set('embedMode', 'message'); $set('extractMode', 'message')"
                            class="group/btn relative flex items-center justify-center md:justify-start gap-2.5 px-3 py-3 md:px-4 md:py-3 rounded-xl text-sm font-medium transition-all duration-300 overflow-hidden
                                {{ $embedMode === 'message'
                                    ? 'bg-gradient-to-r from-primary-500 to-primary-600 text-white shadow-lg shadow-primary-500/30 scale-105'
                                    : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800/50 hover:scale-105 active:scale-95' }}"
                            title="Mensaje Oculto"
                        >
                            {{-- Efecto de brillo en hover --}}
                            @if($embedMode !== 'message')
                                <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/10 to-transparent -translate-x-full group-hover/btn:translate-x-full transition-transform duration-700"></div>
                            @endif

                            <x-heroicon-o-chat-bubble-bottom-center-text class="w-5 h-5 md:w-5 md:h-5 flex-shrink-0 {{ $embedMode === 'message' ? 'animate-pulse' : '' }}"/>
                            <span class="hidden md:inline whitespace-nowrap font-semibold">Mensaje Oculto</span>

                            {{-- Indicador activo --}}
                            @if($embedMode === 'message')
                                <div class="absolute -right-1 -top-1 w-2 h-2 bg-white rounded-full animate-ping"></div>
                                <div class="absolute -right-1 -top-1 w-2 h-2 bg-white rounded-full"></div>
                            @endif
                        </button>

                        {{-- Botón Documento Oculto --}}
                        <button
                            wire:click="$set('embedMode', 'document'); $set('extractMode', 'document')"
                            class="group/btn relative flex items-center justify-center md:justify-start gap-2.5 px-3 py-3 md:px-4 md:py-3 rounded-xl text-sm font-medium transition-all duration-300 overflow-hidden
                                {{ $embedMode === 'document'
                                    ? 'bg-gradient-to-r from-primary-500 to-primary-600 text-white shadow-lg shadow-primary-500/30 scale-105'
                                    : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800/50 hover:scale-105 active:scale-95' }}"
                            title="Documento Oculto"
                        >
                            {{-- Efecto de brillo en hover --}}
                            @if($embedMode !== 'document')
                                <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/10 to-transparent -translate-x-full group-hover/btn:translate-x-full transition-transform duration-700"></div>
                            @endif

                            <x-heroicon-o-document-magnifying-glass class="w-5 h-5 md:w-5 md:h-5 flex-shrink-0 {{ $embedMode === 'document' ? 'animate-pulse' : '' }}"/>
                            <span class="hidden md:inline whitespace-nowrap font-semibold">Documento Oculto</span>

                            {{-- Indicador activo --}}
                            @if($embedMode === 'document')
                                <div class="absolute -right-1 -top-1 w-2 h-2 bg-white rounded-full animate-ping"></div>
                                <div class="absolute -right-1 -top-1 w-2 h-2 bg-white rounded-full"></div>
                            @endif
                        </button>
                    </div>
                </div>

                {{-- Etiqueta inferior (solo visible en desktop) --}}
                <div class="hidden md:block px-3 py-2 text-center border-t border-slate-200/50 dark:border-slate-700/50 bg-slate-50/50 dark:bg-slate-800/30">
                    <p class="text-[10px] font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Tipo de Contenido</p>
                </div>

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
    </style>
</x-filament-panels::page>
