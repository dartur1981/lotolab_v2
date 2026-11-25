@php
    $total = $stats['total'] ?? 0;
    $apostado = $stats['apostado'] ?? 0;
    $lancado = $stats['lancado'] ?? 0;
    $pendente = $stats['pendente'] ?? 0;
    $divergente = $stats['divergente'] ?? 0;

    $pctApostado = $stats['pct_apostado'] ?? 0;
    $pctLancado = $stats['pct_lancado'] ?? 0;
    $pctPendente = $stats['pct_pendente'] ?? 0;
    $pctDivergente = $stats['pct_divergente'] ?? 0;

    $cards = [
        [
            'status' => '4',
            'label' => 'Apostados',
            'count' => $apostado,
            'pct' => $pctApostado,
            'dot' => '#34d399',
            'color' => '#34d399',
            'active_border' => 'rgba(52, 211, 153, 0.7)',
            'active_bg' => 'rgba(6, 78, 59, 0.35)',
        ],
        [
            'status' => '3',
            'label' => 'Lançados',
            'count' => $lancado,
            'pct' => $pctLancado,
            'dot' => '#38bdf8',
            'color' => '#38bdf8',
            'active_border' => 'rgba(56, 189, 248, 0.7)',
            'active_bg' => 'rgba(12, 74, 110, 0.35)',
        ],
        [
            'status' => '0',
            'label' => 'Pendentes',
            'count' => $pendente,
            'pct' => $pctPendente,
            'dot' => '#9ca3af',
            'color' => '#d1d5db',
            'active_border' => 'rgba(156, 163, 175, 0.7)',
            'active_bg' => 'rgba(55, 65, 81, 0.45)',
        ],
        [
            'status' => '1',
            'label' => 'Divergentes',
            'count' => $divergente,
            'pct' => $pctDivergente,
            'dot' => '#fbbf24',
            'color' => '#fde68a',
            'active_border' => 'rgba(251, 191, 36, 0.7)',
            'active_bg' => 'rgba(120, 53, 15, 0.35)',
        ],
    ];
@endphp

<div
    x-data="{
        open: (() => {
            try {
                const saved = localStorage.getItem('lotolab_status_cards_open');
                return saved === null ? true : saved === '1';
            } catch (e) {
                return true;
            }
        })(),
        toggle() {
            this.open = !this.open;
            try {
                localStorage.setItem('lotolab_status_cards_open', this.open ? '1' : '0');
            } catch (e) {}
        }
    }"
    style="background: #111827; border: 1px solid #1f2937; border-radius: 0.75rem; padding: 0.875rem 1rem; margin-bottom: 1rem;"
>
    {{-- Cabeçalho colapsável --}}
    <div style="display: flex; align-items: center; justify-content: space-between; gap: 0.75rem; flex-wrap: wrap;">
        <button
            type="button"
            x-on:click="toggle()"
            style="display: inline-flex; align-items: center; gap: 0.625rem; background: transparent; border: none; padding: 0; cursor: pointer; color: #f3f4f6; text-align: left;"
        >
            <span style="font-size: 0.875rem; font-weight: 700; color: #f9fafb;">Status dos Jogos</span>
            <span style="font-size: 0.75rem; font-family: ui-monospace, SFMono-Regular, Menlo, monospace; color: #9ca3af;">
                <strong style="color: #34d399;">{{ $apostado }}</strong>/{{ $total }} conferidos ({{ number_format($pctApostado, 0) }}%)
            </span>
        </button>

        <div style="display: flex; align-items: center; gap: 0.75rem;">
            @if ($activeStatusFilter !== null)
                <button
                    type="button"
                    wire:click="filtrarPorStatus('{{ $activeStatusFilter }}')"
                    style="font-size: 0.75rem; color: #38bdf8; background: rgba(56, 189, 248, 0.1); border: 1px solid rgba(56, 189, 248, 0.3); border-radius: 9999px; padding: 0.15rem 0.6rem; cursor: pointer; font-weight: 600;"
                >
                    Limpar filtro
                </button>
            @endif

            <button
                type="button"
                x-on:click="toggle()"
                style="display: inline-flex; align-items: center; gap: 0.3rem; font-size: 0.75rem; font-weight: 600; color: #9ca3af; background: transparent; border: none; padding: 0.15rem 0.25rem; cursor: pointer;"
            >
                <span x-text="open ? 'Recolher' : 'Expandir'">Recolher</span>
                <svg
                    x-bind:style="open ? 'transform: rotate(180deg);' : 'transform: rotate(0deg);'"
                    style="width: 1rem; height: 1rem; transition: transform 150ms ease;"
                    viewBox="0 0 20 20"
                    fill="currentColor"
                >
                    <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                </svg>
            </button>
        </div>
    </div>

    {{-- Barra de progresso segmentada (sempre visível) --}}
    <div style="margin-top: 0.65rem; height: 0.5rem; width: 100%; border-radius: 9999px; overflow: hidden; display: flex; background: #1f2937;">
        @if ($pctApostado > 0)
            <div style="width: {{ $pctApostado }}%; background: #10b981; transition: width 300ms ease;" title="Apostados: {{ $apostado }}"></div>
        @endif
        @if ($pctLancado > 0)
            <div style="width: {{ $pctLancado }}%; background: #38bdf8; transition: width 300ms ease;" title="Lançados: {{ $lancado }}"></div>
        @endif
        @if ($pctDivergente > 0)
            <div style="width: {{ $pctDivergente }}%; background: #f59e0b; transition: width 300ms ease;" title="Divergentes: {{ $divergente }}"></div>
        @endif
        @if ($pctPendente > 0)
            <div style="width: {{ $pctPendente }}%; background: #6b7280; transition: width 300ms ease;" title="Pendentes: {{ $pendente }}"></div>
        @endif
    </div>

    {{-- 4 Mini Cards clicáveis (colapsáveis) --}}
    <div x-show="open" x-collapse>
        <div class="lotolab-status-cards-grid" style="margin-top: 0.75rem; display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 0.625rem; width: 100%;">
            @foreach ($cards as $card)
                @php
                    $isActive = ($activeStatusFilter === $card['status']);
                    $borderStyle = $isActive ? "1px solid {$card['active_border']}" : '1px solid #1f2937';
                    $bgStyle = $isActive ? $card['active_bg'] : '#0b1220';
                    $ringStyle = $isActive ? "box-shadow: 0 0 0 1px {$card['active_border']};" : '';
                @endphp
                <button
                    type="button"
                    wire:click="filtrarPorStatus('{{ $card['status'] }}')"
                    title="Clique para filtrar por {{ $card['label'] }}"
                    style="width: 100%; display: flex; align-items: center; justify-content: space-between; gap: 0.5rem; padding: 0.65rem 0.85rem; border-radius: 0.5rem; border: {{ $borderStyle }}; background: {{ $bgStyle }}; {{ $ringStyle }} cursor: pointer; text-align: left; transition: filter 150ms ease;"
                    onmouseover="this.style.filter='brightness(1.12)'"
                    onmouseout="this.style.filter='none'"
                >
                    <span style="display: inline-flex; align-items: center; gap: 0.45rem; font-size: 0.8rem; font-weight: 600; color: #d1d5db;">
                        <span style="width: 0.5rem; height: 0.5rem; border-radius: 9999px; background: {{ $card['dot'] }}; flex-shrink: 0;"></span>
                        <span>{{ $card['label'] }}</span>
                    </span>
                    <span style="display: inline-flex; align-items: baseline; gap: 0.25rem;">
                        <span style="font-size: 1rem; font-weight: 800; font-variant-numeric: tabular-nums; color: {{ $card['color'] }};">{{ $card['count'] }}</span>
                        <span style="font-size: 0.7rem; color: #6b7280; font-variant-numeric: tabular-nums;">({{ number_format($card['pct'], 0) }}%)</span>
                    </span>
                </button>
            @endforeach
        </div>
    </div>
    <style>
        @media (max-width: 768px) {
            .lotolab-status-cards-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            }
        }
    </style>
</div>
