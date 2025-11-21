<x-filament-panels::page class="!p-0 !max-w-none">
    <div>
        <div class="h-[calc(100vh-7rem)] flex flex-col bg-gray-50 dark:bg-gray-900 rounded-xl">
            {{-- Header del Chat --}}
            <div class="bg-white rounded-t-xl dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 px-6 py-3 shadow-sm flex-shrink-0">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div class="relative">
                            <div class="w-8 h-8 bg-gradient-to-br from-primary-500 to-primary-600 rounded-full flex items-center justify-center">
                                <x-heroicon-o-chat-bubble-left-right class="w-4 h-4 text-white" />
                            </div>
                            <div class="absolute -bottom-1 -right-1 w-4 h-4 bg-primary-500 border-2 border-white dark:border-gray-800 rounded-full"></div>
                        </div>
                        <div>
                            <h1 class="text-lg font-semibold text-gray-900 dark:text-white">Chat Global</h1>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Área de Mensajes - CON ALTURA FLEXIBLE Y SCROLL INTERNO --}}
            <div class="flex-1 min-h-0 overflow-hidden"> {{-- Contenedor para limitar altura --}}
                <div class="h-full overflow-y-auto bg-gray-100 dark:bg-gray-900" id="messages-container">
                    <div class="max-w-4xl mx-auto p-4 space-y-3">

                        {{-- Mensaje de bienvenida --}}
                        <div class="text-center">
                            <div class="inline-flex items-center gap-2 px-4 py-3 bg-white dark:bg-gray-800 rounded-full border border-gray-200 dark:border-gray-700 shadow-xs">
                                <x-heroicon-o-check-badge class="w-4 h-4 text-primary-500" />
                                <span class="text-sm text-gray-600 dark:text-gray-400">Chat conectado en tiempo real</span>
                            </div>
                        </div>

                        @if($isLoading)
                            {{-- Loading --}}
                            <div class="flex justify-center items-center py-12">
                                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-600"></div>
                            </div>
                        @else
                            {{-- Mensajes --}}
                            @foreach($messages as $message)
                                <div class="flex {{ $message['is_own'] ? 'justify-end' : 'justify-start' }} animate-fade-in">
                                    <div class="flex gap-3 max-w-[70%] {{ $message['is_own'] ? 'flex-row-reverse' : '' }}">

                                        {{-- Avatar --}}
                                        @if(!$message['is_own'])
                                            <div class="flex-shrink-0">
                                                <div class="w-8 h-8 rounded-full bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center text-white text-xs font-semibold">
                                                    @php
                                                        $name = $message['user']['name'];
                                                        $parts = explode(' ', $name);

                                                        if (count($parts) > 1) {
                                                            // Más de una palabra: inicial de la primera y segunda
                                                            $initials = strtoupper(substr($parts[0], 0, 1) . substr($parts[1], 0, 1));
                                                        } else {
                                                            // Solo una palabra: las dos primeras letras
                                                            $initials = strtoupper(substr($name, 0, 2));
                                                        }
                                                    @endphp
                                                    {{ $initials }}
                                                </div>
                                            </div>
                                        @endif

                                        {{-- Burbuja de mensaje --}}
                                        <div class="flex flex-col {{ $message['is_own'] ? 'items-end' : 'items-start' }}">
                                            @if(!$message['is_own'])
                                                <span class="text-xs font-medium text-gray-700 dark:text-gray-300 mb-1 px-2">
                                            {{ $message['user']['name'] }}
                                        </span>
                                            @endif
                                            <div class="group relative">
                                                <div class="{{ $message['is_own']
                                            ? 'bg-primary-500 text-white rounded-l-2xl rounded-tr-2xl rounded-br-sm'
                                            : 'bg-white dark:bg-gray-800 text-gray-900 dark:text-white rounded-r-2xl rounded-tl-2xl rounded-bl-sm border border-gray-200 dark:border-gray-700'
                                        }} px-4 py-3 shadow-sm">
                                                    <p class="text-sm leading-relaxed break-words">{{ $message['message'] }}</p>
                                                </div>
                                                <div class="flex items-center gap-2 mt-1 px-1 {{ $message['is_own'] ? 'justify-end' : 'justify-start' }}">
                                                    <span class="text-xs text-gray-500 dark:text-gray-400">{{ $message['time'] }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach

                            @if(count($messages) === 0)
                                {{-- Estado vacío --}}
                                <div class="text-center py-16">
                                    <div class="w-24 h-24 mx-auto mb-4 bg-gray-200 dark:bg-gray-800 rounded-full flex items-center justify-center">
                                        <x-heroicon-o-chat-bubble-left-right class="w-10 h-10 text-gray-400 dark:text-gray-600" />
                                    </div>
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">No hay mensajes aún</h3>
                                    <p class="text-gray-600 dark:text-gray-400 max-w-sm mx-auto">
                                        Sé el primero en iniciar la conversación. ¡Escribe un mensaje abajo!
                                    </p>
                                </div>
                            @endif
                        @endif
                    </div>
                </div>
            </div>

            {{-- Input de Mensaje - FIJO EN LA PARTE INFERIOR --}}
            <div class="bg-white rounded-b-xl dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 p-4 flex-shrink-0">
                <div class="max-w-4xl mx-auto">
                    <form wire:submit.prevent="sendMessage" class="flex gap-3 items-center">
                        <div class="flex-1">
                            <div class="relative">
                        <textarea
                            wire:model="newMessage"
                            placeholder="Escribe un mensaje..."
                            rows="1"
                            class="w-full border border-gray-300 dark:border-gray-600 rounded-2xl px-4 py-3 pr-12 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent bg-white dark:bg-gray-900 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 resize-none max-h-32"
                            maxlength="1000"
                            oninput="this.style.height = 'auto'; this.style.height = (this.scrollHeight) + 'px';"
                        ></textarea>
                                <div class="absolute right-3 bottom-3 flex items-center gap-2">
                                    <button type="button" class="p-1 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg transition-colors">
                                        <x-heroicon-o-face-smile class="w-5 h-5 text-gray-500 dark:text-gray-400" />
                                    </button>
                                    <button type="button" class="p-1 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg transition-colors">
                                        <x-heroicon-o-paper-clip class="w-5 h-5 text-gray-500 dark:text-gray-400" />
                                    </button>
                                </div>
                            </div>

                        </div>
                        <button
                            type="submit"
                            wire:loading.attr="disabled"
                            class=" w-10 h-10 bg-primary-500 hover:bg-primary-600 disabled:bg-primary-300 text-white rounded-full flex items-center justify-center transition-colors focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2"
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

    @script
    <script>
        // Scroll automático al nuevo mensaje
        $wire.on('scroll-to-bottom', () => {
            const container = document.getElementById('messages-container');
            if (container) {
                setTimeout(() => {
                    container.scrollTop = container.scrollHeight;
                }, 100);
            }
        });

        // Inicializar scroll al final al cargar
        document.addEventListener('DOMContentLoaded', function() {
            const container = document.getElementById('messages-container');
            if (container) {
                container.scrollTop = container.scrollHeight;
            }
        });

        // Enter para enviar (Shift+Enter para nueva línea)
        document.addEventListener('keydown', function(e) {
            const textarea = document.querySelector('textarea[wire\\:model="newMessage"]');
            if (e.key === 'Enter' && textarea && document.activeElement === textarea) {
                if (e.shiftKey) {
                    // Shift+Enter - nueva línea
                    return;
                } else {
                    // Enter - enviar mensaje
                    e.preventDefault();
                    if (textarea.value.trim()) {
                        @this.sendMessage();
                    }
                }
            }
        });

        // Auto-resize del textarea
        document.addEventListener('input', function(e) {
            if (e.target.tagName === 'TEXTAREA' && e.target.getAttribute('wire:model') === 'newMessage') {
                e.target.style.height = 'auto';
                e.target.style.height = (e.target.scrollHeight) + 'px';
            }
        });
    </script>
    @endscript
    @script
    <script>
        // Scroll automático
        Livewire.on('scroll-to-bottom', () => {
            setTimeout(() => {
                const container = document.querySelector('.chat-messages-container');
                if (container) {
                    container.scrollTop = container.scrollHeight;
                }
            }, 100);
        });
    </script>
    @endscript

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

        /* Scrollbar personalizado para el área de mensajes */
        #messages-container::-webkit-scrollbar {
            width: 6px;
        }

        #messages-container::-webkit-scrollbar-track {
            background: transparent;
        }

        #messages-container::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 10px;
        }

        #messages-container::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        @media (prefers-color-scheme: dark) {
            #messages-container::-webkit-scrollbar-thumb {
                background: #475569;
            }
            #messages-container::-webkit-scrollbar-thumb:hover {
                background: #64748b;
            }
        }
    </style>
</x-filament-panels::page>
