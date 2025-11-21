<x-filament-panels::page class="">
    <div x-data="chatComponent()" x-init="init()">
        <div class="h-[calc(100vh-7rem)] xl:min-w-[80rem] flex flex-col bg-gray-50 dark:bg-gray-900 rounded-xl">

            {{-- Header del Chat con Switches --}}
            <div class="bg-white rounded-t-xl dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 px-6 py-3 shadow-sm flex-shrink-0">
                <div class="flex items-center justify-between flex-wrap gap-4">

                    {{-- Lado izquierdo: Logo y estado --}}
                    <div class="flex items-center gap-2">
                        <div class="relative">
                            <div class="w-8 h-8 bg-gradient-to-br from-primary-500 to-primary-600 rounded-full flex items-center justify-center">
                                <x-heroicon-o-chat-bubble-left-right class="w-4 h-4 text-white" />
                            </div>
                            <div class="absolute -bottom-1 -right-1 w-4 h-4 border-2 border-white dark:border-gray-800 rounded-full transition-all duration-300"
                                 :class="{
                                     'bg-primary-500': $wire.connectionStatus === 'connected',
                                     'bg-blue-500 animate-pulse': $wire.connectionStatus === 'connecting',
                                     'bg-yellow-500': $wire.connectionStatus === 'disconnected',
                                     'bg-red-500': $wire.connectionStatus === 'error'
                                 }">
                            </div>
                        </div>
                        <div>
                            <h1 class="text-lg font-semibold text-gray-900 dark:text-white">Chat Global</h1>
                            <p class="text-xs transition-colors duration-300 flex items-center gap-1.5"
                               :class="{
                                   'text-primary-600 dark:text-primary-400': $wire.connectionStatus === 'connected',
                                   'text-blue-600 dark:text-blue-400': $wire.connectionStatus === 'connecting',
                                   'text-yellow-600 dark:text-yellow-400': $wire.connectionStatus === 'disconnected',
                                   'text-red-600 dark:text-red-400': $wire.connectionStatus === 'error'
                               }">
                                <span x-show="$wire.connectionStatus === 'connected'" class="flex items-center gap-1.5">
                                    <x-heroicon-s-check-circle class="w-3.5 h-3.5" />
                                    <span>Conectado</span>
                                </span>
                                <span x-show="$wire.connectionStatus === 'connecting'" class="flex items-center gap-1.5">
                                    <x-heroicon-s-arrow-path class="w-3.5 h-3.5 animate-spin" />
                                    <span>Conectando</span>
                                </span>
                                <span x-show="$wire.connectionStatus === 'disconnected'" class="flex items-center gap-1.5">
                                    <x-heroicon-s-exclamation-circle class="w-3.5 h-3.5" />
                                    <span>Desconectado</span>
                                </span>
                                <span x-show="$wire.connectionStatus === 'error'" class="flex items-center gap-1.5">
                                    <x-heroicon-s-x-circle class="w-3.5 h-3.5" />
                                    <span>Error de conexión</span>
                                </span>
                            </p>
                        </div>
                    </div>

                    {{-- Lado derecho: Switches --}}
                    <div class="flex items-center gap-2">
                        {{-- Switch 1: Imagen/Texto --}}
                        <div class="flex items-center gap-3 text-sm font-semibold">
                            <div class="flex gap-1">
                                <x-heroicon-o-photo
                                    x-bind:class="$wire.viewMode === 'image' ? 'w-5 h-5 transition-colors text-primary-600 dark:text-primary-400' : 'w-5 h-5 transition-colors text-gray-400'"
                                />
                                <span class="transition-colors md:block hidden" x-bind:class="$wire.viewMode === 'image' ? 'text-primary-600 dark:text-primary-400' : 'text-gray-500'">
                                    Multimedia
                                </span>
                            </div>
                            <button
                                wire:click="toggleViewMode"
                                class="relative inline-flex h-5 w-9 items-center rounded-full transition-colors focus:outline-none focus:ring-1 focus:ring-primary-500 focus:ring-offset-1"
                                :class="$wire.viewMode === 'image' ? 'bg-gray-300 dark:bg-gray-600' : 'bg-primary-600'"
                            >
                                <span class="sr-only">Cambiar modo de visualización</span>
                                <span
                                    class="inline-block h-3 w-3 transform rounded-full bg-white shadow-lg transition-transform duration-200"
                                    :class="$wire.viewMode === 'text' ? 'translate-x-5' : 'translate-x-1'"
                                ></span>
                            </button>
                            <div class="flex gap-1">
                                <x-heroicon-o-chat-bubble-bottom-center-text
                                    x-bind:class="$wire.viewMode === 'text' ? 'w-5 h-5 transition-colors text-primary-600 dark:text-primary-400' : 'w-5 h-5 transition-colors text-gray-400'"
                                />
                                <span class="md:block hidden transition-colors" x-bind:class="$wire.viewMode === 'text' ? 'text-primary-600 dark:text-primary-400' : 'text-gray-500'">
                                    Mensaje Oculto
                                </span>
                            </div>
                        </div>

                        {{-- Switch 2: Ocultar mensajes vacíos (solo en modo texto) --}}
                        <div
                            x-show="$wire.viewMode === 'text'"
                            x-transition:enter="transition ease-out duration-200"
                            x-transition:enter-start="opacity-0 transform scale-95"
                            x-transition:enter-end="opacity-100 transform scale-100"
                            x-transition:leave="transition ease-in duration-150"
                            x-transition:leave-start="opacity-100 transform scale-100"
                            x-transition:leave-end="opacity-0 transform scale-95"
                            class="flex items-center gap-3 text-sm font-semibold border-l border-gray-300 dark:border-gray-600 pl-2"
                        >
                            <div class="flex items-center gap-2">
                                <x-heroicon-o-eye-slash
                                    x-bind:class="$wire.hideEmptyMessages ? 'w-4 h-4 transition-colors text-primary-600 dark:text-primary-400' : 'w-4 h-4 transition-colors text-gray-400'"
                                />
                                <span class="md:block hidden transition-colors" x-bind:class="$wire.hideEmptyMessages ? 'text-primary-600 dark:text-primary-400' : 'text-gray-500'">
                                    Ocultar vacíos
                                </span>
                            </div>
                            <button
                                wire:click="toggleHideEmptyMessages"
                                class="relative inline-flex h-5 w-9 items-center rounded-full transition-colors focus:outline-none focus:ring-1 focus:ring-primary-500 focus:ring-offset-1"
                                :class="$wire.hideEmptyMessages ? 'bg-primary-600' : 'bg-gray-300 dark:bg-gray-600'"
                            >
                                <span class="sr-only">Ocultar mensajes sin contenido</span>
                                <span
                                    class="inline-block h-3 w-3 transform rounded-full bg-white shadow-lg transition-transform duration-200"
                                    :class="$wire.hideEmptyMessages ? 'translate-x-5' : 'translate-x-1'"
                                ></span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Área de Mensajes --}}
            <div class="flex-1 min-h-0 overflow-hidden relative">
                <div
                    x-ref="messagesContainer"
                    @scroll="checkScrollPosition()"
                    class="h-full overflow-y-auto bg-gray-100 dark:bg-gray-900"
                >
                    <div class="max-w-6xl mx-auto p-4 space-y-3">

                        {{-- Mensaje de bienvenida --}}
                        <div class="text-center">
                            <div class="inline-flex items-center gap-2 px-4 py-3 bg-white dark:bg-gray-800 rounded-full border shadow-xs transition-all duration-300"
                                 :class="{
                                     'border-primary-200 dark:border-primary-800': $wire.connectionStatus === 'connected',
                                     'border-blue-200 dark:border-blue-800': $wire.connectionStatus === 'connecting',
                                     'border-yellow-200 dark:border-yellow-800': $wire.connectionStatus === 'disconnected',
                                     'border-red-200 dark:border-red-800': $wire.connectionStatus === 'error'
                                 }">
                                <span x-show="$wire.connectionStatus === 'connected'">
                                    <x-heroicon-o-check-circle class="w-4 h-4 text-primary-500" />
                                </span>
                                <span x-show="$wire.connectionStatus === 'connecting'">
                                    <x-heroicon-o-arrow-path class="w-4 h-4 text-blue-500 animate-spin" />
                                </span>
                                <span x-show="$wire.connectionStatus === 'disconnected'">
                                    <x-heroicon-o-exclamation-circle class="w-4 h-4 text-yellow-500" />
                                </span>
                                <span x-show="$wire.connectionStatus === 'error'">
                                    <x-heroicon-o-x-circle class="w-4 h-4 text-red-500" />
                                </span>

                                <span class="text-sm transition-colors duration-300"
                                      :class="{
                                          'text-primary-600 dark:text-primary-400': $wire.connectionStatus === 'connected',
                                          'text-blue-600 dark:text-blue-400': $wire.connectionStatus === 'connecting',
                                          'text-yellow-600 dark:text-yellow-400': $wire.connectionStatus === 'disconnected',
                                          'text-red-600 dark:text-red-400': $wire.connectionStatus === 'error'
                                      }">
                                    <span class="font-semibold" x-show="$wire.connectionStatus === 'connected'">Chat esteganográfico conectado</span>
                                    <span class="font-semibold" x-show="$wire.connectionStatus === 'connecting'">Conectando al chat</span>
                                    <span class="font-semibold" x-show="$wire.connectionStatus === 'disconnected'">Chat desconectado</span>
                                    <span class="font-semibold" x-show="$wire.connectionStatus === 'error'">Error en la conexión</span>
                                </span>
                            </div>
                        </div>

                        @if($isLoading)
                            <div class="flex justify-center items-center py-12">
                                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-600"></div>
                            </div>
                        @elseif($isExtracting && $viewMode === 'text')
                            {{-- Loading de extracción --}}
                            <div class="flex flex-col justify-center items-center py-12">
                                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600 mb-4"></div>
                                <p class="text-gray-700 dark:text-gray-300 font-semibold">Extrayendo mensajes ocultos...</p>
                                <p class="text-gray-500 dark:text-gray-400 text-sm mt-2">Esto puede tomar unos momentos</p>
                            </div>
                        @else
                            @php
                                // Usar mensajes filtrados según el estado del filtro
                                $displayMessages = $this->filteredMessages;
                            @endphp

                            @foreach($displayMessages as $index => $message)
                                <div wire:key="message-{{ $message['id'] }}-{{ $index }}" class="flex {{ $message['is_own'] ? 'justify-end' : 'justify-start' }} animate-fade-in">
                                    <div class="flex gap-3 max-w-[70%] {{ $message['is_own'] ? 'flex-row-reverse' : '' }}">

                                        {{-- Avatar --}}
                                        @if(!$message['is_own'])
                                            <div class="flex-shrink-0">
                                                <div class="w-8 h-8 rounded-full bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center text-white text-xs font-semibold">
                                                    @php
                                                        $name = $message['user']['name'];
                                                        $parts = explode(' ', $name);
                                                        if (count($parts) > 1) {
                                                            $initials = strtoupper(substr($parts[0], 0, 1) . substr($parts[1], 0, 1));
                                                        } else {
                                                            $initials = strtoupper(substr($name, 0, 2));
                                                        }
                                                    @endphp
                                                    {{ $initials }}
                                                </div>
                                            </div>
                                        @endif

                                        {{-- Contenido del mensaje --}}
                                        <div class="flex flex-col {{ $message['is_own'] ? 'items-end' : 'items-start' }}">
                                            @if(!$message['is_own'])
                                                <span class="text-xs font-medium text-gray-700 dark:text-gray-300 mb-1 px-2">
                                                    {{ $message['user']['name'] }}
                                                </span>
                                            @endif

                                            <div class="group relative">
                                                {{-- MODO IMAGEN: Mostrar imagen o audio --}}
                                                @if($viewMode === 'image')
                                                    @if($message['file_type'] === 'image')
                                                        {{-- Mostrar imagen --}}
                                                        <div class="rounded-2xl overflow-hidden shadow-lg max-w-sm">
                                                            <img
                                                                src="{{ $message['file_url'] }}"
                                                                alt="Imagen esteganográfica"
                                                                class="w-full h-auto cursor-pointer hover:opacity-90 transition-opacity"
                                                                onclick="window.open('{{ $message['file_url'] }}', '_blank')"
                                                            >
                                                        </div>
                                                    @elseif($message['file_type'] === 'audio')
                                                        {{-- Reproductor de audio --}}
                                                        <div class="{{ $message['is_own']
                                                            ? 'bg-primary-500 text-white rounded-l-2xl rounded-tr-2xl rounded-br-sm'
                                                            : 'bg-white dark:bg-gray-800 text-gray-900 dark:text-white rounded-r-2xl rounded-tl-2xl rounded-bl-sm border border-gray-200 dark:border-gray-700'
                                                        }} p-4 shadow-sm min-w-[300px]">
                                                            <div class="flex items-center gap-3">
                                                                <x-heroicon-o-speaker-wave class="w-5 h-5" />
                                                                <span class="text-sm font-medium">Audio esteganográfico</span>
                                                            </div>
                                                            <audio
                                                                controls
                                                                class="w-full mt-3"
                                                                preload="metadata"
                                                            >
                                                                <source src="{{ $message['file_url'] }}" type="audio/{{ pathinfo($message['message'], PATHINFO_EXTENSION) }}">
                                                                Tu navegador no soporta el elemento de audio.
                                                            </audio>
                                                        </div>
                                                    @endif
                                                @else
                                                    {{-- MODO TEXTO: Mostrar mensaje oculto --}}
                                                    <div class="{{ $message['is_own']
                                                        ? 'bg-primary-500 text-white rounded-l-2xl rounded-tr-2xl rounded-br-sm'
                                                        : 'bg-white dark:bg-gray-800 text-gray-900 dark:text-white rounded-r-2xl rounded-tl-2xl rounded-bl-sm border border-gray-200 dark:border-gray-700'
                                                    }} px-4 py-3 shadow-sm min-w-[200px]">
                                                        <div class="flex items-center gap-2 mb-2">
                                                            @if($message['hidden_message'] === null)
                                                                {{-- Aún no extraído --}}
                                                                <div class="animate-spin rounded-full h-3 w-3 border-b border-current"></div>
                                                                <span class="text-xs font-semibold">Extrayendo...</span>
                                                            @elseif(str_starts_with($message['hidden_message'], '[Sin mensaje'))
                                                                {{-- Sin mensaje oculto --}}
                                                                <x-heroicon-o-exclamation-circle class="w-4 h-4 text-amber-200" />
                                                                <span class="text-xs font-semibold">Sin mensaje:</span>
                                                            @elseif(str_starts_with($message['hidden_message'], '[Error'))
                                                                {{-- Error al extraer --}}
                                                                <x-heroicon-o-x-circle class="w-4 h-4 text-red-500" />
                                                                <span class="text-xs font-semibold">Error:</span>
                                                            @else
                                                                {{-- Mensaje extraído exitosamente --}}
                                                                <x-heroicon-o-eye-slash class="w-4 h-4" />
                                                                <span class="text-xs font-semibold">Mensaje oculto:</span>
                                                            @endif
                                                        </div>
                                                        <p class="text-sm leading-relaxed break-words">
                                                            @if($message['hidden_message'] === null)
                                                                <span class="italic opacity-70">
                                                                    Procesando...
                                                                </span>
                                                            @elseif(str_starts_with($message['hidden_message'], '[Sin mensaje') || str_starts_with($message['hidden_message'], '[Error'))
                                                                <span class="italic opacity-70">
                                                                    {{ $message['hidden_message'] }}
                                                                </span>
                                                            @else
                                                                {{ $message['hidden_message'] }}
                                                            @endif
                                                        </p>
                                                    </div>
                                                @endif

                                                {{-- Timestamp --}}
                                                <div class="flex items-center gap-2 mt-1 px-1 {{ $message['is_own'] ? 'justify-end' : 'justify-start' }}">
                                                    <span class="text-xs text-gray-500 dark:text-gray-400">{{ $message['time'] }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach

                            @if(count($displayMessages) === 0)
                                <div class="text-center py-16">
                                    <div class="w-24 h-24 mx-auto mb-4 bg-gray-200 dark:bg-gray-800 rounded-full flex items-center justify-center">
                                        @if($viewMode === 'text' && $hideEmptyMessages)
                                            <x-heroicon-o-eye-slash class="w-10 h-10 text-gray-400 dark:text-gray-600" />
                                        @else
                                            <x-heroicon-o-chat-bubble-left-right class="w-10 h-10 text-gray-400 dark:text-gray-600" />
                                        @endif
                                    </div>
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                                        @if($viewMode === 'text' && $hideEmptyMessages)
                                            No hay mensajes con contenido oculto
                                        @else
                                            No hay mensajes aún
                                        @endif
                                    </h3>
                                    <p class="text-gray-600 dark:text-gray-400 max-w-sm mx-auto">
                                        @if($viewMode === 'text' && $hideEmptyMessages)
                                            Desactiva el filtro "Ocultar vacíos" para ver todos los mensajes.
                                        @else
                                            Sé el primero en enviar un mensaje esteganográfico. ¡Sube una imagen o audio!
                                        @endif
                                    </p>
                                </div>
                            @endif
                        @endif
                    </div>
                </div>

                {{-- Botón de scroll --}}
                <button
                    x-ref="scrollButton"
                    x-show="showScrollButton"
                    @click="scrollToBottom()"
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 transform scale-90"
                    x-transition:enter-end="opacity-100 transform scale-100"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100 transform scale-100"
                    x-transition:leave-end="opacity-0 transform scale-90"
                    class="absolute bottom-4 left-1/2 -translate-x-1/2 w-10 h-10 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 hover:border-primary-500 dark:hover:border-primary-400 text-gray-700 dark:text-gray-300 rounded-full shadow-lg hover:shadow-xl transition-all duration-100 flex items-center justify-center z-10 group"
                    title="Ir al final"
                >
                    <x-heroicon-s-chevron-down class="w-5 h-5 group-hover:text-primary-500 dark:group-hover:text-primary-400 transition-colors duration-300" />
                </button>
            </div>

            {{-- Input de Mensaje (FileUpload) --}}
            <div class="bg-white rounded-b-xl dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 p-4 flex-shrink-0">
                <div class="mx-auto">
                    <form wire:submit.prevent="sendMessage" class="flex items-center gap-3">
                        <div class="w-full">
                            {{ $this->sendMessageForm }}
                        </div>
                        <button
                            type="submit"
                            wire:loading.attr="disabled"
                            class="w-10 h-10 bg-primary-500 hover:bg-primary-600 disabled:bg-primary-300 text-white rounded-full flex items-center justify-center transition-colors focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2"
                        >
                            <span wire:loading.remove>
                                <x-heroicon-o-paper-airplane class="w-5 h-5" />
                            </span>
                            <span wire:loading>
                                <div class="animate-spin rounded-full h-5 w-5 border-b-2 border-white"></div>
                            </span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function chatComponent() {
            return {
                showScrollButton: false,

                init() {
                    // Scroll inicial al cargar
                    this.$nextTick(() => {
                        this.scrollToBottom(false);
                        this.checkScrollPosition();
                    });

                    // Escuchar evento de Livewire para scroll
                    this.$wire.on('scroll-to-bottom', () => {
                        setTimeout(() => this.scrollToBottom(), 150);
                    });

                    // Detectar cambios en el DOM (nuevos mensajes)
                    this.$watch('$wire.messages', () => {
                        this.$nextTick(() => {
                            this.scrollToBottom();
                        });
                    });

                    // Detectar cambios en hideEmptyMessages
                    this.$watch('$wire.hideEmptyMessages', () => {
                        this.$nextTick(() => {
                            this.scrollToBottom();
                        });
                    });

                    // Inicializar WebSocket status
                    this.initWebSocketStatus();
                },

                scrollToBottom(smooth = true) {
                    const container = this.$refs.messagesContainer;
                    if (container) {
                        container.scrollTo({
                            top: container.scrollHeight,
                            behavior: smooth ? 'smooth' : 'auto'
                        });
                        this.showScrollButton = false;
                    }
                },

                checkScrollPosition() {
                    const container = this.$refs.messagesContainer;
                    if (!container) return;

                    const scrollTop = container.scrollTop;
                    const scrollHeight = container.scrollHeight;
                    const clientHeight = container.clientHeight;
                    const distanceFromBottom = scrollHeight - scrollTop - clientHeight;

                    this.showScrollButton = distanceFromBottom > 100;
                },

                initWebSocketStatus() {
                    setTimeout(() => {
                        const echo = window.Echo || window.laravelEcho;

                        if (!echo?.connector?.pusher) {
                            console.warn('⚠️ Echo/Pusher no disponible');
                            this.$wire.set('connectionStatus', 'error');
                            return;
                        }

                        const pusher = echo.connector.pusher;
                        const connection = pusher.connection;

                        // Mapeo de estados
                        const statusMap = {
                            'connected': 'connected',
                            'connecting': 'connecting',
                            'unavailable': 'connecting',
                            'disconnected': 'disconnected',
                            'failed': 'error'
                        };

                        // Eventos de conexión
                        connection.bind('connected', () => {
                            console.log('✅ WebSocket CONECTADO');
                            this.$wire.set('connectionStatus', 'connected');
                        });

                        connection.bind('error', (error) => {
                            console.error('❌ Error WebSocket:', error);
                            this.$wire.set('connectionStatus', 'error');
                        });

                        connection.bind('disconnected', () => {
                            console.warn('⚠️ WebSocket DESCONECTADO');
                            this.$wire.set('connectionStatus', 'disconnected');
                        });

                        connection.bind('state_change', (states) => {
                            console.log('🔄 Estado:', states.previous, '->', states.current);
                            this.$wire.set('connectionStatus', statusMap[states.current] || 'disconnected');
                        });

                        // Estado inicial
                        this.$wire.set('connectionStatus', statusMap[connection.state] || 'disconnected');

                    }, 800);
                }
            }
        }
    </script>

    <style>
        .animate-fade-in {
            animation: fadeIn 0.3s ease-in-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        [x-ref="messagesContainer"]::-webkit-scrollbar {
            width: 6px;
        }

        [x-ref="messagesContainer"]::-webkit-scrollbar-track {
            background: transparent;
        }

        [x-ref="messagesContainer"]::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 10px;
        }

        [x-ref="messagesContainer"]::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        @media (prefers-color-scheme: dark) {
            [x-ref="messagesContainer"]::-webkit-scrollbar-thumb {
                background: #475569;
            }
            [x-ref="messagesContainer"]::-webkit-scrollbar-thumb:hover {
                background: #64748b;
            }
        }

        /* Estilos para el reproductor de audio en modo oscuro */
        audio {
            filter: invert(1) hue-rotate(180deg);
        }

        @media (prefers-color-scheme: dark) {
            audio {
                filter: invert(0.9) hue-rotate(180deg);
            }
        }
    </style>
</x-filament-panels::page>
