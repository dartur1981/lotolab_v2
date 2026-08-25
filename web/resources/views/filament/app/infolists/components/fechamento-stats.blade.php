@php
    $record = $getRecord();
    
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

    $dezenas = $parseArr($record->dezenas ?? []);
    $resultado = $parseArr($record->resultado ?? []);

    $hasResultado = !empty($resultado);

    $cobertos = 0;
    if ($hasResultado && is_array($dezenas) && is_array($resultado)) {
        $cobertos = count(array_intersect($dezenas, $resultado));
    }

    $estatisticas = [
        15 => 0,
        14 => 0,
        13 => 0,
        12 => 0,
        11 => 0,
    ];

    if ($hasResultado) {
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
    
    <div class="stats-title">
        {{ count($dezenas) }} Dezenas
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

    <div style="display: flex; flex-direction: column; gap: 0.5rem; margin-top: 0.5rem;">
        @if($hasResultado)
            <div style="color: #9ca3af; font-size: 0.875rem; font-weight: 500;">
                Resultado: <span style="color: white;">{{ implode(' - ', array_map(fn($n) => str_pad($n, 2, '0', STR_PAD_LEFT), $resultado)) }}</span>
            </div>
            <div class="stats-msg-box yellow-border">
                <span class="stats-msg-text stats-msg-text-yellow">
                    {{ $cobertos }} de 15 sorteados cobertos
                </span>
            </div>
        @endif
    </div>

    <div class="stats-grid">
        <div class="stats-card card-15">
            <div class="card-title">15 Acertos</div>
            <div class="card-value-container">
                <span class="card-value">{{ $estatisticas[15] }}</span>
                <span class="card-subtitle hidden sm:block" style="display: none;">Premio Max</span>
            </div>
        </div>

        <div class="stats-card card-14">
            <div class="card-title">14 Acertos</div>
            <div class="card-value-container">
                <span class="card-value">{{ $estatisticas[14] }}</span>
            </div>
        </div>

        <div class="stats-card card-13">
            <div class="card-title">13 Acertos</div>
            <div class="card-value-container">
                <span class="card-value">{{ $estatisticas[13] }}</span>
            </div>
        </div>

        <div class="stats-card card-12">
            <div class="card-title">12 Acertos</div>
            <div class="card-value-container">
                <span class="card-value">{{ $estatisticas[12] }}</span>
            </div>
        </div>

        <div class="stats-card card-11">
            <div class="card-title">11 Acertos</div>
            <div class="card-value-container">
                <span class="card-value">{{ $estatisticas[11] }}</span>
            </div>
        </div>
    </div>
</div>
