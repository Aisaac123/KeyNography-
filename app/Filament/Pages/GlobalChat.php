<?php

namespace App\Filament\Pages;

use App\Events\GlobalChatMessage;
use App\Models\ChatMessage;
use Auth;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Notifications\Notification;
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
    public $hideEmptyMessages = false;
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
            return $this->formatMessage($message);
        })->toArray();
    }

    // ========================================
    // ✅ MÉTODO CORREGIDO: formatMessage
    // ========================================
    private function formatMessage($message): array
    {
        $fileType = $this->getFileType($message->message);

        return [
            'id' => $message->id,
            'message' => $message->message,
            'file_type' => $fileType,
            'file_url' => $message->message ? Storage::url($message->message) : null,

            // ✅ Contenido oculto
            'hidden_content_type' => $message->hidden_content_type ?? null,
            'hidden_message' => $message->hidden_message ?? null,

            // ✅ Documento oculto
            'hidden_document_path' => $message->hidden_document_path ?? null,
            'hidden_document_filename' => $message->hidden_document_filename ?? null,
            'hidden_document_mime_type' => $message->hidden_document_mime_type ?? null,
            'hidden_document_size' => $message->hidden_document_size ?? null,
            'formatted_document_size' => method_exists($message, 'getFormattedDocumentSize')
                ? $message->getFormattedDocumentSize()
                : null,
            'document_icon' => method_exists($message, 'getDocumentIcon')
                ? $message->getDocumentIcon()
                : null,
            'is_password_protected' => $message->is_password_protected ?? false,

            // Usuario y timestamps
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
    }

    private function getFileType($filePath)
    {
        if (!$filePath) return null;

        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        if (in_array($extension, ['png', 'jpg', 'jpeg', 'gif', 'webp'])) {
            return 'image';
        }

        if (in_array($extension, ['wav'])) {
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
                'hidden_content_type' => null, // ✅ Inicializar en null
            ]);

            // Si estamos en modo texto, extraer el mensaje inmediatamente
            if ($this->viewMode === 'text') {
                $fileType = $this->getFileType($finalPath);

                if ($fileType === 'image') {
                    $this->extractSingleImage($chatMessage->id);
                } elseif ($fileType === 'audio') {
                    $this->extractSingleAudio($chatMessage->id);
                }

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
    // ✅ MÉTODO CORREGIDO: extractAllMessages
    // ========================================
    public function extractAllMessages()
    {
        $this->isExtracting = true;

        try {
            // ✅ Usar isset() para evitar errores con claves inexistentes
            $messagesToExtract = collect($this->messages)
                ->filter(fn ($msg) => !isset($msg['hidden_content_type']) || $msg['hidden_content_type'] === null)
                ->values();

            if ($messagesToExtract->isEmpty()) {
                $this->isExtracting = false;
                return;
            }

            $this->extractUnifiedBatch($messagesToExtract->all());

        } catch (\Exception $e) {
            \Log::error('Error extracting messages: ' . $e->getMessage());
            Notification::make()
                ->danger()
                ->title('Error al extraer mensajes')
                ->body($e->getMessage())
                ->send();
        }

        $this->isExtracting = false;
        $this->loadRecentMessages();
    }

    private function extractUnifiedBatch(array $messages)
    {
        try {
            $request = Http::timeout(300)->asMultipart();
            $messageIds = [];

            foreach ($messages as $message) {
                $fullPath = Storage::disk('public')->path($message['message']);

                if (!file_exists($fullPath)) {
                    continue;
                }

                $request->attach(
                    'files',
                    file_get_contents($fullPath),
                    basename($fullPath)
                );

                $messageIds[] = $message['id'];
            }

            if (empty($messageIds)) {
                return;
            }

            $response = $request->post($this->apiBaseUrl . '/chat/extract-batch');

            if ($response->successful()) {
                $data = $response->json();
                $results = $data['results'] ?? [];

                \Log::info('Unified extraction results:', [
                    'total' => $data['total'] ?? 0,
                    'successful' => $data['successful'] ?? 0,
                    'messages_found' => $data['messages_found'] ?? 0,
                    'documents_found' => $data['documents_found'] ?? 0,
                ]);

                foreach ($results as $result) {
                    $index = $result['index'];
                    $messageId = $messageIds[$index] ?? null;

                    if (!$messageId) continue;

                    $chatMessage = ChatMessage::find($messageId);
                    if (!$chatMessage) continue;

                    $this->processExtractionResult($chatMessage, $result);
                }

                Notification::make()
                    ->success()
                    ->title('Extracción completada')
                    ->body("Mensajes: {$data['messages_found']}, Documentos: {$data['documents_found']}")
                    ->send();
            } else {
                throw new \Exception('Error en la API: ' . $response->status());
            }

        } catch (\Exception $e) {
            \Log::error('Unified batch extraction error: ' . $e->getMessage());
            throw $e;
        }
    }

    private function processExtractionResult(ChatMessage $chatMessage, array $result)
    {
        // CASO 1: ERROR
        if ($result['status'] === 'error') {
            $chatMessage->update([
                'hidden_content_type' => 'error',
                'hidden_message' => '[Error: ' . ($result['error'] ?? 'Desconocido') . ']',
            ]);
            return;
        }

        $contentType = $result['content_type'] ?? null;

        // CASO 2: SIN CONTENIDO
        if ($contentType === null) {
            $chatMessage->update([
                'hidden_content_type' => 'empty',
                'hidden_message' => '[Sin contenido oculto]',
            ]);
            return;
        }

        // CASO 3: DOCUMENTO OCULTO
        if ($contentType === 'document') {
            $this->saveHiddenDocument($chatMessage, $result);
            return;
        }

        // CASO 4: MENSAJE DE TEXTO
        if ($contentType === 'text') {
            $chatMessage->update([
                'hidden_content_type' => 'text',
                'hidden_message' => $result['message'] ?? '',
            ]);
            return;
        }
    }

    private function saveHiddenDocument(ChatMessage $chatMessage, array $result)
    {
        try {
            if ($result['is_password_protected'] ?? false) {
                $chatMessage->update([
                    'hidden_content_type' => 'document',
                    'is_password_protected' => true,
                    'hidden_message' => '[🔒 Documento protegido con contraseña]',
                ]);
                return;
            }

            $documentBase64 = $result['document_base64'] ?? null;
            if (!$documentBase64) {
                throw new \Exception('Documento base64 no encontrado');
            }

            $documentBytes = base64_decode($documentBase64);
            $originalFilename = $result['original_filename'] ?? 'document.bin';

            $extension = pathinfo($originalFilename, PATHINFO_EXTENSION);
            $uniqueName = 'hidden_docs/' . uniqid() . '_' . time() . '.' . $extension;

            Storage::disk('public')->put($uniqueName, $documentBytes);

            $chatMessage->update([
                'hidden_content_type' => 'document',
                'hidden_document_path' => $uniqueName,
                'hidden_document_filename' => $originalFilename,
                'hidden_document_mime_type' => $result['mime_type'] ?? 'application/octet-stream',
                'hidden_document_size' => $result['document_size'] ?? strlen($documentBytes),
                'is_password_protected' => false,
            ]);

        } catch (\Exception $e) {
            \Log::error('Error saving hidden document: ' . $e->getMessage());
            $chatMessage->update([
                'hidden_content_type' => 'error',
                'hidden_message' => '[Error al guardar documento: ' . $e->getMessage() . ']',
            ]);
        }
    }

    public function downloadHiddenDocument($messageId)
    {
        try {
            $chatMessage = ChatMessage::findOrFail($messageId);

            if (!$chatMessage->hasHiddenDocument()) {
                Notification::make()
                    ->danger()
                    ->title('Error')
                    ->body('Este mensaje no contiene un documento oculto')
                    ->send();
                return;
            }

            if (!$chatMessage->hidden_document_path) {
                Notification::make()
                    ->danger()
                    ->title('Error')
                    ->body('Ruta del documento no encontrada')
                    ->send();
                return;
            }

            $fullPath = Storage::disk('public')->path($chatMessage->hidden_document_path);

            if (!file_exists($fullPath)) {
                Notification::make()
                    ->danger()
                    ->title('Error')
                    ->body('Archivo no encontrado en el servidor')
                    ->send();
                return;
            }

            return response()->download(
                $fullPath,
                $chatMessage->hidden_document_filename ?? 'document.bin'
            );

        } catch (\Exception $e) {
            \Log::error('Error downloading document: ' . $e->getMessage());
            Notification::make()
                ->danger()
                ->title('Error al descargar')
                ->body($e->getMessage())
                ->send();
        }
    }

    // ========================================
    // ✅ MÉTODO CORREGIDO: extractSingleImage
    // ========================================
    private function extractSingleImage($messageId)
    {
        try {
            $chatMessage = ChatMessage::find($messageId);

            if (!$chatMessage || $chatMessage->hidden_content_type !== null) {
                return;
            }

            $fullPath = Storage::disk('public')->path($chatMessage->message);

            if (!file_exists($fullPath)) {
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
                    'hidden_content_type' => $messageLength > 0 ? 'text' : 'empty',
                    'hidden_message' => $messageLength > 0 ? $extractedMessage : '[Sin mensaje oculto]',
                ]);
            } else {
                throw new \Exception('Error en la API');
            }

        } catch (\Exception $e) {
            $chatMessage->update([
                'hidden_content_type' => 'error',
                'hidden_message' => '[Error: '.$e->getMessage().']',
            ]);
        }
    }

    // ========================================
    // ✅ MÉTODO CORREGIDO: extractSingleAudio
    // ========================================
    private function extractSingleAudio($messageId)
    {
        try {
            $chatMessage = ChatMessage::find($messageId);

            if (!$chatMessage || $chatMessage->hidden_content_type !== null) {
                return;
            }

            $fullPath = Storage::disk('public')->path($chatMessage->message);

            if (!file_exists($fullPath)) {
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
                    'hidden_content_type' => $messageLength > 0 ? 'text' : 'empty',
                    'hidden_message' => $messageLength > 0 ? $extractedMessage : '[Sin mensaje oculto]',
                ]);
            } else {
                throw new \Exception('Error en la API');
            }

        } catch (\Exception $e) {
            $chatMessage->update([
                'hidden_content_type' => 'error',
                'hidden_message' => '[Error: '.$e->getMessage().']',
            ]);
        }
    }

    private function detectFileType($uploadedFile): string
    {
        if (is_array($uploadedFile) && !empty($uploadedFile)) {
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

        if (in_array($extension, ['wav'])) {
            return 'audio';
        } elseif (in_array($extension, ['png', 'jpg', 'jpeg', 'bmp'])) {
            return 'image';
        }

        return 'image';
    }

    private function saveAndGetFilePath($uploadedFile): string
    {
        $fileType = $this->detectFileType($uploadedFile);
        $extension = $fileType === 'audio' ? 'wav' : 'png';
        $fileName = 'msg_'.uniqid().'_'.time().'.'.$extension;

        $disk = Storage::disk('local');

        if (!$disk->exists('temp')) {
            $disk->makeDirectory('temp');
        }

        if (is_array($uploadedFile) && !empty($uploadedFile)) {
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

            if (!is_object($firstFile)) {
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

    public function toggleViewMode()
    {
        $this->viewMode = $this->viewMode === 'image' ? 'text' : 'image';

        if ($this->viewMode === 'image') {
            $this->hideEmptyMessages = false;
        }

        if ($this->viewMode === 'text') {
            $this->extractAllMessages();
        }
    }

    public function toggleHideEmptyMessages()
    {
        $this->hideEmptyMessages = !$this->hideEmptyMessages;
    }

    public function getFilteredMessagesProperty()
    {
        if ($this->viewMode !== 'text' || !$this->hideEmptyMessages) {
            return $this->messages;
        }

        return array_values(array_filter($this->messages, function ($message) {
            $hiddenMsg = $message['hidden_message'] ?? null;

            return $hiddenMsg === null ||
                (!str_starts_with($hiddenMsg, '[Sin mensaje') &&
                    !str_starts_with($hiddenMsg, '[Error'));
        }));
    }

    // ========================================
    // ✅ MÉTODO CORREGIDO: handleNewGlobalMessage
    // ========================================
    #[On('echo:global-chat,.new-global-message')]
    public function handleNewGlobalMessage($payload)
    {
        $fileType = $this->getFileType($payload['message']);

        $newMessage = [
            'id' => $payload['id'],
            'message' => $payload['message'],
            'file_type' => $fileType,
            'file_url' => $payload['message'] ? Storage::url($payload['message']) : null,

            // ✅ Campos de contenido oculto
            'hidden_content_type' => $payload['hidden_content_type'] ?? null,
            'hidden_message' => $payload['hidden_message'] ?? null,
            'hidden_document_path' => $payload['hidden_document_path'] ?? null,
            'hidden_document_filename' => $payload['hidden_document_filename'] ?? null,
            'hidden_document_mime_type' => $payload['hidden_document_mime_type'] ?? null,
            'hidden_document_size' => $payload['hidden_document_size'] ?? null,
            'is_password_protected' => $payload['is_password_protected'] ?? false,

            'user' => $payload['user'],
            'created_at' => $payload['timestamp'],
            'human_time' => $payload['human_time'],
            'time' => \Carbon\Carbon::parse($payload['timestamp'])->format('H:i'),
            'is_own' => $payload['user']['id'] === Auth::id(),
        ];

        // Si estamos en modo texto y no tiene hidden_content_type, extraer ahora
        if ($this->viewMode === 'text' && !isset($newMessage['hidden_content_type'])) {
            if ($fileType === 'image') {
                $this->extractSingleImage($payload['id']);
            } elseif ($fileType === 'audio') {
                $this->extractSingleAudio($payload['id']);
            }

            $chatMessage = ChatMessage::find($payload['id']);
            if ($chatMessage) {
                $newMessage['hidden_content_type'] = $chatMessage->hidden_content_type;
                $newMessage['hidden_message'] = $chatMessage->hidden_message;
                $newMessage['hidden_document_path'] = $chatMessage->hidden_document_path;
                $newMessage['hidden_document_filename'] = $chatMessage->hidden_document_filename;
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
                        'audio/wav',
                    ])
                    ->disk('public')
                    ->directory('messages')
                    ->visibility('public')
                    ->maxSize(1024)
                    ->required()
                    ->helperText('📎 PNG, JPG, JPEG, WAV con esteganografía. Máximo 1MB')
                    ->imagePreviewHeight('100')
                    ->storeFiles(false)
                    ->live(),
            ])
            ->statePath('sendMessageFormData');
    }
}
