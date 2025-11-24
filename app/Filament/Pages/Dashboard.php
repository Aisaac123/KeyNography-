<?php

namespace App\Filament\Pages;

use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Livewire\WithFileUploads;

class Dashboard extends \Filament\Pages\Dashboard implements Forms\Contracts\HasForms
{
    use InteractsWithForms;
    use WithFileUploads;

    protected static string|null|\BackedEnum $navigationIcon = 'heroicon-o-lock-closed';

    protected static string|null|\BackedEnum $activeNavigationIcon = 'heroicon-s-lock-open';

    protected static ?string $navigationLabel = 'Esteganografía';

    protected static ?string $title = '';

    protected string $view = 'dashboard';

    public function getMaxContentWidth(): Width
    {
        return Width::MaxContent;
    }

    // API Base URL
    private string $apiBaseUrl = 'http://localhost:7000';

    // NUEVO: Modo de operación (message o document)
    public string $embedMode = 'message';

    public string $extractMode = 'message';

    // Estados de los formularios
    public ?array $embedData = [];

    public ?array $extractData = [];

    public function setBothModes($mode)
    {
        $this->embedMode = $mode;
        $this->extractMode = $mode;

        // Limpiar resultados anteriores
        $this->embedResult = null;
        $this->extractResult = null;

    }

    // Resultados
    public ?string $embedResult = null;

    public ?string $extractResult = null;

    protected function getForms(): array
    {
        return [
            'embedForm',
            'extractForm',
        ];
    }


    // ========================================
    // MÉTODO: Detectar tipo de archivo
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

        if (in_array($extension, ['wav'])) {
            return 'audio';
        } elseif (in_array($extension, ['png', 'jpg', 'jpeg', 'bmp'])) {
            return 'image';
        }

        return 'image';
    }

    // ========================================
    // MÉTODO: Guardar archivo temporal
    // ========================================
    private function saveAndGetFilePath($uploadedFile, string $fileType): string
    {
        // ✅ SOLUCIÓN: Detectar la extensión real del archivo
        $extension = $this->getFileExtension($uploadedFile, $fileType);

        $fileName = 'temp_'.uniqid().'_'.time().'.'.$extension;
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
                return $this->saveAndGetFilePath($firstFile, $fileType);
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

                            // ✅ Usar la extensión real del archivo encontrado
                            $realExtension = pathinfo($file->getRealPath(), PATHINFO_EXTENSION);
                            if ($realExtension) {
                                $extension = $realExtension;
                            }

                            $tempFileName = 'temp_'.uniqid().'_'.time().'.'.$extension;
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

    private function getFileExtension($uploadedFile, string $fileType): string
    {
        // Si es audio o imagen portadora, usar extensión por defecto
        if ($fileType === 'audio') {
            return 'wav';
        }

        if ($fileType === 'image') {
            return 'png';
        }

        // ✅ Para documentos, detectar la extensión real
        if ($fileType === 'document') {
            // Intentar obtener el nombre original del archivo
            $originalName = '';

            if (is_array($uploadedFile) && ! empty($uploadedFile)) {
                $firstFile = reset($uploadedFile);
                if (is_object($firstFile) && method_exists($firstFile, 'getClientOriginalName')) {
                    $originalName = $firstFile->getClientOriginalName();
                }
            } elseif (is_object($uploadedFile) && method_exists($uploadedFile, 'getClientOriginalName')) {
                $originalName = $uploadedFile->getClientOriginalName();
            }

            // Extraer extensión
            if ($originalName) {
                $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
                if ($ext) {
                    return $ext;
                }
            }
        }

        // Fallback por defecto
        return 'tmp';
    }

    // ========================================
    // MÉTODO: Limpiar archivo temporal
    // ========================================
    private function cleanupTempFile(string $path): void
    {
        if (file_exists($path) && strpos($path, 'temp_') !== false) {
            @unlink($path);
        }
    }

    // ========================================
    // FORMULARIO DE INFECTAR
    // ========================================
    public function embedForm(Schema $form): Schema
    {
        return $form
            ->schema([
                Section::make('Ocultar Contenido en Archivos Multimedia')
                    ->description('Elige entre ocultar un mensaje de texto o un documento completo')
                    ->schema([
                        Forms\Components\FileUpload::make('carrier_file')
                            ->label('Archivo Portador (Imagen o Audio)')
                            ->acceptedFileTypes([
                                'image/png', 'image/jpeg', 'image/jpg',
                                'audio/wav', 'audio/x-wav', 'audio/wave',
                                '.png', '.jpg', '.jpeg', '.wav',
                            ])
                            ->maxSize(10240)
                            ->required()
                            ->storeFiles(false)
                            ->helperText('⚠️ Formatos: PNG, JPG, JPEG, WAV. Máximo 10MB')
                            ->columnSpanFull()
                            ->live(),

                        // Contenido a ocultar (condicional según modo)
                        Forms\Components\Textarea::make('message')
                            ->label('Mensaje Secreto')
                            ->placeholder('Escribe el mensaje que deseas ocultar...')
                            ->required($this->embedMode === 'message')
                            ->visible($this->embedMode === 'message')
                            ->rows(4)
                            ->maxLength(10000)
                            ->columnSpanFull(),

                        Forms\Components\FileUpload::make('document')
                            ->label('Documento a Ocultar')
                            ->acceptedFileTypes([
                                'application/pdf',
                                'application/msword',
                                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                                'application/vnd.ms-excel',
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                'text/plain',
                                'application/zip',
                                '.pdf', '.doc', '.docx', '.xls', '.xlsx', '.txt', '.zip',
                            ])
                            ->maxSize(5120)
                            ->required($this->embedMode === 'document')
                            ->visible($this->embedMode === 'document')
                            ->storeFiles(false)
                            ->helperText('⚠️ Formatos: PDF, DOC, DOCX, XLS, XLSX, TXT, ZIP. Máximo 5MB')
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('password')
                            ->label('Contraseña (Opcional)')
                            ->password()
                            ->revealable()
                            ->placeholder('Proteger con contraseña')
                            ->visible($this->embedMode === 'document')
                            ->helperText('Si estableces una contraseña, será necesaria para extraer el documento')
                            ->maxLength(50),

                        Actions::make([
                            Action::make('embed')
                                ->label($this->embedMode === 'message' ? '🔒 Ocultar Mensaje' : '📦 Ocultar Documento')
                                ->color('danger')
                                ->size('lg')
                                ->requiresConfirmation()
                                ->modalHeading($this->embedMode === 'message' ? '¿Ocultar mensaje?' : '¿Ocultar documento?')
                                ->modalDescription($this->embedMode === 'message'
                                    ? 'Se ocultará el mensaje dentro del archivo seleccionado.'
                                    : 'Se ocultará el documento completo (comprimido y cifrado) dentro del archivo seleccionado.')
                                ->action('embedContent'),
                        ])->columnSpanFull(),
                    ])
                    ->columns(1),
            ])
            ->statePath('embedData');
    }

    // ========================================
    // FORMULARIO DE EXTRAER
    // ========================================
    public function extractForm(Schema $form): Schema
    {
        return $form
            ->schema([
                Section::make('Extraer Contenido Oculto')
                    ->description('Extrae mensajes o documentos ocultos de archivos multimedia')
                    ->schema([
                        Forms\Components\FileUpload::make('file')
                            ->label('Archivo con Contenido Oculto (Imagen o Audio)')
                            ->acceptedFileTypes([
                                'image/png', 'image/jpeg', 'image/jpg',
                                'audio/wav', 'audio/x-wav', 'audio/wave',
                                '.png', '.jpg', '.jpeg', '.wav',
                            ])
                            ->maxSize(10240)
                            ->required()
                            ->storeFiles(false)
                            ->helperText('⚠️ Formatos: PNG, JPG, JPEG, WAV. Máximo 10MB')
                            ->live(),

                        Forms\Components\TextInput::make('password')
                            ->label('Contraseña (Si el documento está protegido)')
                            ->password()
                            ->revealable()
                            ->placeholder('Ingresa la contraseña')
                            ->visible($this->extractMode === 'document')
                            ->maxLength(50)
                            ->columnSpanFull(),

                        Actions::make([
                            Action::make('extract')
                                ->label($this->extractMode === 'message' ? '🔓 Extraer Mensaje' : '📂 Extraer Documento')
                                ->color('primary')
                                ->size('lg')
                                ->action('extractContent'),
                        ])->columnSpanFull(),
                    ])
                    ->columns(1),
            ])
            ->statePath('extractData');
    }

    // ========================================
    // ACCIÓN: OCULTAR CONTENIDO
    // ========================================
    public function embedContent(): void
    {
        try {
            $carrierFile = $this->embedData['carrier_file'] ?? null;

            if (empty($carrierFile)) {
                throw new \Exception('Debe seleccionar un archivo portador');
            }

            $fileType = $this->detectFileType($carrierFile);
            $carrierPath = $this->saveAndGetFilePath($carrierFile, $fileType);

            if (! file_exists($carrierPath)) {
                throw new \Exception('Error al procesar el archivo portador');
            }

            $fieldName = $fileType === 'audio' ? 'audio' : 'image';

            // ========================================
            // MODO MENSAJE
            // ========================================
            if ($this->embedMode === 'message') {
                $message = $this->embedData['message'] ?? '';

                if (empty($message)) {
                    throw new \Exception('Debe ingresar un mensaje');
                }

                $endpoint = $fileType === 'audio' ? '/audio/stego/embed' : '/image/stego/embed';
                $url = $this->apiBaseUrl.$endpoint.'?message='.urlencode($message);

                $response = Http::timeout(60)
                    ->attach($fieldName, file_get_contents($carrierPath), basename($carrierPath))
                    ->post($url, ['message' => $message]);

                $this->cleanupTempFile($carrierPath);

                if ($response->successful()) {
                    $result = $response->json();
                    $this->saveEmbedResult($result, $fileType, 'message', $message);

                    Notification::make()
                        ->success()
                        ->title('¡Mensaje ocultado exitosamente!')
                        ->body("Capacidad usada: {$result['capacity_used']}%")
                        ->send();
                } else {
                    throw new \Exception('Error al comunicarse con la API');
                }
            }
            // ========================================
            // MODO DOCUMENTO
            // ========================================
            else {
                $document = $this->embedData['document'] ?? null;

                if (empty($document)) {
                    throw new \Exception('Debe seleccionar un documento');
                }

                $documentPath = $this->saveAndGetFilePath($document, 'document');

                if (! file_exists($documentPath)) {
                    throw new \Exception('Error al procesar el documento');
                }

                $endpoint = $fileType === 'audio'
                    ? '/audio/stego/embed-document'
                    : '/image/stego/embed-document';

                $userId = auth()->user()->id ?? 'Anonymous';
                $password = $this->embedData['password'] ?? null;
                $response = null;
                if ($password) {
                    $response = Http::timeout(120)
                        ->attach($fieldName, file_get_contents($carrierPath), basename($carrierPath))
                        ->attach('document', file_get_contents($documentPath), basename($documentPath))
                        ->post($this->apiBaseUrl.$endpoint, [
                            'user_id' => $userId,
                            'password' => $password,
                        ]);
                } else {
                    $response = Http::timeout(120)
                        ->attach($fieldName, file_get_contents($carrierPath), basename($carrierPath))
                        ->attach('document', file_get_contents($documentPath), basename($documentPath))
                        ->post($this->apiBaseUrl.$endpoint, [
                            'user_id' => $userId,
                        ]);
                }

                $this->cleanupTempFile($carrierPath);
                $this->cleanupTempFile($documentPath);

                if ($response->successful()) {
                    $result = $response->json();
                    $this->saveEmbedResult($result, $fileType, 'document');

                    Notification::make()
                        ->success()
                        ->title('¡Documento ocultado exitosamente!')
                        ->body("Archivo: {$result['original_filename']} - Capacidad usada: {$result['capacity_used']}%")
                        ->send();
                } else {
                    throw new \Exception('Error al comunicarse con la API');
                }
            }

        } catch (\Exception $e) {
            Notification::make()
                ->danger()
                ->title('Error al ocultar contenido')
                ->body($e->getMessage())
                ->send();
        }
    }

    // ========================================
    // ACCIÓN: EXTRAER CONTENIDO
    // ========================================
    public function extractContent(): void
    {
        try {
            $fileData = $this->extractData['file'] ?? null;

            if (empty($fileData)) {
                throw new \Exception('Debe seleccionar un archivo');
            }

            $fileType = $this->detectFileType($fileData);
            $filePath = $this->saveAndGetFilePath($fileData, $fileType);

            if (! file_exists($filePath)) {
                throw new \Exception('Error al procesar el archivo');
            }

            $fieldName = $fileType === 'audio' ? 'audio' : 'image';

            // ========================================
            // MODO MENSAJE
            // ========================================
            if ($this->extractMode === 'message') {
                $endpoint = $fileType === 'audio' ? '/audio/stego/extract' : '/image/stego/extract';

                $response = Http::timeout(60)
                    ->attach($fieldName, file_get_contents($filePath), basename($filePath))
                    ->post($this->apiBaseUrl.$endpoint);

                $this->cleanupTempFile($filePath);

                if ($response->successful()) {
                    $result = $response->json();

                    $this->extractResult = json_encode([
                        'type' => 'message',
                        'message' => $result['message'] ?? '',
                        'length' => $result['message_length'] ?? 0,
                        'file_type' => $fileType,
                    ]);

                    if ($result['message_length'] > 0) {
                        Notification::make()
                            ->success()
                            ->title('¡Mensaje extraído!')
                            ->body("Se encontró un mensaje de {$result['message_length']} caracteres")
                            ->send();
                    } else {
                        Notification::make()
                            ->warning()
                            ->title('Sin mensaje')
                            ->body('No se encontró ningún mensaje oculto')
                            ->send();
                    }
                } else {
                    throw new \Exception('Error al comunicarse con la API');
                }
            }
            // ========================================
            // MODO DOCUMENTO
            // ========================================
            else {
                $endpoint = $fileType === 'audio'
                    ? '/audio/stego/extract-document'
                    : '/image/stego/extract-document';

                $password = $this->extractData['password'] ?? null;

                $response = Http::timeout(120)
                    ->attach($fieldName, file_get_contents($filePath), basename($filePath))
                    ->post($this->apiBaseUrl.$endpoint, [
                        'password' => $password,
                    ]);

                $this->cleanupTempFile($filePath);

                if ($response->successful()) {
                    $result = $response->json();

                    // Guardar documento extraído
                    $documentBase64 = $result['document_base64'];
                    $documentBytes = base64_decode($documentBase64);
                    $originalFilename = $result['original_filename'];

                    // ✅ SOLUCIÓN: Usar el nombre original directamente (ya tiene la extensión correcta)
                    $savedFileName = 'extracted_'.time().'_'.$originalFilename;

                    // ✅ Guardar en public directamente para evitar problemas de symlink
                    $publicPath = public_path('extracted_files');
                    if (! file_exists($publicPath)) {
                        mkdir($publicPath, 0755, true);
                    }

                    file_put_contents($publicPath.'/'.$savedFileName, $documentBytes);
                    $documentUrl = asset('extracted_files/'.$savedFileName);

                    $this->extractResult = json_encode([
                        'type' => 'document',
                        'file_url' => $documentUrl,
                        'file_name' => $savedFileName,
                        'original_filename' => $originalFilename,
                        'document_size' => $result['document_size'],
                        'mime_type' => $result['mime_type'],
                        'user_id' => $result['user_id'],
                        'embedded_at' => $result['embedded_at'],
                    ]);

                    Notification::make()
                        ->success()
                        ->title('¡Documento extraído!')
                        ->body("Archivo: {$originalFilename}")
                        ->send();
                } else {
                    $error = $response->json();
                    throw new \Exception($error['detail'] ?? 'Error al comunicarse con la API');
                }
            }

        } catch (\Exception $e) {
            Notification::make()
                ->danger()
                ->title('Error al extraer contenido')
                ->body($e->getMessage())
                ->send();
        }
    }

    // ========================================
    // MÉTODO AUXILIAR: Guardar resultado de embed
    // ========================================
    private function saveEmbedResult(array $result, string $fileType, string $mode, ?string $message = null): void
    {
        $base64Data = $result['file_base64'];
        $extension = $fileType === 'audio' ? 'wav' : 'png';
        $fileName = 'infected_'.time().'.'.$extension;

        $decodedFile = base64_decode($base64Data);

        // ✅ SOLUCIÓN: Guardar en public directamente
        $publicPath = public_path('infected_files');
        if (! file_exists($publicPath)) {
            mkdir($publicPath, 0755, true);
        }

        file_put_contents($publicPath.'/'.$fileName, $decodedFile);
        $fileUrl = asset('infected_files/'.$fileName);

        $resultData = [
            'mode' => $mode,
            'file' => $fileUrl,
            'file_name' => $fileName,
            'file_type' => $fileType,
            'payload_size' => $result['payload_size'],
            'capacity_used' => $result['capacity_used'],
        ];

        if ($mode === 'message') {
            $resultData['message'] = $message;
        } else {
            $resultData['original_filename'] = $result['original_filename'];
            $resultData['original_size'] = $result['original_size'];
            $resultData['compressed_size'] = $result['compressed_size'];
            $resultData['is_password_protected'] = $result['is_password_protected'];
            $resultData['user_id'] = $result['user_id'];
        }

        $this->embedResult = json_encode($resultData);
    }
}
