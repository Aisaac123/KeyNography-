<?php

namespace App\Filament\Pages;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
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
    protected static ?string $navigationLabel = 'Esteganografía';
    protected static ?string $title = 'Sistema de Esteganografía';

    // API Base URL - CAMBIA ESTO A TU URL
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
                            ->default('image'),

                        Forms\Components\FileUpload::make('file')
                            ->label(fn (Get $get) => $get('file_type') === 'audio' ? 'Subir Audio' : 'Subir Imagen')
                            ->acceptedFileTypes(fn (Get $get) => $get('file_type') === 'audio'
                                ? ['audio/wav', 'audio/x-wav']
                                : ['image/png', 'image/jpeg', 'image/jpg'])
                            ->maxSize(10240)
                            ->required()
                            ->disk('local')
                            ->directory('temp')
                            ->visibility('private')
                            ->helperText(fn (Get $get) => $get('file_type') === 'audio'
                                ? 'Solo archivos WAV. Máximo 10MB'
                                : 'Formatos: PNG, JPG, JPEG. Máximo 10MB'),

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
                            ->default('image'),

                        Forms\Components\FileUpload::make('file')
                            ->label(fn (Get $get) => $get('file_type') === 'audio' ? 'Subir Audio Infectado' : 'Subir Imagen Infectada')
                            ->acceptedFileTypes(fn (Get $get) => $get('file_type') === 'audio'
                                ? ['audio/wav', 'audio/x-wav']
                                : ['image/png', 'image/jpeg', 'image/jpg'])
                            ->maxSize(10240)
                            ->required()
                            ->disk('local')
                            ->directory('temp')
                            ->visibility('private'),

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
                            ->default('image'),

                        Forms\Components\FileUpload::make('file')
                            ->label(fn (Get $get) => $get('file_type') === 'audio' ? 'Subir Audio para Analizar' : 'Subir Imagen para Analizar')
                            ->acceptedFileTypes(fn (Get $get) => $get('file_type') === 'audio'
                                ? ['audio/wav', 'audio/x-wav']
                                : ['image/png', 'image/jpeg', 'image/jpg'])
                            ->maxSize(10240)
                            ->required()
                            ->disk('local')
                            ->directory('temp')
                            ->visibility('private'),

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
            // Validar que existe el archivo
            if (!isset($data['file']) || empty($data['file'])) {
                throw new \Exception('Debe seleccionar un archivo');
            }

            // Obtener ruta del archivo temporal
            $filePath = Storage::disk('local')->path($data['file']);

            if (!file_exists($filePath)) {
                throw new \Exception('El archivo no existe');
            }

            $endpoint = $data['file_type'] === 'audio'
                ? '/audio/stego/embed'
                : '/image/stego/embed';

            // Realizar petición HTTP
            $response = Http::timeout(60)
                ->attach(
                    'audio', // nombre del campo en FastAPI
                    file_get_contents($filePath),
                    basename($filePath)
                )
                ->post($this->apiBaseUrl . $endpoint, [
                    'message' => $data['message']
                ]);

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
                    'message' => $result['message']
                ]);

                // Limpiar archivo temporal
                Storage::disk('local')->delete($data['file']);

                Notification::make()
                    ->success()
                    ->title('¡Infectado exitosamente!')
                    ->body("Mensaje oculto en {$result['file_type']}. Capacidad usada: {$result['capacity_used']}%")
                    ->send();

                $this->embedForm->fill();
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

            $filePath = Storage::disk('local')->path($data['file']);

            if (!file_exists($filePath)) {
                throw new \Exception('El archivo no existe');
            }

            $endpoint = $data['file_type'] === 'audio'
                ? '/audio/stego/extract'
                : '/image/stego/extract';

            $response = Http::timeout(60)
                ->attach(
                    'audio',
                    file_get_contents($filePath),
                    basename($filePath)
                )
                ->post($this->apiBaseUrl . $endpoint);

            if ($response->successful()) {
                $result = $response->json();

                $this->extractResult = json_encode([
                    'message' => $result['message'] ?? 'No se encontró mensaje',
                    'length' => $result['message_length'] ?? 0
                ]);

                // Limpiar archivo temporal
                Storage::disk('local')->delete($data['file']);

                Notification::make()
                    ->success()
                    ->title('¡Mensaje extraído!')
                    ->body("Se encontró un mensaje de {$result['message_length']} caracteres")
                    ->send();

                $this->extractForm->fill();
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

            $filePath = Storage::disk('local')->path($data['file']);

            if (!file_exists($filePath)) {
                throw new \Exception('El archivo no existe');
            }

            $endpoint = $data['file_type'] === 'audio'
                ? '/audio/steganalysis/analyze'
                : '/image/steganalysis/analyze';

            $response = Http::timeout(60)
                ->attach(
                    'audio',
                    file_get_contents($filePath),
                    basename($filePath)
                )
                ->post($this->apiBaseUrl . $endpoint);

            if ($response->successful()) {
                $this->analyzeResult = $response->json();

                // Limpiar archivo temporal
                Storage::disk('local')->delete($data['file']);

                $color = $this->analyzeResult['is_infected'] ? 'danger' : 'success';

                Notification::make()
                    ->color($color)
                    ->title($this->analyzeResult['verdict'])
                    ->body("Confianza: {$this->analyzeResult['confidence']}% | LSB: {$this->analyzeResult['lsb_probability']}%")
                    ->send();

                $this->analyzeForm->fill();
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
