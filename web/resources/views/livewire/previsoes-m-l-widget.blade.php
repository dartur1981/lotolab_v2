<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            Previsões da Inteligência Artificial (Random Forest)
        </x-slot>
        <x-slot name="description">
            Probabilidade preditiva de sorteio de cada dezena para o próximo concurso, classificada da mais quente para a mais fria.
        </x-slot>

        @if($erro)
            <div class="p-4 mb-4 text-sm text-red-800 rounded-lg bg-red-50 dark:bg-gray-800 dark:text-red-400" role="alert">
                <span class="font-medium">Erro!</span> {{ $mensagem_erro }}
            </div>
        @else
            <style>
                .ml-predict-card {
                    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                }
                .ml-predict-card:hover {
                    transform: scale(1.03);
                    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
                    border-color: rgba(128, 128, 128, 0.3);
                }
            </style>
            @php
                $getColors = function ($prob) {
                    if ($prob >= 65) return ['bg' => 'rgba(16, 185, 129, 0.15)', 'text' => '#059669', 'bar' => '#10b981', 'border' => 'rgba(16, 185, 129, 0.3)']; // Emerald
                    if ($prob >= 50) return ['bg' => 'rgba(245, 158, 11, 0.15)', 'text' => '#d97706', 'bar' => '#f59e0b', 'border' => 'rgba(245, 158, 11, 0.3)']; // Amber
                    return ['bg' => 'rgba(225, 29, 72, 0.15)', 'text' => '#e11d48', 'bar' => '#e11d48', 'border' => 'rgba(225, 29, 72, 0.3)']; // Rose
                };
            @endphp

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem; margin-top: 1rem;">
                @foreach($previsoes as $previsao)
                    @php $c = $getColors($previsao['probabilidade']); @endphp
                    <div class="ml-predict-card" style="display: flex; flex-direction: column; padding: 1rem; border-radius: 0.75rem; background: var(--fi-bg); border: 1px solid var(--fi-border); box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05); cursor: default;">
                        
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.75rem;">
                            <div style="display: flex; align-items: center; justify-content: center; width: 3rem; height: 3rem; border-radius: 9999px; background-color: {{ $c['bg'] }}; border: 1px solid {{ $c['border'] }};">
                                <span style="font-size: 1.25rem; font-weight: 700; color: {{ $c['text'] }};">
                                    {{ str_pad($previsao['dezena'], 2, '0', STR_PAD_LEFT) }}
                                </span>
                            </div>
                            
                            <div style="display: flex; flex-direction: column; align-items: flex-end;">
                                <span style="font-size: 0.75rem; font-weight: 600; text-transform: uppercase; color: var(--fi-text-subdued);">Score</span>
                                <span style="font-size: 1.125rem; font-weight: 900; color: {{ $c['text'] }};">
                                    {{ number_format($previsao['probabilidade'], 1, ',', '.') }}%
                                </span>
                            </div>
                        </div>
                        
                        <div style="width: 100%; background-color: var(--fi-bg-subdued); border-radius: 9999px; height: 0.375rem; overflow: hidden; border: 1px solid var(--fi-border);">
                            <div style="background-color: {{ $c['bar'] }}; height: 100%; border-radius: 9999px; width: {{ $previsao['probabilidade'] }}%;"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
