<?php

namespace App\Filament\Pages;

use App\Events\GlobalChatMessage;
use App\Models\ChatMessage;
use Auth;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Pages\Page;
use Livewire\Attributes\On;
use Livewire\WithFileUploads;

class GlobalChat extends Page
{
    use InteractsWithForms;
    use WithFileUploads;

    protected string $view = 'filament.pages.global-chat';
    protected static string|null|\BackedEnum $navigationIcon = 'heroicon-o-chat-bubble-left-right';
    protected static string|null|\BackedEnum $activeNavigationIcon = 'heroicon-s-chat-bubble-left-right';
    protected static ?string $navigationLabel = 'Chat Global';
    protected static ?string $title = '';

    public $newMessage = '';
    public $messages = [];
    public $isLoading = true;
    public $onlineUsers = 0;
    public $connectionStatus = 'connecting'; // NUEVO: Estado de conexión

    public function mount()
    {
        $this->loadRecentMessages();
        $this->isLoading = false;
    }

    private function loadRecentMessages()
    {
        $recentMessages = ChatMessage::with('user')
            ->latest()
            ->limit(100)
            ->get()
            ->reverse()
            ->values();

        $this->messages = $recentMessages->map(function ($message) {
            return [
                'id' => $message->id,
                'message' => $message->message,
                'user' => [
                    'id' => $message->user->id,
                    'name' => $message->user->name,
                    'email' => $message->user->email,
                ],
                'created_at' => $message->created_at->toISOString(),
                'human_time' => $message->created_at->diffForHumans(),
                'time' => $message->created_at->format('H:i'),
                'is_own' => $message->user_id === Auth::id(),
            ];
        })->toArray();
    }

    public function sendMessage()
    {
        $this->validate([
            'newMessage' => 'required|string|min:1|max:1000',
        ]);

        if (empty(trim($this->newMessage))) {
            return;
        }

        // Crear mensaje en base de datos
        $chatMessage = ChatMessage::create([
            'user_id' => Auth::id(),
            'message' => trim($this->newMessage),
        ]);

        // Disparar evento WebSocket
        broadcast(new GlobalChatMessage($chatMessage, Auth::user()));

        $this->newMessage = '';

        // Scroll automático
        $this->dispatch('scroll-to-bottom');
    }

    #[On('echo:global-chat,.new-global-message')]
    public function handleNewGlobalMessage($payload)
    {
        // 🔥 CLAVE: Agregar el mensaje al array
        $this->messages[] = [
            'id' => $payload['id'],
            'message' => $payload['message'],
            'user' => $payload['user'],
            'created_at' => $payload['timestamp'],
            'human_time' => $payload['human_time'],
            'time' => \Carbon\Carbon::parse($payload['timestamp'])->format('H:i'),
            'is_own' => $payload['user']['id'] === Auth::id(),
        ];

        // Mantener máximo 200 mensajes en memoria
        if (count($this->messages) > 200) {
            $this->messages = array_slice($this->messages, -200);
        }

        // 🔥 CRÍTICO: Forzar re-render de Livewire
        $this->dispatch('$refresh');
        $this->dispatch('scroll-to-bottom');
    }

    // NUEVO: Método para actualizar el estado de conexión desde JavaScript
    public function updateConnectionStatus($status)
    {
        $this->connectionStatus = $status;
    }

    public function getListeners()
    {
        return [
            "echo:global-chat,.new-global-message" => 'handleNewGlobalMessage',
        ];
    }
}
