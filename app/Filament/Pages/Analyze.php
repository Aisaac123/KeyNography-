<?php

namespace App\Filament\Pages;

use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Livewire\WithFileUploads;

class Analyze extends Page
{
    use InteractsWithForms;
    use WithFileUploads;

    protected static string|null|\BackedEnum $navigationIcon = 'heroicon-o-chart-bar';

    protected static string|null|\BackedEnum $activeNavigationIcon = 'heroicon-s-chart-bar';

    protected string $view = 'filament.pages.analyze';

    protected static ?string $navigationLabel = 'Análisis de Esteganografía';

    protected static ?string $title = '';

    public function getMaxContentWidth(): Width
    {
        return Width::MaxContent;
    }

    // API Base URL
    private string $apiBaseUrl = 'http://localhost:7000';

    // Estados de los formularios
    public ?array $analyzeData = [];

    // Resultados
    public ?array $analyzeResult = null;

    protected function getForms(): array
    {
        return [
            'analyzeForm',
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
    private function saveAndGetFilePath($uploadedFile): string
    {
        // ✅ Detectar tipo automáticamente
        $fileType = $this->detectFileType($uploadedFile);

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
                return $this->saveAndGetFilePath($firstFile);
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
    // FORMULARIO DE ANÁLISIS
    // ========================================
    public function analyzeForm(Schema $form): Schema
    {
        return $form
            ->schema([
                Section::make('Análisis de Esteganografía')
                    ->description('Detecta si un archivo contiene mensajes ocultos')
                    ->schema([
                        FileUpload::make('file')
                            ->label('Subir Archivo para Analizar (Imagen o Audio)')
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
                            Action::make('analyze')
                                ->label('🔍 Analizar Archivo')
                                ->color('warning')
                                ->size('lg')
                                ->action('analyzeFile'),
                        ])->columnSpanFull(),
                    ])
                    ->columns(1),
            ])
            ->statePath('analyzeData');
    }

    // ========================================
    // ACCIÓN: ANALIZAR ARCHIVO - AUTO-DETECT
    // ========================================
    public function analyzeFile(): void
    {
        try {
            $fileData = $this->analyzeData['file'] ?? null;

            if (empty($fileData)) {
                throw new \Exception('Debe seleccionar un archivo');
            }

            // ✅ Detectar tipo automáticamente
            $fileType = $this->detectFileType($fileData);

            // ✅ Guardar archivo
            $filePath = $this->saveAndGetFilePath($fileData);

            if (! file_exists($filePath)) {
                throw new \Exception('Error al procesar el archivo');
            }

            $endpoint = $fileType === 'audio'
                ? '/audio/steganalysis/analyze'
                : '/image/steganalysis/analyze';

            $fieldName = $fileType === 'audio' ? 'audio' : 'image';

            $response = Http::timeout(60)
                ->attach(
                    $fieldName,
                    file_get_contents($filePath),
                    basename($filePath)
                )
                ->post($this->apiBaseUrl.$endpoint);

            // Limpiar archivo temporal
            $this->cleanupTempFile($filePath);

            if ($response->successful()) {
                $this->analyzeResult = $response->json();

                // ========================================
                // ✅ NUEVA LÓGICA: Detectar tipo de contenido y preparar notificación
                // ========================================
                $color = $this->analyzeResult['is_infected'] ? 'danger' : 'success';
                $title = $this->analyzeResult['verdict'] ?? 'Análisis completado';

                // Determinar el cuerpo de la notificación según el tipo de contenido
                $body = "Confianza: {$this->analyzeResult['confidence']}%";

                if (isset($this->analyzeResult['summary']['document_found']) && $this->analyzeResult['summary']['document_found']) {
                    if (isset($this->analyzeResult['summary']['document_filename'])) {
                        $body = "📄 Documento: {$this->analyzeResult['summary']['document_filename']} | Confianza: {$this->analyzeResult['confidence']}%";
                    } else {
                        $body = "🔒 Documento protegido detectado | Confianza: {$this->analyzeResult['confidence']}%";
                    }
                } elseif (isset($this->analyzeResult['summary']['message_found']) && $this->analyzeResult['summary']['message_found']) {
                    $messageLength = $this->analyzeResult['summary']['message_length'] ?? 0;
                    $body = "💬 Mensaje de texto ({$messageLength} caracteres) | Confianza: {$this->analyzeResult['confidence']}%";
                }

                Notification::make()
                    ->color($color)
                    ->title($title)
                    ->body($body)
                    ->duration(5000)
                    ->send();

            } else {
                $error = $response->json();
                \Log::error('API Error:', $error);
                throw new \Exception($error['detail'] ?? 'Error al comunicarse con la API');
            }
        } catch (\Exception $e) {
            \Log::error('Exception in analyzeFile:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            Notification::make()
                ->danger()
                ->title('Error al analizar')
                ->body($e->getMessage())
                ->send();
        }
    }
}
