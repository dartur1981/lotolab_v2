<?php

namespace App\Filament\App\Pages\Lotofacil;

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

class ImportarResultados extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';
    protected string $view = 'filament.app.pages.lotofacil.importar-resultados';
    
    protected static ?string $navigationLabel = 'Importar Resultados';
    protected ?string $heading = 'Importar Resultados da Loteria';
    protected static string|\UnitEnum|null $navigationGroup = 'Lotofácil';
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
                Section::make('Envio de Arquivo')
                    ->description('Faça o upload do histórico de resultados (CSV, Excel ou TXT) para a pasta local.')
                    ->schema([
                        FileUpload::make('arquivo_historico')
                            ->label('Arquivo de Histórico')
                            ->required()
                            ->disk('data_dir') // Usa o disco raiz
                            ->directory('historicos') // Salva em /data/historicos
                            ->preserveFilenames()
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
                ->label('Enviar Arquivo')
                ->color('primary')
                ->action('save'),
        ];
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $fileName = $data['arquivo_historico'] ?? null;
        
        if ($fileName) {
            // No FileUpload, o path salvo no state geralmente é relativo ao disco. Ex: 'historicos/Lotofacil.xlsx'
            // O disk data_dir aponta para base_path('../data')
            $path = base_path('../data/' . $fileName);
            
            try {
                $pythonUrl = rtrim(config('services.python_api.url', 'http://127.0.0.1:5000'), '/');
                $response = Http::timeout(300)->post("{$pythonUrl}/importar-historico", [
                    'path' => $path
                ]);
                
                if ($response->successful()) {
                    $this->currentLogFile = $response->json('log_file');
                    $this->isProcessing = true;
                    $this->updateLogs(); // Lẽ o log pela primeira vez
                    
                    Notification::make()
                        ->title('Processo Iniciado')
                        ->body($response->json('message') ?? 'Importação disparada com sucesso!')
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
                        ->body('Todas as operações matemáticas em background foram finalizadas.')
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
