@php
    $record = $getRecord();
    $fechamento = $record->fechamento ?? null;
    $status = (string) ($record->status ?? '0');
    $isApostado = ($status === '4');

    $parseArr = function($val) {
        if (empty($val)) return [];
        if (is_string($val)) {
            $decoded = json_decode($val, true);
            if (is_array($decoded)) return array_map('intval', $decoded);
            return array_map('intval', explode(',', $val));
        }
        if (is_array($val)) return array_map('intval', $val);
        return [];
    };

    $dezenas = $parseArr($getState());
    $resultado = $parseArr($fechamento ? ($fechamento->resultado ?? []) : []);
    $hasResultado = !empty($resultado);
@endphp

<div style="display: flex; flex-wrap: wrap; justify-content: center; align-items: center; gap: 0.375rem; padding-top: 0.5rem; padding-bottom: 0.5rem; width: 100%; margin: 0 auto;">
    @foreach($dezenas as $dezena)
        @php
            if ($hasResultado) {
                $isAcerto = in_array($dezena, $resultado);
                if ($isAcerto) {
                    $bgColor = '#22c55e';
                    $textColor = '#ffffff';
                    $border = '2px solid #16a34a';
                    $boxShadow = '0 0 10px rgba(34, 197, 94, 0.45)';
                    $opacity = '1';
                } else {
                    $bgColor = '#374151';
                    $textColor = '#9ca3af';
                    $border = '1px solid #4b5563';
                    $boxShadow = 'none';
                    $opacity = '0.65';
                }
            } else {
                if ($isApostado) {
                    // Jogo conferido e validado no carrinho da Caixa!
                    $bgColor = '#1e293b';
                    $textColor = '#f8fafc';
                    $border = '2px solid #10b981';
                    $boxShadow = '0 0 6px rgba(16, 185, 129, 0.35)';
                    $opacity = '1';
                } else {
                    $bgColor = '#4b5563';
                    $textColor = '#d1d5db';
                    $border = '1px solid #374151';
                    $boxShadow = 'inset 0 2px 4px 0 rgba(0, 0, 0, 0.06)';
                    $opacity = '1';
                }
            }
        @endphp
        <div style="background-color: {{ $bgColor }}; color: {{ $textColor }}; border: {{ $border }}; box-shadow: {{ $boxShadow }}; opacity: {{ $opacity }}; width: 2rem; height: 2rem; border-radius: 9999px; display: flex; align-items: center; justify-content: center; font-size: 0.75rem; font-weight: 700; transition: all 0.2s ease;">
            {{ str_pad($dezena, 2, '0', STR_PAD_LEFT) }}
        </div>
    @endforeach
</div>
