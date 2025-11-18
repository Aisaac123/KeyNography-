<?php

namespace App\Filament\Pages;

use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Livewire\WithFileUploads;
use Filament\Schemas\Schema;

class Dashboard extends \Filament\Pages\Dashboard implements Forms\Contracts\HasForms
{
    use InteractsWithForms;
    use WithFileUploads;

    protected static string|null|\BackedEnum $navigationIcon = 'heroicon-o-lock-closed';
    protected string $view = 'dashboard';
    protected static ?string $navigationLabel = 'Infección y Extracción';
    protected static ?string $title = 'Sistema de Esteganografía: Infección y Extracción';

    public function getMaxContentWidth(): Width
    {
        return Width::MaxContent;
    }

    // API Base URL
    private string $apiBaseUrl = 'http://localhost:7000';

    // Estados de los formularios
    public ?array $embedData = [];
    public ?array $extractData = [];
    public ?array $analyzeData = [];

    // Resultados
    public ?string $embedResult = null;
    public ?string $extractResult = null;
    public ?array $analyzeResult = null;

    protected function getForms(): array
    {
        return [
            'embedForm',
            'extractForm',
            'analyzeForm',
        ];
    }

    // ========================================
    // MÉTODO MEJORADO: Guardar archivo directamente en storage y obtener ruta
    // ========================================
    private function saveAndGetFilePath($uploadedFile, string $fileType): string
    {
        // Generar nombre único
        $extension = $fileType === 'audio' ? 'wav' : 'png';
        $fileName = 'temp_' . uniqid() . '_' . time() . '.' . $extension;

        // Disk local
        $disk = Storage::disk('local');

        // Crear directorio temp si no existe
        if (!$disk->exists('temp')) {
            $disk->makeDirectory('temp');
        }

        // Si es un string (nombre de archivo temporal de Livewire)
        if (is_string($uploadedFile)) {
            // Buscar en todas las ubicaciones posibles
            $possiblePaths = [
                storage_path('app/livewire-tmp/' . $uploadedFile),
                storage_path('app/private/' . $uploadedFile),
                storage_path('app/uploads/' . $uploadedFile),
                storage_path('app/' . $uploadedFile),
            ];

            // También buscar sin el nombre, solo en los directorios
            $baseName = basename($uploadedFile);
            $possiblePaths[] = storage_path('app/livewire-tmp/' . $baseName);
            $possiblePaths[] = storage_path('app/private/' . $baseName);

            foreach ($possiblePaths as $path) {
                if (file_exists($path)) {
                    // Copiar a nuestra ubicación temp
                    $content = file_get_contents($path);
                    $disk->put('temp/' . $fileName, $content);
                    return $disk->path('temp/' . $fileName);
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
                        $fileName = $file->getFilename();
                        // Buscar por nombre completo o por hash
                        if ($fileName === $uploadedFile || $fileName === basename($uploadedFile) ||
                            strpos($fileName, basename($uploadedFile)) !== false) {
                            $content = file_get_contents($file->getRealPath());
                            $tempFileName = 'temp_' . uniqid() . '_' . time() . '.' . $extension;
                            $disk->put('temp/' . $tempFileName, $content);
                            return $disk->path('temp/' . $tempFileName);
                        }
                    }
                }
            }
        }

        // Si es un objeto UploadedFile
        if (is_object($uploadedFile) && method_exists($uploadedFile, 'getRealPath')) {
            $realPath = $uploadedFile->getRealPath();
            if (file_exists($realPath)) {
                $content = file_get_contents($realPath);
                $disk->put('temp/' . $fileName, $content);
                return $disk->path('temp/' . $fileName);
            }
        }

        // Si es un array (a veces Filament lo envía así)
        if (is_array($uploadedFile) && !empty($uploadedFile)) {
            return $this->saveAndGetFilePath($uploadedFile[0], $fileType);
        }

        throw new \Exception('No se pudo procesar el archivo. Debug: ' . (is_string($uploadedFile) ? $uploadedFile : gettype($uploadedFile)));
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
                Section::make('Infectar Archivo con Mensaje Oculto')
                    ->description('Oculta un mensaje secreto dentro de una imagen o audio')
                    ->schema([
                        Forms\Components\Select::make('file_type')
                            ->label('Tipo de Archivo')
                            ->options([
                                'image' => '🖼️ Imagen',
                                'audio' => '🎵 Audio',
                            ])
                            ->required()
                            ->live()
                            ->default('image')
                            ->afterStateUpdated(function ($state, Set $set) {
                                // Limpiar el archivo cuando cambia el tipo
                                $set('file', null);
                            }),

                        Forms\Components\FileUpload::make('file')
                            ->label(fn(Get $get) => $get('file_type') === 'audio' ? 'Subir Audio WAV' : 'Subir Imagen')
                            ->acceptedFileTypes(fn(Get $get) => $get('file_type') === 'audio'
                                ? ['audio/wav', 'audio/x-wav', 'audio/wave']
                                : ['image/png', 'image/jpeg', 'image/jpg'])
                            ->maxSize(10240) // 10MB
                            ->required()
                            ->helperText(fn(Get $get) => $get('file_type') === 'audio'
                                ? '⚠️ Solo archivos WAV. Máximo 10MB'
                                : '⚠️ Formatos: PNG, JPG, JPEG. Máximo 10MB')
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
                                ->action('embedMessage')
                        ])->columnSpanFull()
                    ])
                    ->columns(1)
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
                Section::make('Extraer Mensaje Oculto')
                    ->description('Extrae el mensaje secreto de un archivo infectado')
                    ->schema([
                        Forms\Components\Select::make('file_type')
                            ->label('Tipo de Archivo')
                            ->options([
                                'image' => '🖼️ Imagen',
                                'audio' => '🎵 Audio',
                            ])
                            ->required()
                            ->live()
                            ->default('image')
                            ->afterStateUpdated(function ($state, Set $set) {
                                $set('file', null);
                            }),

                        Forms\Components\FileUpload::make('file')
                            ->label(fn(Get $get) => $get('file_type') === 'audio' ? 'Subir Audio WAV Infectado' : 'Subir Imagen Infectada')
                            ->acceptedFileTypes(fn(Get $get) => $get('file_type') === 'audio'
                                ? ['audio/wav', 'audio/x-wav', 'audio/wave']
                                : ['image/png', 'image/jpeg', 'image/jpg'])
                            ->maxSize(10240)
                            ->required()
                            ->disk('local')
                            ->directory('uploads')
                            ->visibility('private')
                            ->live(),

                        Actions::make([
                            Action::make('extract')
                                ->label('🔓 Extraer Mensaje')
                                ->color('success')
                                ->size('lg')
                                ->action('extractMessage')
                        ])->columnSpanFull()
                    ])
                    ->columns(1)
            ])
            ->statePath('extractData');
    }

    // ========================================
    // FORMULARIO DE ANALIZAR
    // ========================================
    public function analyzeForm(Schema $form): Schema
    {
        return $form
            ->schema([
                Section::make('Análisis de Esteganografía')
                    ->description('Detecta si un archivo contiene mensajes ocultos')
                    ->schema([
                        Forms\Components\Select::make('file_type')
                            ->label('Tipo de Archivo')
                            ->options([
                                'image' => '🖼️ Imagen',
                                'audio' => '🎵 Audio',
                            ])
                            ->required()
                            ->live()
                            ->default('image')
                            ->afterStateUpdated(function ($state, Set $set) {
                                $set('file', null);
                            }),

                        Forms\Components\FileUpload::make('file')
                            ->label(fn(Get $get) => $get('file_type') === 'audio' ? 'Subir Audio WAV para Analizar' : 'Subir Imagen para Analizar')
                            ->acceptedFileTypes(fn(Get $get) => $get('file_type') === 'audio'
                                ? ['audio/wav', 'audio/x-wav', 'audio/wave']
                                : ['image/png', 'image/jpeg', 'image/jpg'])
                            ->maxSize(10240)
                            ->required()
                            ->disk('local')
                            ->directory('uploads')
                            ->visibility('private')
                            ->live(),

                        Actions::make([
                            Action::make('analyze')
                                ->label('🔍 Analizar Archivo')
                                ->color('warning')
                                ->size('lg')
                                ->action('analyzeFile')
                        ])->columnSpanFull()
                    ])
                    ->columns(1)
            ])
            ->statePath('analyzeData');
    }

    // ========================================
    // ACCIÓN: INFECTAR MENSAJE
    // ========================================
    public function embedMessage(): void
    {
        $data = $this->embedForm->getState();

        try {
            if (!isset($data['file']) || empty($data['file'])) {
                throw new \Exception('Debe seleccionar un archivo');
            }

            // Guardar archivo y obtener ruta real
            $filePath = $this->saveAndGetFilePath($data['file'], $data['file_type']);

            if (!file_exists($filePath)) {
                throw new \Exception('Error al procesar el archivo');
            }

            $endpoint = $data['file_type'] === 'audio'
                ? '/audio/stego/embed'
                : '/image/stego/embed';

            $fieldName = $data['file_type'] === 'audio' ? 'audio' : 'image';

            // Realizar petición a la API
            $response = Http::timeout(60)
                ->attach(
                    $fieldName,
                    file_get_contents($filePath),
                    basename($filePath)
                )
                ->post($this->apiBaseUrl . $endpoint, [
                    'message' => $data['message']
                ]);

            // Limpiar archivo temporal
            $this->cleanupTempFile($filePath);

            if ($response->successful()) {
                $result = $response->json();

                // Guardar archivo infectado
                $base64Data = $result['file_base64'];
                $extension = $data['file_type'] === 'audio' ? 'wav' : 'png';
                $fileName = 'infected_' . time() . '.' . $extension;

                Storage::disk('public')->put($fileName, base64_decode($base64Data));

                $this->embedResult = json_encode([
                    'file' => Storage::disk('public')->url($fileName),
                    'payload_size' => $result['payload_size'],
                    'capacity_used' => $result['capacity_used'],
                    'message' => $data['message'],
                    'file_type' => $data['file_type']
                ]);

                Notification::make()
                    ->success()
                    ->title('¡Infectado exitosamente!')
                    ->body("Capacidad usada: {$result['capacity_used']}%")
                    ->send();

                // Limpiar formulario
                $this->embedForm->fill(['file_type' => 'image', 'file' => null, 'message' => null]);
            } else {
                $error = $response->json();
                throw new \Exception($error['detail'] ?? 'Error al comunicarse con la API');
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
    // ACCIÓN: EXTRAER MENSAJE
    // ========================================
    public function extractMessage(): void
    {
        $data = $this->extractForm->getState();

        try {
            if (empty($data['file'])) {
                throw new \Exception('Debe seleccionar un archivo');
            }

            // Guardar archivo y obtener ruta real
            $filePath = $this->saveAndGetFilePath($data['file'], $data['file_type']);

            if (!file_exists($filePath)) {
                throw new \Exception('Error al procesar el archivo');
            }

            $endpoint = $data['file_type'] === 'audio'
                ? '/audio/stego/extract'
                : '/image/stego/extract';

            $fieldName = $data['file_type'] === 'audio' ? 'audio' : 'image';

            $response = Http::timeout(60)
                ->attach(
                    $fieldName,
                    file_get_contents($filePath),
                    basename($filePath)
                )
                ->post($this->apiBaseUrl . $endpoint);

            // Limpiar archivo temporal
            $this->cleanupTempFile($filePath);

            if ($response->successful()) {
                $result = $response->json();

                $this->extractResult = json_encode([
                    'message' => $result['message'] ?? 'No se encontró mensaje',
                    'length' => $result['message_length'] ?? 0
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

                // Limpiar formulario
                $this->extractForm->fill(['file_type' => 'image', 'file' => null]);
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

    // ========================================
    // ACCIÓN: ANALIZAR ARCHIVO
    // ========================================
    public function analyzeFile(): void
    {
        $data = $this->analyzeForm->getState();

        try {
            if (empty($data['file'])) {
                throw new \Exception('Debe seleccionar un archivo');
            }

            // Guardar archivo y obtener ruta real
            $filePath = $this->saveAndGetFilePath($data['file'], $data['file_type']);

            if (!file_exists($filePath)) {
                throw new \Exception('Error al procesar el archivo');
            }

            $endpoint = $data['file_type'] === 'audio'
                ? '/audio/steganalysis/analyze'
                : '/image/steganalysis/analyze';

            $fieldName = $data['file_type'] === 'audio' ? 'audio' : 'image';

            $response = Http::timeout(60)
                ->attach(
                    $fieldName,
                    file_get_contents($filePath),
                    basename($filePath)
                )
                ->post($this->apiBaseUrl . $endpoint);

            // Limpiar archivo temporal
            $this->cleanupTempFile($filePath);

            if ($response->successful()) {
                $this->analyzeResult = $response->json();

                $color = $this->analyzeResult['is_infected'] ? 'danger' : 'success';

                Notification::make()
                    ->color($color)
                    ->title($this->analyzeResult['verdict'])
                    ->body("Confianza: {$this->analyzeResult['confidence']}%")
                    ->send();

                // Limpiar formulario
                $this->analyzeForm->fill(['file_type' => 'image', 'file' => null]);
            } else {
                $error = $response->json();
                throw new \Exception($error['detail'] ?? 'Error al comunicarse con la API');
            }
        } catch (\Exception $e) {
            Notification::make()
                ->danger()
                ->title('Error al analizar')
                ->body($e->getMessage())
                ->send();
        }
    }
}
