@php
    $fechamento = $getRecord()->fechamento;

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
@endphp

<div style="display: flex; flex-wrap: wrap; gap: 0.375rem; padding-top: 0.5rem; padding-bottom: 0.5rem;">
    @foreach($dezenas as $dezena)
        @php
            $isAcerto = in_array($dezena, $resultado);
            $bgColor = $isAcerto ? '#22c55e' : '#4b5563';
            $textColor = $isAcerto ? '#ffffff' : '#d1d5db';
        @endphp
        <div style="background-color: {{ $bgColor }}; color: {{ $textColor }}; width: 2rem; height: 2rem; border-radius: 9999px; display: flex; align-items: center; justify-content: center; font-size: 0.75rem; font-weight: 700; box-shadow: inset 0 2px 4px 0 rgba(0, 0, 0, 0.06);">
            {{ str_pad($dezena, 2, '0', STR_PAD_LEFT) }}
        </div>
    @endforeach
</div>
