<?php

namespace App\Filament\Pages;

use App\Events\GlobalChatMessage;
use App\Models\ChatMessage;
use Auth;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
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

    private string $apiBaseUrl = 'http://localhost:7000';

    public $messages = [];

    public $isLoading = true;

    public $onlineUsers = 0;

    public $connectionStatus = 'connecting';

    public $viewMode = 'image';

    public $isExtracting = false;

    public $hideEmptyMessages = false; // Nueva propiedad para filtrar mensajes vacíos

    public ?array $sendMessageFormData = [];

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
            $fileType = $this->getFileType($message->message);

            return [
                'id' => $message->id,
                'message' => $message->message,
                'hidden_message' => $message->hidden_message,
                'file_type' => $fileType,
                'file_url' => $message->message ? Storage::url($message->message) : null,
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

    private function getFileType($filePath)
    {
        if (! $filePath) {
            return null;
        }

        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        if (in_array($extension, ['png', 'jpg', 'jpeg', 'gif', 'webp'])) {
            return 'image';
        }

        if (in_array($extension, ['wav', 'mp3', 'ogg', 'm4a'])) {
            return 'audio';
        }

        return null;
    }

    public function sendMessage()
    {
        try {
            $fileData = $this->sendMessageFormData['file'] ?? null;

            if (empty($fileData)) {
                $this->addError('sendMessageFormData.file', 'Debes subir un archivo');

                return;
            }

            $filePath = $this->saveAndGetFilePath($fileData);

            if (! file_exists($filePath)) {
                throw new \Exception('Error al procesar el archivo');
            }

            $fileName = basename($filePath);
            $finalPath = 'messages/'.$fileName;

            Storage::disk('public')->put($finalPath, file_get_contents($filePath));

            $this->cleanupTempFile($filePath);

            $chatMessage = ChatMessage::create([
                'user_id' => Auth::id(),
                'message' => $finalPath,
                'hidden_message' => null,
            ]);

            // Si estamos en modo texto, extraer el mensaje inmediatamente
            if ($this->viewMode === 'text') {
                $fileType = $this->getFileType($finalPath);

                if ($fileType === 'image') {
                    $this->extractSingleImage($chatMessage->id);
                } elseif ($fileType === 'audio') {
                    $this->extractSingleAudio($chatMessage->id);
                }

                // Recargar el mensaje actualizado
                $chatMessage->refresh();
            }

            broadcast(new GlobalChatMessage($chatMessage, Auth::user()));

            $this->sendMessageFormData = [];
            $this->dispatch('scroll-to-bottom');

        } catch (\Exception $e) {
            $this->addError('sendMessageFormData.file', 'Error al subir archivo: '.$e->getMessage());
        }
    }

    // ========================================
    // MÉTODO MEJORADO: Extraer TODOS los mensajes en BATCH
    // ========================================
    public function extractAllMessages()
    {
        $this->isExtracting = true;

        try {
            // Filtrar mensajes que necesitan extracción
            $messagesToExtract = collect($this->messages)
                ->filter(fn ($msg) => $msg['hidden_message'] === null)
                ->values();

            if ($messagesToExtract->isEmpty()) {
                $this->isExtracting = false;

                return;
            }

            // Separar por tipo de archivo
            $imageMessages = $messagesToExtract->filter(fn ($msg) => $msg['file_type'] === 'image');
            $audioMessages = $messagesToExtract->filter(fn ($msg) => $msg['file_type'] === 'audio');

            // Extraer imágenes en batch (si hay)
            if ($imageMessages->isNotEmpty()) {
                $this->extractImageBatch($imageMessages->all());
            }

            // Extraer audios en batch (ahora también soporta batch!)
            if ($audioMessages->isNotEmpty()) {
                $this->extractAudioBatch($audioMessages->all());
            }

        } catch (\Exception $e) {
            \Log::error('Error extracting messages: '.$e->getMessage());
        }

        $this->isExtracting = false;
        $this->loadRecentMessages();
    }

    // ========================================
    // MÉTODO: Extraer múltiples IMÁGENES en batch
    // ========================================
    private function extractImageBatch(array $messages)
    {
        try {
            $request = Http::timeout(120)->asMultipart();
            $messageIds = [];

            // Preparar archivos para el batch
            foreach ($messages as $message) {
                $fullPath = Storage::disk('public')->path($message['message']);

                if (! file_exists($fullPath)) {
                    continue;
                }

                // Attach cada archivo individualmente
                $request->attach(
                    'images',
                    file_get_contents($fullPath),
                    basename($fullPath)
                );

                $messageIds[] = $message['id'];
            }

            if (empty($messageIds)) {
                return;
            }

            // Llamada a la API en batch
            $response = $request->post($this->apiBaseUrl.'/image/stego/extract-batch');

            if ($response->successful()) {
                $results = $response->json()['results'] ?? [];

                // Procesar resultados
                foreach ($results as $result) {
                    $index = $result['index'];
                    $messageId = $messageIds[$index] ?? null;

                    if (! $messageId) {
                        continue;
                    }

                    $chatMessage = ChatMessage::find($messageId);

                    if (! $chatMessage) {
                        continue;
                    }

                    if ($result['status'] === 'error') {
                        $chatMessage->update([
                            'hidden_message' => '[Error: '.$result['error'].']',
                        ]);
                    } elseif ($result['message_length'] > 0) {
                        $chatMessage->update([
                            'hidden_message' => $result['message'],
                        ]);
                    } else {
                        $chatMessage->update([
                            'hidden_message' => '[Sin mensaje oculto]',
                        ]);
                    }
                }
            }

        } catch (\Exception $e) {
            \Log::error('Batch image extraction error: '.$e->getMessage());

            // Fallback: extraer uno por uno
            foreach ($messages as $message) {
                $this->extractSingleImage($message['id']);
            }
        }
    }

    // ========================================
    // NUEVO MÉTODO: Extraer múltiples AUDIOS en batch
    // ========================================
    private function extractAudioBatch(array $messages)
    {
        try {
            $request = Http::timeout(180)->asMultipart(); // Más timeout para audios
            $messageIds = [];

            // Preparar archivos para el batch
            foreach ($messages as $message) {
                $fullPath = Storage::disk('public')->path($message['message']);

                if (! file_exists($fullPath)) {
                    continue;
                }

                // Attach cada archivo individualmente
                $request->attach(
                    'audios',
                    file_get_contents($fullPath),
                    basename($fullPath)
                );

                $messageIds[] = $message['id'];
            }

            if (empty($messageIds)) {
                return;
            }

            // Llamada a la API en batch
            $response = $request->post($this->apiBaseUrl.'/audio/stego/extract-batch');

            if ($response->successful()) {
                $results = $response->json()['results'] ?? [];

                // Procesar resultados
                foreach ($results as $result) {
                    $index = $result['index'];
                    $messageId = $messageIds[$index] ?? null;

                    if (! $messageId) {
                        continue;
                    }

                    $chatMessage = ChatMessage::find($messageId);

                    if (! $chatMessage) {
                        continue;
                    }

                    if ($result['status'] === 'error') {
                        $chatMessage->update([
                            'hidden_message' => '[Error: '.$result['error'].']',
                        ]);
                    } elseif ($result['message_length'] > 0) {
                        $chatMessage->update([
                            'hidden_message' => $result['message'],
                        ]);
                    } else {
                        $chatMessage->update([
                            'hidden_message' => '[Sin mensaje oculto]',
                        ]);
                    }
                }
            }

        } catch (\Exception $e) {
            \Log::error('Batch audio extraction error: '.$e->getMessage());

            // Fallback: extraer uno por uno
            foreach ($messages as $message) {
                $this->extractSingleAudio($message['id']);
            }
        }
    }

    // ========================================
    // MÉTODO: Extraer UNA imagen individual (fallback)
    // ========================================
    private function extractSingleImage($messageId)
    {
        try {
            $chatMessage = ChatMessage::find($messageId);

            if (! $chatMessage || $chatMessage->hidden_message !== null) {
                return;
            }

            $fullPath = Storage::disk('public')->path($chatMessage->message);

            if (! file_exists($fullPath)) {
                throw new \Exception('Archivo no encontrado');
            }

            $response = Http::timeout(60)
                ->attach(
                    'image',
                    file_get_contents($fullPath),
                    basename($fullPath)
                )
                ->post($this->apiBaseUrl.'/image/stego/extract');

            if ($response->successful()) {
                $result = $response->json();
                $extractedMessage = $result['message'] ?? null;
                $messageLength = $result['message_length'] ?? 0;

                $chatMessage->update([
                    'hidden_message' => $messageLength > 0 ? $extractedMessage : '[Sin mensaje oculto]',
                ]);
            } else {
                throw new \Exception('Error en la API');
            }

        } catch (\Exception $e) {
            $chatMessage->update([
                'hidden_message' => '[Error: '.$e->getMessage().']',
            ]);
        }
    }

    // ========================================
    // MÉTODO: Extraer UN audio individual (fallback)
    // ========================================
    private function extractSingleAudio($messageId)
    {
        try {
            $chatMessage = ChatMessage::find($messageId);

            if (! $chatMessage || $chatMessage->hidden_message !== null) {
                return;
            }

            $fullPath = Storage::disk('public')->path($chatMessage->message);

            if (! file_exists($fullPath)) {
                throw new \Exception('Archivo no encontrado');
            }

            $response = Http::timeout(60)
                ->attach(
                    'audio',
                    file_get_contents($fullPath),
                    basename($fullPath)
                )
                ->post($this->apiBaseUrl.'/audio/stego/extract');

            if ($response->successful()) {
                $result = $response->json();
                $extractedMessage = $result['message'] ?? null;
                $messageLength = $result['message_length'] ?? 0;

                $chatMessage->update([
                    'hidden_message' => $messageLength > 0 ? $extractedMessage : '[Sin mensaje oculto]',
                ]);
            } else {
                throw new \Exception('Error en la API');
            }

        } catch (\Exception $e) {
            $chatMessage->update([
                'hidden_message' => '[Error: '.$e->getMessage().']',
            ]);
        }
    }

    // ========================================
    // DETECTAR TIPO DE ARCHIVO
    // ========================================
    private function detectFileType($uploadedFile): string
    {
        if (is_array($uploadedFile) && ! empty($uploadedFile)) {
            $firstFile = reset($uploadedFile);

            if (is_object($firstFile) && method_exists($firstFile, 'getClientOriginalName')) {
                $fileName = $firstFile->getClientOriginalName();
            } elseif (is_object($firstFile) && method_exists($firstFile, 'getFilename')) {
                $fileName = $firstFile->getFilename();
            } else {
                $fileName = '';
            }
        } elseif (is_string($uploadedFile)) {
            $fileName = $uploadedFile;
        } elseif (is_object($uploadedFile) && method_exists($uploadedFile, 'getClientOriginalName')) {
            $fileName = $uploadedFile->getClientOriginalName();
        } else {
            $fileName = '';
        }

        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if (in_array($extension, ['wav', 'mp3'])) {
            return 'audio';
        } elseif (in_array($extension, ['png', 'jpg', 'jpeg', 'bmp'])) {
            return 'image';
        }

        return 'image';
    }

    // ========================================
    // GUARDAR ARCHIVO Y OBTENER RUTA
    // ========================================
    private function saveAndGetFilePath($uploadedFile): string
    {
        $fileType = $this->detectFileType($uploadedFile);
        $extension = $fileType === 'audio' ? 'wav' : 'png';
        $fileName = 'msg_'.uniqid().'_'.time().'.'.$extension;

        $disk = Storage::disk('local');

        if (! $disk->exists('temp')) {
            $disk->makeDirectory('temp');
        }

        if (is_array($uploadedFile) && ! empty($uploadedFile)) {
            $firstFile = reset($uploadedFile);

            if (is_object($firstFile) && method_exists($firstFile, 'getRealPath')) {
                $realPath = $firstFile->getRealPath();
                if (file_exists($realPath)) {
                    $content = file_get_contents($realPath);
                    $disk->put('temp/'.$fileName, $content);

                    return $disk->path('temp/'.$fileName);
                }

                if (method_exists($firstFile, 'path')) {
                    $path = $firstFile->path();
                    if (file_exists($path)) {
                        $content = file_get_contents($path);
                        $disk->put('temp/'.$fileName, $content);

                        return $disk->path('temp/'.$fileName);
                    }
                }
            }

            if (! is_object($firstFile)) {
                return $this->saveAndGetFilePath($firstFile);
            }
        }

        if (is_string($uploadedFile)) {
            $possiblePaths = [
                storage_path('app/livewire-tmp/'.$uploadedFile),
                storage_path('app/private/livewire-tmp/'.$uploadedFile),
                storage_path('app/private/'.$uploadedFile),
                storage_path('app/uploads/'.$uploadedFile),
                storage_path('app/'.$uploadedFile),
            ];

            $baseName = basename($uploadedFile);
            $possiblePaths[] = storage_path('app/livewire-tmp/'.$baseName);
            $possiblePaths[] = storage_path('app/private/livewire-tmp/'.$baseName);
            $possiblePaths[] = storage_path('app/private/'.$baseName);

            foreach ($possiblePaths as $path) {
                if (file_exists($path)) {
                    $content = file_get_contents($path);
                    $disk->put('temp/'.$fileName, $content);

                    return $disk->path('temp/'.$fileName);
                }
            }

            $livewireTmpPath = storage_path('app/livewire-tmp');
            if (is_dir($livewireTmpPath)) {
                $iterator = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($livewireTmpPath, \RecursiveDirectoryIterator::SKIP_DOTS)
                );

                foreach ($iterator as $file) {
                    if ($file->isFile()) {
                        $fileNameFound = $file->getFilename();
                        if ($fileNameFound === $uploadedFile || $fileNameFound === basename($uploadedFile) ||
                            strpos($fileNameFound, basename($uploadedFile)) !== false) {
                            $content = file_get_contents($file->getRealPath());
                            $tempFileName = 'msg_'.uniqid().'_'.time().'.'.$extension;
                            $disk->put('temp/'.$tempFileName, $content);

                            return $disk->path('temp/'.$tempFileName);
                        }
                    }
                }
            }

            $privateLivewireTmpPath = storage_path('app/private/livewire-tmp');
            if (is_dir($privateLivewireTmpPath)) {
                $iterator = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($privateLivewireTmpPath, \RecursiveDirectoryIterator::SKIP_DOTS)
                );

                foreach ($iterator as $file) {
                    if ($file->isFile()) {
                        $fileNameFound = $file->getFilename();
                        if ($fileNameFound === $uploadedFile || $fileNameFound === basename($uploadedFile) ||
                            strpos($fileNameFound, basename($uploadedFile)) !== false) {
                            $content = file_get_contents($file->getRealPath());
                            $tempFileName = 'msg_'.uniqid().'_'.time().'.'.$extension;
                            $disk->put('temp/'.$tempFileName, $content);

                            return $disk->path('temp/'.$tempFileName);
                        }
                    }
                }
            }
        }

        if (is_object($uploadedFile) && method_exists($uploadedFile, 'getRealPath')) {
            $realPath = $uploadedFile->getRealPath();
            if (file_exists($realPath)) {
                $content = file_get_contents($realPath);
                $disk->put('temp/'.$fileName, $content);

                return $disk->path('temp/'.$fileName);
            }
        }

        throw new \Exception('No se pudo procesar el archivo');
    }

    private function cleanupTempFile(string $path): void
    {
        if (file_exists($path) && strpos($path, 'msg_') !== false) {
            @unlink($path);
        }
    }

    // ========================================
    // TOGGLE VIEW MODE
    // ========================================
    public function toggleViewMode()
    {
        $this->viewMode = $this->viewMode === 'image' ? 'text' : 'image';

        // Si cambia a modo imagen, desactivar el filtro
        if ($this->viewMode === 'image') {
            $this->hideEmptyMessages = false;
        }

        // Si cambia a modo texto, extraer mensajes que no tengan el hidden_message
        if ($this->viewMode === 'text') {
            $this->extractAllMessages();
        }
    }

    // ========================================
    // TOGGLE HIDE EMPTY MESSAGES
    // ========================================
    public function toggleHideEmptyMessages()
    {
        $this->hideEmptyMessages = ! $this->hideEmptyMessages;
    }

    // ========================================
    // COMPUTED PROPERTY: Mensajes filtrados
    // ========================================
    public function getFilteredMessagesProperty()
    {
        if ($this->viewMode !== 'text' || ! $this->hideEmptyMessages) {
            return $this->messages;
        }

        // Filtrar mensajes que tienen contenido oculto válido
        return array_values(array_filter($this->messages, function ($message) {
            $hiddenMsg = $message['hidden_message'] ?? null;

            // Mantener si:
            // - Está siendo extraído (null)
            // - Tiene mensaje válido (no empieza con [Sin mensaje] ni [Error])
            return $hiddenMsg === null ||
                (! str_starts_with($hiddenMsg, '[Sin mensaje') &&
                    ! str_starts_with($hiddenMsg, '[Error'));
        }));
    }

    #[On('echo:global-chat,.new-global-message')]
    public function handleNewGlobalMessage($payload)
    {
        $fileType = $this->getFileType($payload['message']);

        $newMessage = [
            'id' => $payload['id'],
            'message' => $payload['message'],
            'hidden_message' => $payload['hidden_message'] ?? null, // Usar el hidden_message del payload
            'file_type' => $fileType,
            'file_url' => $payload['message'] ? Storage::url($payload['message']) : null,
            'user' => $payload['user'],
            'created_at' => $payload['timestamp'],
            'human_time' => $payload['human_time'],
            'time' => \Carbon\Carbon::parse($payload['timestamp'])->format('H:i'),
            'is_own' => $payload['user']['id'] === Auth::id(),
        ];

        // Si estamos en modo texto y no tiene hidden_message, extraer ahora
        if ($this->viewMode === 'text' && $newMessage['hidden_message'] === null) {
            if ($fileType === 'image') {
                $this->extractSingleImage($payload['id']);
            } elseif ($fileType === 'audio') {
                $this->extractSingleAudio($payload['id']);
            }

            // Recargar el mensaje actualizado desde la BD
            $chatMessage = ChatMessage::find($payload['id']);
            if ($chatMessage) {
                $newMessage['hidden_message'] = $chatMessage->hidden_message;
            }
        }

        $this->messages[] = $newMessage;

        if (count($this->messages) > 200) {
            $this->messages = array_slice($this->messages, -200);
        }

        $this->dispatch('$refresh');
        $this->js(<<<'JS'
        setTimeout(() => {
            const event = new Event('scroll-to-bottom');
            window.dispatchEvent(event);
        }, 100);
    JS);
    }

    public function updateConnectionStatus($status)
    {
        $this->connectionStatus = $status;
    }

    public function getListeners()
    {
        return [
            'echo:global-chat,.new-global-message' => 'handleNewGlobalMessage',
        ];
    }

    protected function getForms(): array
    {
        return [
            'sendMessageForm',
        ];
    }

    public function sendMessageForm(Schema $form): Schema
    {
        return $form
            ->schema([
                FileUpload::make('file')
                    ->label('Subir Archivo Esteganográfico')
                    ->acceptedFileTypes([
                        'image/png', 'image/jpeg', 'image/jpg',
                        'audio/wav', 'audio/mp3', 'audio/x-wav',
                    ])
                    ->disk('public')
                    ->directory('messages')
                    ->visibility('public')
                    ->maxSize(1024)
                    ->required()
                    ->helperText('📎 PNG, JPG, JPEG, WAV, MP3 con esteganografía. Máximo 1MB')
                    ->imagePreviewHeight('100')
                    ->storeFiles(false)
                    ->live(),
            ])
            ->statePath('sendMessageFormData');
    }
}
