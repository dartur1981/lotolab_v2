@php
    $isMinha = in_array($numero, $minhasDezenas);
    $isOutro = in_array($numero, $dezenasOutros);

    if ($isMinha) {
        $bg = 'radial-gradient(circle at 35% 30%, #6ee7b7 0%, #10b981 35%, #059669 70%, #064e3b 100%)';
        $shadow = '0 0 12px rgba(16, 185, 129, 0.6), inset 2px 2px 4px rgba(255,255,255,0.8), inset -2px -2px 5px rgba(0,0,0,0.5)';
        $textColor = '#ffffff';
        $textShadow = '0 1px 2px rgba(0,0,0,0.7)';
        $cursor = 'pointer';
        $border = 'none';
        $opacity = '1';
    } elseif ($isOutro) {
        $bg = 'radial-gradient(circle at 35% 30%, #475569 0%, #334155 50%, #1e293b 100%)';
        $shadow = 'inset -1px -1px 3px rgba(0, 0, 0, 0.6)';
        $textColor = '#64748b';
        $textShadow = 'none';
        $cursor = 'not-allowed';
        $border = '1px solid #334155';
        $opacity = '0.4';
    } else {
        // Estilo Esfera 3D Prata/Cinza idêntica à imagem
        $bg = 'radial-gradient(circle at 35% 30%, #f1f5f9 0%, #cbd5e1 32%, #94a3b8 70%, #64748b 100%)';
        $shadow = '0 4px 6px -1px rgba(0,0,0,0.5), inset 2px 2px 4px rgba(255,255,255,0.95), inset -2px -2px 5px rgba(0,0,0,0.35)';
        $textColor = '#475569';
        $textShadow = '0 1px 0 rgba(255,255,255,0.8)';
        $cursor = 'pointer';
        $border = 'none';
        $opacity = '1';
    }
@endphp

<button 
    type="button"
    wire:click="toggleNumero({{ $numero }})"
    @if($isOutro || $isLocked) disabled @endif
    style="
        width: 38px;
        height: 38px;
        border-radius: 9999px;
        background: {{ $bg }};
        box-shadow: {{ $shadow }};
        color: {{ $textColor }};
        text-shadow: {{ $textShadow }};
        border: {{ $border }};
        opacity: {{ $opacity }};
        font-family: ui-sans-serif, system-ui, sans-serif;
        font-weight: 800;
        font-size: 0.82rem;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: {{ $cursor }};
        user-select: none;
        transition: transform 0.12s ease-in-out, filter 0.12s ease-in-out;
    "
    onmouseover="if (!{{ $isOutro || $isLocked ? 'true' : 'false' }}) { this.style.transform='scale(1.12)'; this.style.filter='brightness(1.08)'; }"
    onmouseout="this.style.transform='scale(1)'; this.style.filter='brightness(1)';"
    title="Dezena {{ str_pad($numero, 2, '0', STR_PAD_LEFT) }}"
>
    {{ str_pad($numero, 2, '0', STR_PAD_LEFT) }}
</button>
