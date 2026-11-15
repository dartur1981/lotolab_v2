<?php

namespace App\Filament\App\Pages\Megasena;

use App\Filament\Concerns\BelongsToMegasena;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Forms\Components\FileUpload;
use Filament\Schemas\Components\Section;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema as DbSchema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Str;

class ImportarResultados extends Page implements HasForms
{
    use InteractsWithForms, BelongsToMegasena;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';
    protected string $view = 'filament.app.pages.megasena.importar-resultados';
    
    protected static ?string $navigationLabel = 'Importar Resultados';
    protected ?string $heading = 'Importar Resultados da Mega-Sena';
    protected static string|\UnitEnum|null $navigationGroup = 'Mega-Sena';
    protected static ?int $navigationSort = 10;

    public ?array $data = [];
    public string $processLogs = '';
    public ?string $currentLogFile = null;
    public bool $isProcessing = false;

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Section::make('Envio de Arquivo da Mega-Sena')
                    ->description('Faça o upload do histórico oficial de resultados (Excel ou CSV) da Caixa Econômica Federal.')
                    ->schema([
                        FileUpload::make('arquivo_historico')
                            ->label('Arquivo de Histórico (Mega-Sena)')
                            ->required()
                            ->disk('data_dir')
                            ->directory('historicos')
                            ->getUploadedFileNameForStorageUsing(fn ($file) => Str::ascii($file->getClientOriginalName()))
                            ->acceptedFileTypes([
                                'text/csv', 
                                'text/plain', 
                                'application/vnd.ms-excel', 
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
                            ])
                    ])
            ])
            ->statePath('data');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label('Enviar e Processar')
                ->color('success')
                ->action('save'),
        ];
    }

    private function garantirColunasAnaliticas(): void
    {
        try {
            $schema = DbSchema::connection('analytics_megasena');
            if ($schema->hasTable('features_megasena')) {
                $schema->table('features_megasena', function (Blueprint $table) use ($schema) {
                    if (!$schema->hasColumn('features_megasena', 'temp_quente')) {
                        $table->integer('temp_quente')->default(0)->after('soma');
                    }
                    if (!$schema->hasColumn('features_megasena', 'temp_morno')) {
                        $table->integer('temp_morno')->default(0)->after('temp_quente');
                    }
                    if (!$schema->hasColumn('features_megasena', 'temp_frio')) {
                        $table->integer('temp_frio')->default(0)->after('temp_morno');
                    }
                });
            }

            if ($schema->hasTable('tendencias_dezenas_megasena')) {
                $schema->table('tendencias_dezenas_megasena', function (Blueprint $table) use ($schema) {
                    if (!$schema->hasColumn('tendencias_dezenas_megasena', 'score_temperatura')) {
                        $table->float('score_temperatura')->default(0.0)->after('atraso');
                    }
                    if (!$schema->hasColumn('tendencias_dezenas_megasena', 'r_10')) {
                        $table->float('r_10')->default(0.0)->after('score_temperatura');
                    }
                    if (!$schema->hasColumn('tendencias_dezenas_megasena', 'r_20')) {
                        $table->float('r_20')->default(0.0)->after('r_10');
                    }
                    if (!$schema->hasColumn('tendencias_dezenas_megasena', 'r_50')) {
                        $table->float('r_50')->default(0.0)->after('r_20');
                    }
                    if (!$schema->hasColumn('tendencias_dezenas_megasena', 'r_100')) {
                        $table->float('r_100')->default(0.0)->after('r_50');
                    }
                });
            }
        } catch (\Throwable $e) {
            // Em caso de falha de conexão inicial no analytics, o backend Python possui o fallback
        }
    }

    public function save(): void
    {
        $this->garantirColunasAnaliticas();

        $data = $this->form->getState();
        $fileName = $data['arquivo_historico'] ?? null;
        
        if ($fileName) {
            $disk = Storage::disk('data_dir');
            $fullPath = $disk->path($fileName);
            $realPath = realpath($fullPath) ?: str_replace('/var/www/html/../data', '/var/www/data', $fullPath);
            $pathToSend = str_replace('\\', '/', $realPath);
            
            try {
                $pythonUrl = rtrim(config('services.python_api.url', 'http://127.0.0.1:5000'), '/');
                $response = Http::timeout(300)->post("{$pythonUrl}/megasena/importar-historico", [
                    'path' => $pathToSend
                ]);
                
                if ($response->successful()) {
                    $this->currentLogFile = $response->json('log_file');
                    $this->isProcessing = true;
                    $this->updateLogs();
                    
                    Notification::make()
                        ->title('Processo Iniciado')
                        ->body($response->json('message') ?? 'Importação da Mega-Sena disparada com sucesso!')
                        ->success()
                        ->send();
                } else {
                    $this->processLogs = $response->json('detail') ?? 'Falha ao processar arquivo no backend Python.';
                    Notification::make()
                        ->title('Erro no Python')
                        ->body('Houve um erro durante o processamento.')
                        ->danger()
                        ->send();
                }
            } catch (\Exception $e) {
                $this->processLogs = 'Erro de conexão: ' . $e->getMessage();
                Notification::make()
                    ->title('Erro de Conexão')
                    ->body('Não foi possível conectar à API Python.')
                    ->danger()
                    ->send();
            }
        }
            
        $this->form->fill();
    }
    
    public function updateLogs(): void
    {
        if ($this->currentLogFile && $this->isProcessing) {
            $path = base_path('../logs/' . $this->currentLogFile);
            if (File::exists($path)) {
                $this->processLogs = File::get($path);
                
                if (str_contains($this->processLogs, 'Processo em background finalizado com SUCESSO')) {
                    $this->isProcessing = false;
                    Notification::make()
                        ->title('Processo Concluído')
                        ->body('Todas as operações matemáticas da Mega-Sena foram finalizadas.')
                        ->success()
                        ->send();
                } elseif (str_contains($this->processLogs, 'ERRO no processo de background')) {
                    $this->isProcessing = false;
                    Notification::make()
                        ->title('Erro no Background')
                        ->body('O processo falhou no backend Python.')
                        ->danger()
                        ->send();
                }
            }
        }
    }
}
