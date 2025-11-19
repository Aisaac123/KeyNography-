<?php

namespace App\Filament\Pages;

use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
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
    // Guardar archivo directamente en storage y obtener ruta
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

    public function analyzeForm(Schema $form): Schema
    {
        return $form
            ->schema([
                Section::make('Análisis de Esteganografía')
                    ->description('Detecta si un archivo contiene mensajes ocultos')
                    ->schema([
                        Select::make('file_type')
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

                        FileUpload::make('file')
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
