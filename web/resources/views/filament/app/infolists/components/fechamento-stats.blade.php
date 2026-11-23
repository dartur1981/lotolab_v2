@php
    $record = (isset($getRecord) && is_callable($getRecord)) ? $getRecord() : ($record ?? null);
    
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

    $dezenas = $record ? $parseArr($record->dezenas ?? []) : [];
    $resultado = $record ? $parseArr($record->resultado ?? []) : [];

    $hasResultado = !empty($resultado);

    $cobertos = 0;
    if ($hasResultado && is_array($dezenas) && is_array($resultado)) {
        $cobertos = count(array_intersect($dezenas, $resultado));
    }

    $isMega = ($record instanceof \App\Models\Megasena\MegasenaFechamento) 
        || (isset($record->concurso) && $record->concurso instanceof \App\Models\Megasena\Concurso)
        || request()->is('*megasena*')
        || request()->routeIs('*megasena*')
        || str_contains(request()->path(), 'megasena');

    $isQuina = ($record instanceof \App\Models\Quina\QuinaFechamento)
        || request()->is('*quina*')
        || request()->routeIs('*quina*')
        || str_contains(request()->path(), 'quina');

    $totalSorteados = $isQuina ? 5 : ($isMega ? 6 : 15);
    $faixas = $isQuina ? [5, 4, 3, 2] : ($isMega ? [6, 5, 4, 3, 2] : [15, 14, 13, 12, 11]);

    $estatisticas = array_fill_keys($faixas, 0);

    if ($hasResultado && $record && isset($record->jogos)) {
        foreach ($record->jogos as $jogo) {
            $jogoDezenas = $parseArr($jogo->dezenas ?? []);
            
            $acertos = count(array_intersect($jogoDezenas, $resultado));
            if (isset($estatisticas[$acertos])) {
                $estatisticas[$acertos]++;
            }
        }
    }
@endphp

<style>
    .stats-container {
        background-color: #111827;
        border-radius: 1rem;
        padding: 1.5rem;
        border: 1px solid #1f2937;
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
        margin-bottom: 1.5rem;
    }
    .stats-title {
        color: white;
        font-size: 1.125rem;
        font-weight: 700;
    }
    .stats-balls-container {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
    }
    .stats-ball {
        width: 2.5rem;
        height: 2.5rem;
        border-radius: 9999px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.875rem;
        font-weight: 700;
        box-shadow: inset 0 2px 4px 0 rgba(0, 0, 0, 0.06);
    }
    .stats-msg-box {
        width: 100%;
        background-color: #1f2937;
        border: 1px solid #374151;
        border-radius: 0.375rem;
        padding: 0.375rem;
        text-align: center;
        box-shadow: inset 0 2px 4px 0 rgba(0, 0, 0, 0.06);
    }
    .stats-msg-box.yellow-border {
        border-color: rgba(161, 98, 7, 0.5); /* yellow-700/50 */
    }
    .stats-msg-text {
        color: #9ca3af;
        font-weight: 700;
        font-size: 0.875rem;
        letter-spacing: 0.025em;
    }
    .stats-msg-text-yellow {
        color: #eab308;
    }
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(5, 1fr);
        gap: 1rem;
        margin-top: 0.5rem;
    }
    @media (max-width: 768px) {
        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }
    .stats-card {
        border-radius: 0.75rem;
        padding: 1rem;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        height: 7rem;
        border-bottom-width: 4px;
        border-bottom-style: solid;
    }
    .card-15 { background-color: #2563eb; border-bottom-color: #1e40af; }
    .card-14 { background-color: #16a34a; border-bottom-color: #166534; }
    .card-13 { background-color: #eab308; border-bottom-color: #a16207; }
    .card-12 { background-color: #dc2626; border-bottom-color: #991b1b; }
    .card-11 { background-color: #f87171; border-bottom-color: #dc2626; }
    
    .card-title {
        font-size: 0.75rem;
        font-weight: 700;
        letter-spacing: 0.05em;
        text-transform: uppercase;
    }
    .card-15 .card-title { color: #dbeafe; }
    .card-14 .card-title { color: #dcfce7; }
    .card-13 .card-title { color: #fef9c3; }
    .card-12 .card-title { color: #fee2e2; }
    .card-11 .card-title { color: #fef2f2; }

    .card-value-container {
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
    }
    .card-value {
        color: white;
        font-size: 2.25rem;
        font-weight: 800;
        line-height: 1;
    }
    .card-subtitle {
        font-size: 0.625rem;
        text-transform: uppercase;
        font-weight: 700;
        margin-bottom: 0.25rem;
    }
    .card-15 .card-subtitle { color: #bfdbfe; }
</style>

<div class="stats-container">
    
    @php
        $bolao = $record->bolao ?? null;
        $jogosCollection = $record->jogos ?? collect();
        $totalCartelas = $jogosCollection->count() ?: ($record->quantidade_jogos ?? 0);
        $totalApostados = $jogosCollection->where('status', '4')->count();
        $totalDivergentes = $jogosCollection->where('status', '1')->count();

        $precoUnit = $isMega ? 5.00 : 3.00;
        $valTotalBolao = ($bolao && $bolao->valor_total > 0) ? (float) $bolao->valor_total : ($totalCartelas * $precoUnit);
        $valCotaBolao = ($bolao && $bolao->valor_cota > 0) ? (float) $bolao->valor_cota : $valTotalBolao;
        
        $percentApostado = $totalCartelas > 0 ? round(($totalApostados / $totalCartelas) * 100) : 0;
    @endphp

    {{-- Cards de Resumo da Conferência e Rateio --}}
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 0.75rem; width: 100%; margin-bottom: 0.5rem;">
        {{-- Card 1: Status do Robô --}}
        <div style="background: #111827; border: 1px solid {{ $percentApostado === 100 ? 'rgba(16, 185, 129, 0.5)' : '#374151' }}; border-radius: 0.75rem; padding: 0.85rem 1rem; display: flex; align-items: center; gap: 0.75rem; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);">
            <div style="width: 2.5rem; height: 2.5rem; border-radius: 0.5rem; background: {{ $percentApostado === 100 ? '#064e3b' : '#1f2937' }}; color: {{ $percentApostado === 100 ? '#34d399' : '#9ca3af' }}; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 1.125rem;">
                {{ $percentApostado === 100 ? '✓' : ($totalApostados > 0 ? '⏳' : '🤖') }}
            </div>
            <div>
                <div style="font-size: 0.6875rem; font-weight: 700; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.05em;">Status do Robô</div>
                <div style="font-size: 0.875rem; font-weight: 700; color: {{ $percentApostado === 100 ? '#10b981' : ($totalApostados > 0 ? '#fbbf24' : '#e2e8f0') }};">
                    @if($percentApostado === 100)
                        100% Validado no Carrinho
                    @elseif($totalApostados > 0)
                        {{ $totalApostados }}/{{ $totalCartelas }} Jogos Validados
                    @else
                        Aguardando Lançamento
                    @endif
                </div>
            </div>
        </div>

        {{-- Card 2: Apostas no Carrinho --}}
        <div style="background: #111827; border: 1px solid #374151; border-radius: 0.75rem; padding: 0.85rem 1rem; display: flex; align-items: center; gap: 0.75rem; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);">
            <div style="width: 2.5rem; height: 2.5rem; border-radius: 0.5rem; background: #0c4a6e; color: #38bdf8; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 1.125rem;">
                🛒
            </div>
            <div>
                <div style="font-size: 0.6875rem; font-weight: 700; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.05em;">Apostas no Carrinho</div>
                <div style="font-size: 0.875rem; font-weight: 700; color: #ffffff;">
                    {{ $totalApostados }} de {{ $totalCartelas }} Jogos ({{ $totalDivergentes }} Diverg.)
                </div>
            </div>
        </div>

        {{-- Card 3: Total do Bolão e Cota --}}
        <div style="background: #111827; border: 1px solid #374151; border-radius: 0.75rem; padding: 0.85rem 1rem; display: flex; align-items: center; gap: 0.75rem; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);">
            <div style="width: 2.5rem; height: 2.5rem; border-radius: 0.5rem; background: #78350f; color: #fbbf24; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 1.125rem;">
                💰
            </div>
            <div>
                <div style="font-size: 0.6875rem; font-weight: 700; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.05em;">Total do Bolão / Cota</div>
                <div style="font-size: 0.875rem; font-weight: 700; color: #ffffff;">
                    R$ {{ number_format($valTotalBolao, 2, ',', '.') }}
                    <span style="font-size: 0.75rem; color: #94a3b8; font-weight: 500;">(R$ {{ number_format($valCotaBolao, 2, ',', '.') }}/cota)</span>
                </div>
            </div>
        </div>
    </div>

    <div class="stats-title">
        {{ count($dezenas) }} Dezenas Selecionadas
    </div>

    <div class="stats-balls-container">
        @foreach($dezenas as $dezena)
            @php
                $isAcerto = $hasResultado ? in_array($dezena, $resultado) : false;
                $bgColor = $isAcerto ? '#22c55e' : '#4b5563';
                $textColor = $isAcerto ? '#ffffff' : '#d1d5db';
            @endphp
            <div class="stats-ball" style="background-color: {{ $bgColor }}; color: {{ $textColor }};">
                {{ str_pad($dezena, 2, '0', STR_PAD_LEFT) }}
            </div>
        @endforeach
    </div>

    @php
        $rawGrupos = $record->grupos ?? null;
        if (is_string($rawGrupos)) {
            $rawGrupos = json_decode($rawGrupos, true) ?? [];
        }
        $gruposData = [];
        $tituloGrupos = $isQuina 
            ? '🎯 Matriz de Grupos (Duques e Ternos Combinados)' 
            : ($isMega ? '🎯 Matriz de Ternos (Grupos Combinados)' : '🎯 Grupos Combinados');
        
        if (is_array($rawGrupos)) {
            if (isset($rawGrupos['grupos']) && is_array($rawGrupos['grupos'])) {
                $gruposData = $rawGrupos['grupos'];
            } elseif (!isset($rawGrupos['metodo'])) {
                $gruposData = $rawGrupos;
            }
        }
    @endphp

    @if(!empty($gruposData) && is_array($gruposData))
        <div style="border-top: 1px solid #1f2937; padding-top: 1rem; margin-top: 0.5rem; text-align: center;">
            <div style="color: #94a3b8; font-size: 0.8rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.65rem; text-align: center;">
                {{ $tituloGrupos }}
            </div>
            <div style="display: flex; flex-wrap: wrap; gap: 0.65rem; justify-content: center; align-items: center;">
                @foreach($gruposData as $nomeGrupo => $dezenasGrupo)
                    @if(is_array($dezenasGrupo))
                        <div style="background: #1f2937; border: 1px solid #374151; border-radius: 0.5rem; padding: 0.35rem 0.65rem; display: flex; align-items: center; justify-content: center; gap: 0.5rem;">
                            <span style="font-weight: 800; font-size: 0.75rem; color: #38bdf8; background: #0c4a6e; padding: 0.15rem 0.4rem; border-radius: 0.25rem; text-align: center;">
                                {{ $nomeGrupo }}
                            </span>
                            <div style="display: flex; gap: 0.25rem; flex-wrap: wrap; justify-content: center; align-items: center;">
                                @foreach($dezenasGrupo as $dz)
                                    @if(is_scalar($dz))
                                        <span style="font-weight: 700; font-size: 0.8rem; color: #f1f5f9; background: #111827; padding: 0.1rem 0.35rem; border-radius: 0.25rem; border: 1px solid #374151; min-width: 1.6rem; text-align: center;">
                                            {{ str_pad((string)$dz, 2, '0', STR_PAD_LEFT) }}
                                        </span>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    @endif

    <div style="display: flex; flex-direction: column; gap: 0.5rem; margin-top: 0.5rem;">
        @if($hasResultado)
            @php
                $qtdSena = $isMega ? ($estatisticas[6] ?? 0) : 0;
                $qtdQuina = ($isMega || $isQuina) ? ($estatisticas[5] ?? 0) : 0;
                $qtdQuadra = ($isMega || $isQuina) ? ($estatisticas[4] ?? 0) : 0;
                $qtdTerno = $isQuina ? ($estatisticas[3] ?? 0) : 0;
                $qtdDuque = $isQuina ? ($estatisticas[2] ?? 0) : 0;
                $temPremio = $isQuina 
                    ? ($qtdQuina > 0 || $qtdQuadra > 0 || $qtdTerno > 0 || $qtdDuque > 0)
                    : ($isMega ? ($qtdSena > 0 || $qtdQuina > 0 || $qtdQuadra > 0) : false);
            @endphp

            @if($temPremio)
                <div style="background: linear-gradient(135deg, #14532d, #166534); border: 1px solid rgba(34, 197, 94, 0.5); border-radius: 0.75rem; padding: 0.85rem 1.25rem; display: flex; align-items: center; justify-content: space-between; color: #ffffff; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);">
                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                        <span style="font-size: 1.75rem;">🏆</span>
                        <div>
                            <div style="font-weight: 800; font-size: 0.95rem; letter-spacing: 0.025em; color: #f0fdf4;">
                                PARABÉNS! PREMIAÇÃO CONFIRMADA NESTE BOLÃO
                            </div>
                            <div style="font-size: 0.8rem; color: #bbf7d0;">
                                @if($qtdSena > 0) <strong style="color: #fde047;">{{ $qtdSena }}x Sena!</strong> @endif
                                @if($qtdQuina > 0) <strong style="color: #67e8f9;">{{ $qtdQuina }}x Quina!</strong> @endif
                                @if($qtdQuadra > 0) <strong style="color: #fdba74;">{{ $qtdQuadra }}x Quadra!</strong> @endif
                                @if($qtdTerno > 0) <strong style="color: #a7f3d0;">{{ $qtdTerno }}x Terno!</strong> @endif
                                @if($qtdDuque > 0) <strong style="color: #bae6fd;">{{ $qtdDuque }}x Duque!</strong> @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <div style="color: #9ca3af; font-size: 0.875rem; font-weight: 500;">
                Resultado: <span style="color: white;">{{ implode(' - ', array_map(fn($n) => str_pad($n, 2, '0', STR_PAD_LEFT), $resultado)) }}</span>
            </div>
            <div class="stats-msg-box yellow-border">
                <span class="stats-msg-text stats-msg-text-yellow">
                    {{ $cobertos }} de {{ $totalSorteados }} sorteados cobertos
                </span>
            </div>
        @endif
    </div>

    @php
        $cardClasses = ['card-15', 'card-14', 'card-13', 'card-12', 'card-11'];
        $faixaLabels = $isQuina ? [
            5 => '5 Acertos (Quina)',
            4 => '4 Acertos (Quadra)',
            3 => '3 Acertos (Terno)',
            2 => '2 Acertos (Duque)',
        ] : ($isMega ? [
            6 => '6 Acertos (Sena)',
            5 => '5 Acertos (Quina)',
            4 => '4 Acertos (Quadra)',
            3 => '3 Acertos (Terno)',
            2 => '2 Acertos (Duque)',
        ] : [
            15 => '15 Acertos',
            14 => '14 Acertos',
            13 => '13 Acertos',
            12 => '12 Acertos',
            11 => '11 Acertos',
        ]);
    @endphp

    <div class="stats-grid">
        @foreach($faixas as $idx => $faixa)
            <div class="stats-card {{ $cardClasses[$idx] ?? 'card-15' }}">
                <div class="card-title">{{ $faixaLabels[$faixa] ?? "{$faixa} Acertos" }}</div>
                <div class="card-value-container">
                    <span class="card-value">{{ $estatisticas[$faixa] ?? 0 }}</span>
                </div>
            </div>
        @endforeach
    </div>
</div>
