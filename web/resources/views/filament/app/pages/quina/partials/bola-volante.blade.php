@php
    $isMinha = in_array($numero, $minhasDezenas);
    $isOutro = in_array($numero, $dezenasOutros);
    $isBlocked = $isBlocked ?? false;

    $tempRaw = strtolower($tendencias[$numero] ?? 'frio');
    $temp = match($tempRaw) {
        'quente' => 'quente',
        'morna', 'morno' => 'morno',
        default => 'frio',
    };

    if ($isMinha) {
        $bg = 'radial-gradient(circle at 35% 35%, #86efac, #16a34a 55%, #14532d)';
        $shadow = '0 0 12px rgba(34, 197, 94, 0.6), inset 2px 2px 4px rgba(255,255,255,0.85), inset -2px -2px 5px rgba(0,0,0,0.5)';
        $textColor = '#ffffff';
        $textShadow = '0 1px 2px rgba(0,0,0,0.7)';
        $cursor = 'pointer';
        $border = '2px solid #22c55e';
        $opacity = '1';
        $disabled = false;
        $label = 'Sua dezena';
    } elseif ($isOutro) {
        $bg = 'radial-gradient(circle at 35% 30%, #f1f5f9 0%, #cbd5e1 32%, #94a3b8 70%, #64748b 100%)';
        $shadow = '0 3px 5px -1px rgba(0,0,0,0.5), inset 2px 2px 4px rgba(255,255,255,0.95), inset -2px -2px 5px rgba(0,0,0,0.35)';
        $textColor = '#1e293b';
        $textShadow = '0 1px 0 rgba(255,255,255,0.8)';
        $cursor = 'not-allowed';
        $border = 'none';
        $opacity = '0.9';
        $disabled = true;
        $label = 'Outro participante';
    } elseif ($isBlocked || $isLocked) {
        $bg = '#141824';
        $shadow = 'inset 0 1px 2px rgba(0, 0, 0, 0.5)';
        $textColor = '#334155';
        $textShadow = 'none';
        $cursor = 'not-allowed';
        $border = '1px solid #1e2433';
        $opacity = '0.35';
        $disabled = true;
        $label = 'Bloqueada por limite';
    } else {
        // Estilo Esfera 3D Colorida pela Temperatura
        if ($temp === 'quente') {
            $bg = 'radial-gradient(circle at 35% 35%, #fca5a5, #dc2626 55%, #7f1d1d)';
            $shadow = '0 4px 6px -1px rgba(220,38,38,0.4), inset 2px 2px 4px rgba(255,255,255,0.85), inset -2px -2px 5px rgba(0,0,0,0.4)';
            $label = 'Quente';
        } elseif ($temp === 'morno') {
            $bg = 'radial-gradient(circle at 35% 35%, #fde68a, #d97706 55%, #78350f)';
            $shadow = '0 4px 6px -1px rgba(217,119,6,0.4), inset 2px 2px 4px rgba(255,255,255,0.85), inset -2px -2px 5px rgba(0,0,0,0.4)';
            $label = 'Morna';
        } else {
            $bg = 'radial-gradient(circle at 35% 35%, #818cf8, #4338ca 55%, #1e1b4b)';
            $shadow = '0 4px 6px -1px rgba(67, 56, 202, 0.4), inset 2px 2px 4px rgba(255,255,255,0.85), inset -2px -2px 5px rgba(0,0,0,0.4)';
            $label = 'Fria';
        }
        $textColor = '#ffffff';
        $textShadow = '0 1px 2px rgba(0,0,0,0.7)';
        $cursor = 'pointer';
        $border = 'none';
        $opacity = '1';
        $disabled = false;
    }
@endphp

<button
    type="button"
    wire:click="toggleNumero({{ $numero }})"
    @if($disabled) disabled @endif
    title="{{ $label }}: {{ str_pad($numero, 2, '0', STR_PAD_LEFT) }}"
    style="
        width: 2.25rem;
        height: 2.25rem;
        border-radius: 9999px;
        background: {{ $bg }};
        box-shadow: {{ $shadow }};
        color: {{ $textColor }};
        text-shadow: {{ $textShadow }};
        border: {{ $border }};
        opacity: {{ $opacity }};
        cursor: {{ $cursor }};
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-weight: 800;
        font-size: 0.8125rem;
        user-select: none;
        transition: transform 0.15s ease, filter 0.15s ease;
    "
    onmouseover="if (!this.disabled) { this.style.transform='scale(1.15)'; this.style.zIndex='10'; }"
    onmouseout="this.style.transform='scale(1)'; this.style.zIndex='1';"
>
    {{ str_pad($numero, 2, '0', STR_PAD_LEFT) }}
</button>
