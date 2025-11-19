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

    protected string $view = 'dashboard';

    protected static ?string $navigationLabel = 'Infección y Extracción';

    protected static ?string $title = '';

    public function getMaxContentWidth(): Width
    {
        return Width::MaxContent;
    }

    // API Base URL
    private string $apiBaseUrl = 'http://localhost:7000';

    // Estados de los formularios
    public ?array $embedData = [];

    public ?array $extractData = [];

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
    // NUEVO: Detectar tipo de archivo por extensión
    // ========================================
    private function detectFileType($uploadedFile): string
    {
        // Si es un array, obtener el primer elemento
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

        // Detectar por extensión
        if (in_array($extension, ['wav'])) {
            return 'audio';
        } elseif (in_array($extension, ['png', 'jpg', 'jpeg', 'bmp'])) {
            return 'image';
        }

        return 'image'; // Por defecto
    }

    // ========================================
    // MÉTODO MEJORADO: Guardar archivo directamente en storage y obtener ruta
    // ========================================
    private function saveAndGetFilePath($uploadedFile, string $fileType): string
    {
        // Generar nombre único
        $extension = $fileType === 'audio' ? 'wav' : 'png';
        $fileName = 'temp_'.uniqid().'_'.time().'.'.$extension;

        // Disk local
        $disk = Storage::disk('local');

        // Crear directorio temp si no existe
        if (! $disk->exists('temp')) {
            $disk->makeDirectory('temp');
        }

        // ✅ NUEVO: Si es un array asociativo con UUID (Livewire/Filament)
        if (is_array($uploadedFile) && ! empty($uploadedFile)) {
            // Obtener el primer valor del array (el objeto TemporaryUploadedFile)
            $firstFile = reset($uploadedFile);

            // Si es un objeto TemporaryUploadedFile de Livewire
            if (is_object($firstFile) && method_exists($firstFile, 'getRealPath')) {
                $realPath = $firstFile->getRealPath();
                if (file_exists($realPath)) {
                    $content = file_get_contents($realPath);
                    $disk->put('temp/'.$fileName, $content);

                    return $disk->path('temp/'.$fileName);
                }

                // Intentar con path() si getRealPath() falla
                if (method_exists($firstFile, 'path')) {
                    $path = $firstFile->path();
                    if (file_exists($path)) {
                        $content = file_get_contents($path);
                        $disk->put('temp/'.$fileName, $content);

                        return $disk->path('temp/'.$fileName);
                    }
                }
            }

            // Si no es objeto, intentar recursivamente con el primer elemento
            if (! is_object($firstFile)) {
                return $this->saveAndGetFilePath($firstFile, $fileType);
            }
        }

        // Si es un string (nombre de archivo temporal de Livewire)
        if (is_string($uploadedFile)) {
            // Buscar en todas las ubicaciones posibles
            $possiblePaths = [
                storage_path('app/livewire-tmp/'.$uploadedFile),
                storage_path('app/private/livewire-tmp/'.$uploadedFile),
                storage_path('app/private/'.$uploadedFile),
                storage_path('app/uploads/'.$uploadedFile),
                storage_path('app/'.$uploadedFile),
            ];

            // También buscar sin el nombre, solo en los directorios
            $baseName = basename($uploadedFile);
            $possiblePaths[] = storage_path('app/livewire-tmp/'.$baseName);
            $possiblePaths[] = storage_path('app/private/livewire-tmp/'.$baseName);
            $possiblePaths[] = storage_path('app/private/'.$baseName);

            foreach ($possiblePaths as $path) {
                if (file_exists($path)) {
                    // Copiar a nuestra ubicación temp
                    $content = file_get_contents($path);
                    $disk->put('temp/'.$fileName, $content);

                    return $disk->path('temp/'.$fileName);
                }
            }

            // Búsqueda recursiva en livewire-tmp
            $livewireTmpPath = storage_path('app/livewire-tmp');
            if (is_dir($livewireTmpPath)) {
                $iterator = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($livewireTmpPath, \RecursiveDirectoryIterator::SKIP_DOTS)
                );

                foreach ($iterator as $file) {
                    if ($file->isFile()) {
                        $fileNameFound = $file->getFilename();
                        // Buscar por nombre completo o por hash
                        if ($fileNameFound === $uploadedFile || $fileNameFound === basename($uploadedFile) ||
                            strpos($fileNameFound, basename($uploadedFile)) !== false) {
                            $content = file_get_contents($file->getRealPath());
                            $tempFileName = 'temp_'.uniqid().'_'.time().'.'.$extension;
                            $disk->put('temp/'.$tempFileName, $content);

                            return $disk->path('temp/'.$tempFileName);
                        }
                    }
                }
            }

            // Búsqueda recursiva en private/livewire-tmp
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
                            $tempFileName = 'temp_'.uniqid().'_'.time().'.'.$extension;
                            $disk->put('temp/'.$tempFileName, $content);

                            return $disk->path('temp/'.$tempFileName);
                        }
                    }
                }
            }
        }

        // Si es un objeto UploadedFile directo
        if (is_object($uploadedFile) && method_exists($uploadedFile, 'getRealPath')) {
            $realPath = $uploadedFile->getRealPath();
            if (file_exists($realPath)) {
                $content = file_get_contents($realPath);
                $disk->put('temp/'.$fileName, $content);

                return $disk->path('temp/'.$fileName);
            }
        }

        throw new \Exception('No se pudo procesar el archivo. Debug: '.(is_string($uploadedFile) ? $uploadedFile : gettype($uploadedFile)));
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
    // FORMULARIO DE INFECTAR - SIN SELECT ✅
    // ========================================
    public function embedForm(Schema $form): Schema
    {
        return $form
            ->schema([
                Section::make('Infectar Archivo con Mensaje Oculto')
                    ->description('Oculta un mensaje secreto dentro de una imagen o audio')
                    ->schema([
                        Forms\Components\FileUpload::make('file')
                            ->label('Subir Archivo (Imagen o Audio)')
                            ->acceptedFileTypes([
                                'image/png', 'image/jpeg', 'image/jpg',
                                'audio/wav', 'audio/x-wav', 'audio/wave',
                                '.png', '.jpg', '.jpeg', '.wav',
                            ])
                            ->maxSize(10240) // 10MB
                            ->required()
                            ->storeFiles(false)
                            ->helperText('⚠️ Formatos: PNG, JPG, JPEG, WAV. Máximo 10MB')
                            ->live(),

                        Forms\Components\Textarea::make('message')
                            ->label('Mensaje Secreto')
                            ->placeholder('Escribe el mensaje que deseas ocultar...')
                            ->required()
                            ->rows(4)
                            ->maxLength(10000)
                            ->columnSpanFull(),

                        Actions::make([
                            Action::make('embed')
                                ->label('🔒 Infectar Archivo')
                                ->color('danger')
                                ->size('lg')
                                ->requiresConfirmation()
                                ->modalHeading('¿Infectar archivo?')
                                ->modalDescription('Se ocultará el mensaje dentro del archivo seleccionado.')
                                ->action('embedMessage'),
                        ])->columnSpanFull(),
                    ])
                    ->columns(1),
            ])
            ->statePath('embedData');
    }

    // ========================================
    // FORMULARIO DE EXTRAER - SIN SELECT ✅
    // ========================================
    public function extractForm(Schema $form): Schema
    {
        return $form
            ->schema([
                Section::make('Extraer Mensaje Oculto')
                    ->description('Extrae el mensaje secreto de un archivo infectado')
                    ->schema([
                        Forms\Components\FileUpload::make('file')
                            ->label('Subir Archivo Infectado (Imagen o Audio)')
                            ->acceptedFileTypes([
                                'image/png', 'image/jpeg', 'image/jpg',
                                'audio/wav', 'audio/x-wav', 'audio/wave',
                                '.png', '.jpg', '.jpeg', '.wav',
                            ])
                            ->maxSize(10240) // 10MB
                            ->required()
                            ->storeFiles(false)
                            ->helperText('⚠️ Formatos: PNG, JPG, JPEG, WAV. Máximo 10MB')
                            ->live(),

                        Actions::make([
                            Action::make('extract')
                                ->label('🔓 Extraer Mensaje')
                                ->color('primary')
                                ->size('lg')
                                ->action('extractMessage'),
                        ])->columnSpanFull(),
                    ])
                    ->columns(1),
            ])
            ->statePath('extractData');
    }

    // ========================================
    // ACCIÓN: INFECTAR MENSAJE - AUTO-DETECT ✅
    // ========================================
    public function embedMessage(): void
    {
        try {
            $fileData = $this->embedData['file'] ?? null;

            if (empty($fileData)) {
                throw new \Exception('Debe seleccionar un archivo');
            }

            $message = $this->embedData['message'] ?? '';

            if (empty($message)) {
                throw new \Exception('Debe ingresar un mensaje');
            }

            // ✅ Detectar tipo automáticamente
            $fileType = $this->detectFileType($fileData);

            $filePath = $this->saveAndGetFilePath($fileData, $fileType);

            if (! file_exists($filePath)) {
                throw new \Exception('Error al procesar el archivo');
            }

            $endpoint = $fileType === 'audio'
                ? '/audio/stego/embed'
                : '/image/stego/embed';

            $fieldName = $fileType === 'audio' ? 'audio' : 'image';

            $url = $this->apiBaseUrl.$endpoint.'?message='.urlencode($message);

            $response = Http::timeout(60)
                ->attach(
                    $fieldName,
                    file_get_contents($filePath),
                    basename($filePath)
                )
                ->post($url, [
                    'message' => $message,
                ]);

            $this->cleanupTempFile($filePath);

            if ($response->successful()) {
                $result = $response->json();

                $base64Data = $result['file_base64'];
                $extension = $fileType === 'audio' ? 'wav' : 'png';
                $fileName = 'infected_'.time().'.'.$extension;

                $decodedFile = base64_decode($base64Data);

                if ($decodedFile === false) {
                    throw new \Exception('Error al decodificar el archivo base64');
                }

                Storage::disk('public')->put($fileName, $decodedFile);

                $fileUrl = Storage::disk('public')->url($fileName);

                $this->embedResult = json_encode([
                    'file' => $fileUrl,
                    'file_name' => $fileName,
                    'payload_size' => $result['payload_size'],
                    'capacity_used' => $result['capacity_used'],
                    'message' => $message,
                    'file_type' => $fileType,
                ]);

                $fileTypeLabel = $fileType === 'audio' ? '🎵 Audio' : '🖼️ Imagen';
                Notification::make()
                    ->success()
                    ->title("¡{$fileTypeLabel} infectado exitosamente!")
                    ->body("Capacidad usada: {$result['capacity_used']}%")
                    ->send();

                // ✅ Limpiar formulario
                $this->embedData = ['file' => null, 'message' => null];
                $this->embedForm->fill($this->embedData);
            } else {
                $error = $response->json();
                throw new \Exception($error['detail'][0]['msg'] ?? 'Error al comunicarse con la API');
            }
        } catch (\Exception $e) {
            Notification::make()
                ->danger()
                ->title('Error al infectar')
                ->body($e->getMessage())
                ->send();
        }
    }

    // ========================================
    // ACCIÓN: EXTRAER MENSAJE - AUTO-DETECT ✅
    // ========================================
    public function extractMessage(): void
    {
        try {
            $fileData = $this->extractData['file'] ?? null;

            if (empty($fileData)) {
                throw new \Exception('Debe seleccionar un archivo');
            }

            // ✅ Detectar tipo automáticamente
            $fileType = $this->detectFileType($fileData);

            $filePath = $this->saveAndGetFilePath($fileData, $fileType);

            if (! file_exists($filePath)) {
                throw new \Exception('Error al procesar el archivo');
            }

            $endpoint = $fileType === 'audio'
                ? '/audio/stego/extract'
                : '/image/stego/extract';

            $fieldName = $fileType === 'audio' ? 'audio' : 'image';

            $response = Http::timeout(60)
                ->attach(
                    $fieldName,
                    file_get_contents($filePath),
                    basename($filePath)
                )
                ->post($this->apiBaseUrl.$endpoint);

            $this->cleanupTempFile($filePath);

            if ($response->successful()) {
                $result = $response->json();

                $this->extractResult = json_encode([
                    'message' => $result['message'] ?? 'No se encontró mensaje',
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

                // ✅ Limpiar formulario
                $this->extractData = ['file' => null];
                $this->extractForm->fill($this->extractData);
            } else {
                $error = $response->json();
                throw new \Exception($error['detail'] ?? 'Error al comunicarse con la API');
            }
        } catch (\Exception $e) {
            Notification::make()
                ->danger()
                ->title('Error al extraer')
                ->body($e->getMessage())
                ->send();
        }
    }
}
