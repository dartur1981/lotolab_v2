@php
    $record = $getRecord();
    $fechamento = $record->fechamento ?? null;
    $hasResultado = !empty($fechamento?->resultado);
    $acertos = (int) ($record->acertos ?? 0);

    $isMega = ($fechamento instanceof \App\Models\Megasena\MegasenaFechamento) 
        || request()->is('*megasena*')
        || str_contains(request()->path(), 'megasena');

    $isQuina = ($fechamento instanceof \App\Models\Quina\QuinaFechamento)
        || request()->is('*quina*')
        || str_contains(request()->path(), 'quina');
@endphp

@if($hasResultado)
    <div style="display: flex; align-items: center; justify-content: center; width: 100%; margin: 0 auto;">
        @if($isQuina)
            @if($acertos >= 5)
                <span style="display: inline-flex; align-items: center; gap: 0.25rem; background: linear-gradient(135deg, #d97706, #b45309); color: #ffffff; font-weight: 800; font-size: 0.75rem; padding: 0.35rem 0.65rem; border-radius: 0.375rem; border: 1px solid rgba(245, 158, 11, 0.6); box-shadow: 0 2px 4px rgba(217, 119, 6, 0.3);">
                    🏆 5 (Quina)
                </span>
            @elseif($acertos === 4)
                <span style="display: inline-flex; align-items: center; gap: 0.25rem; background: linear-gradient(135deg, #4f46e5, #3730a3); color: #ffffff; font-weight: 800; font-size: 0.75rem; padding: 0.35rem 0.65rem; border-radius: 0.375rem; border: 1px solid rgba(99, 102, 241, 0.6); box-shadow: 0 2px 4px rgba(79, 70, 229, 0.3);">
                    ⭐ 4 (Quadra)
                </span>
            @elseif($acertos === 3)
                <span style="display: inline-flex; align-items: center; gap: 0.25rem; background: linear-gradient(135deg, #059669, #047857); color: #ffffff; font-weight: 800; font-size: 0.75rem; padding: 0.35rem 0.65rem; border-radius: 0.375rem; border: 1px solid rgba(16, 185, 129, 0.6); box-shadow: 0 2px 4px rgba(5, 150, 105, 0.3);">
                    🎯 3 (Terno)
                </span>
            @elseif($acertos === 2)
                <span style="display: inline-flex; align-items: center; gap: 0.25rem; background: linear-gradient(135deg, #0284c7, #0369a1); color: #ffffff; font-weight: 800; font-size: 0.75rem; padding: 0.35rem 0.65rem; border-radius: 0.375rem; border: 1px solid rgba(14, 165, 233, 0.6); box-shadow: 0 2px 4px rgba(2, 132, 199, 0.3);">
                    🎲 2 (Duque)
                </span>
            @else
                <div style="background-color: #374151; color: #9ca3af; width: 2rem; height: 2rem; border-radius: 9999px; display: flex; align-items: center; justify-content: center; font-size: 0.75rem; font-weight: 700; border: 1px solid #4b5563;">
                    {{ $acertos }}
                </div>
            @endif
        @elseif($isMega)
            @if($acertos >= 6)
                <span style="display: inline-flex; align-items: center; gap: 0.25rem; background: linear-gradient(135deg, #d97706, #b45309); color: #ffffff; font-weight: 800; font-size: 0.75rem; padding: 0.35rem 0.65rem; border-radius: 0.375rem; border: 1px solid rgba(245, 158, 11, 0.6); box-shadow: 0 2px 4px rgba(217, 119, 6, 0.3);">
                    🏆 6 (Sena)
                </span>
            @elseif($acertos === 5)
                <span style="display: inline-flex; align-items: center; gap: 0.25rem; background: linear-gradient(135deg, #4f46e5, #3730a3); color: #ffffff; font-weight: 800; font-size: 0.75rem; padding: 0.35rem 0.65rem; border-radius: 0.375rem; border: 1px solid rgba(99, 102, 241, 0.6); box-shadow: 0 2px 4px rgba(79, 70, 229, 0.3);">
                    ⭐ 5 (Quina)
                </span>
            @elseif($acertos === 4)
                <span style="display: inline-flex; align-items: center; gap: 0.25rem; background: linear-gradient(135deg, #ea580c, #9a3412); color: #ffffff; font-weight: 800; font-size: 0.75rem; padding: 0.35rem 0.65rem; border-radius: 0.375rem; border: 1px solid rgba(234, 88, 12, 0.6); box-shadow: 0 2px 4px rgba(234, 88, 12, 0.3);">
                    🎯 4 (Quadra)
                </span>
            @else
                <div style="background-color: #374151; color: #9ca3af; width: 2rem; height: 2rem; border-radius: 9999px; display: flex; align-items: center; justify-content: center; font-size: 0.75rem; font-weight: 700; border: 1px solid #4b5563;">
                    {{ $acertos }}
                </div>
            @endif
        @else
            {{-- Lotofácil --}}
            @if($acertos >= 15)
                <span style="display: inline-flex; align-items: center; gap: 0.25rem; background: linear-gradient(135deg, #d97706, #b45309); color: #ffffff; font-weight: 800; font-size: 0.75rem; padding: 0.35rem 0.65rem; border-radius: 0.375rem; border: 1px solid rgba(245, 158, 11, 0.6);">
                    🏆 15 Pontos
                </span>
            @elseif($acertos === 14)
                <span style="display: inline-flex; align-items: center; gap: 0.25rem; background: linear-gradient(135deg, #4f46e5, #3730a3); color: #ffffff; font-weight: 800; font-size: 0.75rem; padding: 0.35rem 0.65rem; border-radius: 0.375rem; border: 1px solid rgba(99, 102, 241, 0.6);">
                    ⭐ 14 Pontos
                </span>
            @elseif($acertos >= 11)
                <span style="display: inline-flex; align-items: center; gap: 0.25rem; background: #065f46; color: #6ee7b7; font-weight: 700; font-size: 0.75rem; padding: 0.35rem 0.65rem; border-radius: 0.375rem; border: 1px solid rgba(16, 185, 129, 0.4);">
                    🎯 {{ $acertos }} Pontos
                </span>
            @else
                <div style="background-color: #374151; color: #9ca3af; width: 2rem; height: 2rem; border-radius: 9999px; display: flex; align-items: center; justify-content: center; font-size: 0.75rem; font-weight: 700;">
                    {{ $acertos }}
                </div>
            @endif
        @endif
    </div>
@else
    <div style="display: flex; align-items: center; justify-content: center; width: 100%;">
        <span style="color: #64748b; font-weight: 600; font-size: 0.875rem;">-</span>
    </div>
@endif
