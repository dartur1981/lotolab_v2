<x-filament-panels::page>
@if($bolaoAtivo)
    <div wire:poll.2s="carregarDezenasOutros" style="display: flex; flex-direction: column; gap: 1.5rem; width: 100%;">
        
        @if(count($meusBoloes) > 1)
        <div style="background-color: var(--fi-bg); border: 1px solid var(--fi-border); padding: 1rem; border-radius: 0.75rem; width: 100%;">
            {{ $this->form }}
        </div>
        @endif

        @php
            $userIds = DB::table('quina_bolao_user')->where('quina_bolao_id', $bolaoAtivo->id)->orderBy('id')->pluck('user_id')->toArray();
            $isUltimo = end($userIds) == auth()->id();
            $qtd = count($userIds);
            $totalBolao = $bolaoAtivo->total_numeros;
            $porPessoa = $qtd > 0 ? (int) floor($totalBolao / $qtd) : 0;
            $sobra = $qtd > 0 ? (int) ($totalBolao % $qtd) : 0;
            $meuLimite = $isUltimo ? ($porPessoa + $sobra) : $porPessoa;
            $selecionados = $minhasDezenas;

            $maxQ1 = $bolaoAtivo->max_quadrante_1 ?? 3;
            $maxQ2 = $bolaoAtivo->max_quadrante_2 ?? 3;
            $maxQ3 = $bolaoAtivo->max_quadrante_3 ?? 3;
            $maxQ4 = $bolaoAtivo->max_quadrante_4 ?? 3;

            $quadrantesDef = [
                [
                    'id' => 1,
                    'label' => 'Q1: 01 – 20',
                    'count' => $stats['q1'],
                    'max' => $maxQ1,
                ],
                [
                    'id' => 2,
                    'label' => 'Q2: 21 – 40',
                    'count' => $stats['q2'],
                    'max' => $maxQ2,
                ],
                [
                    'id' => 3,
                    'label' => 'Q3: 41 – 60',
                    'count' => $stats['q3'],
                    'max' => $maxQ3,
                ],
                [
                    'id' => 4,
                    'label' => 'Q4: 61 – 80',
                    'count' => $stats['q4'],
                    'max' => $maxQ4,
                ],
            ];
        @endphp

        {{-- Avisos de Status --}}
        @if ($isLocked)
            <div style="border-radius: 0.75rem; background-color: rgba(220, 38, 38, 0.2); border: 1px solid #b91c1c; padding: 0.75rem; color: #fca5a5; font-size: 0.875rem; font-weight: 500; text-align: center;">
                🔒 Bolão da Quina Encerrado. O pool de dezenas está completo e pronto para geração de fechamentos.
            </div>
        @elseif (count($selecionados) >= $meuLimite)
            <div style="border-radius: 0.75rem; background-color: rgba(6, 78, 59, 0.2); border: 1px solid #047857; padding: 0.75rem; color: #6ee7b7; font-size: 0.875rem; font-weight: 500; text-align: center;">
                ✅ Você já selecionou todas as suas {{ $meuLimite }} dezenas da cota.
            </div>
        @endif

        {{-- Aviso se absorveu a sobra --}}
        @if ($isUltimo && $sobra > 0 && !$isLocked)
            <div style="border-radius: 0.75rem; background-color: rgba(120, 53, 15, 0.25); border: 1px solid #d97706; padding: 0.75rem 1rem; color: #fcd34d; font-size: 0.875rem; font-weight: 500; display: flex; align-items: center; gap: 0.5rem;">
                <span style="font-size: 1.1rem;">⚠️</span>
                <span>Como a divisão não foi exata, a sua cota recebeu as <strong>{{ $sobra }} dezena(s) da sobra</strong>. Você escolherá <strong>{{ $meuLimite }} dezenas</strong> no total ({{ $porPessoa }} normais + {{ $sobra }} da sobra).</span>
            </div>
        @endif

        {{-- Layout Principal: Volante na Esquerda + Cards Informativos na Direita --}}
        <div style="display: flex; gap: 1.5rem; align-items: flex-start; width: 100%; flex-wrap: wrap;">
            
            {{-- COLUNA DA ESQUERDA: VOLANTE E OS 4 QUADRANTES --}}
            <div style="flex: 1 1 680px; min-width: 0; display: flex; flex-direction: column; gap: 1.5rem;">
                
                {{-- Barra de Resumo Superior --}}
                <div style="display: flex; align-items: center; justify-content: space-between; background-color: #0d0f12; border: 1px solid #1e2229; padding: 1rem 1.25rem; border-radius: 0.875rem; flex-wrap: wrap; gap: 0.75rem;">
                    <div>
                        <p style="font-size: 0.875rem; color: #9ca3af; margin: 0;">
                            Bolão: <strong style="color: white;">{{ $bolaoAtivo->nome }}</strong> · Cota: <strong style="color: #818cf8;">{{ $meuLimite }} dezenas</strong>
                        </p>
                        <p style="font-size: 0.75rem; color: #6b7280; margin: 0.25rem 0 0 0;">
                            Distribuição em 4 quadrantes espaciais (80 números divididos em blocos de 20).
                        </p>
                    </div>
                    <div style="display: flex; align-items: center; gap: 1rem; flex-wrap: wrap;">
                        <div style="font-size: 0.875rem; color: #d1d5db; display: flex; gap: 0.75rem; flex-wrap: wrap; align-items: center;">
                            <span style="color: #f87171;">🔥 <strong>{{ $stats['quentes'] ?? 0 }}</strong> Quente</span>
                            <span style="color: #fbbf24;">⚡ <strong>{{ $stats['mornos'] ?? 0 }}</strong> Morna</span>
                            <span style="color: #818cf8;">❄️ <strong>{{ $stats['frios'] ?? 0 }}</strong> Fria</span>
                            <span style="color: #4b5563;">|</span>
                            <span>P/I: <strong>{{ $stats['pares'] }}P / {{ $stats['impares'] }}I</strong></span>
                            <span>Soma: <strong>{{ $stats['soma'] }}</strong></span>
                        </div>
                        <span style="font-size: 1.125rem; font-weight: 800; color: {{ count($selecionados) >= $meuLimite ? '#10b981' : '#818cf8' }}; background: #161b22; padding: 0.25rem 0.75rem; border-radius: 0.5rem; border: 1px solid #21262d;">
                            {{ count($selecionados) }} / {{ $meuLimite }}
                        </span>
                    </div>
                </div>

                {{-- Legenda de Cores das Bolas por Temperatura --}}
                <div style="display: flex; align-items: center; justify-content: flex-start; gap: 1.25rem; background-color: #0b0d11; border: 1px solid #1e2229; padding: 0.65rem 1.25rem; border-radius: 0.75rem; flex-wrap: wrap;">
                    <span style="font-size: 0.75rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em;">Legenda:</span>
                    <div style="display: flex; align-items: center; gap: 0.45rem; font-size: 0.8rem; color: #cbd5e1;">
                        <span style="width: 14px; height: 14px; border-radius: 9999px; background: radial-gradient(circle at 35% 35%, #fca5a5, #dc2626 55%, #7f1d1d); display: inline-block;"></span>
                        <span>Quente</span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 0.45rem; font-size: 0.8rem; color: #cbd5e1;">
                        <span style="width: 14px; height: 14px; border-radius: 9999px; background: radial-gradient(circle at 35% 35%, #fde68a, #d97706 55%, #78350f); display: inline-block;"></span>
                        <span>Morna</span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 0.45rem; font-size: 0.8rem; color: #cbd5e1;">
                        <span style="width: 14px; height: 14px; border-radius: 9999px; background: radial-gradient(circle at 35% 35%, #818cf8, #4338ca 55%, #1e1b4b); display: inline-block;"></span>
                        <span>Fria</span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 0.45rem; font-size: 0.8rem; color: #cbd5e1;">
                        <span style="width: 14px; height: 14px; border-radius: 9999px; background: radial-gradient(circle at 35% 35%, #86efac, #16a34a 55%, #14532d); outline: 2px solid #22c55e; display: inline-block;"></span>
                        <span style="color: #34d399; font-weight: 700;">Sua Marcada</span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 0.45rem; font-size: 0.8rem; color: #cbd5e1;">
                        <span style="width: 14px; height: 14px; border-radius: 9999px; background: radial-gradient(circle at 35% 30%, #f1f5f9 0%, #cbd5e1 32%, #94a3b8 70%, #64748b 100%); display: inline-block;"></span>
                        <span>Outro</span>
                    </div>
                </div>

                {{-- CONTAINER DOS 4 QUADRANTES --}}
                <div style="display: flex; flex-direction: column; gap: 1.5rem; width: 100%;">
                    @foreach($quadrantesDef as $quadrante)
                        <div style="
                            border: 1px solid #232730;
                            background: #0b0d11;
                            border-radius: 1rem;
                            padding: 1.5rem 1rem 1.25rem 1rem;
                            position: relative;
                        ">
                            {{-- Título do Quadrante embutido na borda superior --}}
                            <div style="
                                position: absolute;
                                top: -0.65rem;
                                left: 1.5rem;
                                background: #0b0d11;
                                padding: 0 0.6rem;
                                font-family: ui-monospace, monospace;
                                font-size: 0.85rem;
                                color: #94a3b8;
                                letter-spacing: 0.05em;
                                display: flex;
                                align-items: center;
                                gap: 0.6rem;
                            ">
                                <span style="color: #cbd5e1; font-weight: 600;">{{ $quadrante['label'] }}</span>
                                <span style="color: {{ $quadrante['count'] > $quadrante['max'] ? '#f87171' : ($quadrante['count'] == $quadrante['max'] ? '#818cf8' : '#64748b') }}; font-weight: 700;">
                                    {{ $quadrante['count'] }}/{{ $quadrante['max'] }}
                                </span>
                            </div>

                            {{-- 5 Blocos 2x2 por Quadrante (Total de 10 colunas x 2 linhas = 20 dezenas) --}}
                            <div style="
                                display: grid;
                                grid-template-columns: repeat(5, minmax(0, 1fr));
                                gap: 0.85rem;
                                width: 100%;
                            ">
                                @for($col = 1; $col <= 5; $col++)
                                    @php
                                        $qIdx = $quadrante['id'] - 1; // 0, 1, 2, 3
                                        $blocoCol = $col - 1; // 0, 1, 2, 3, 4
                                        $linhaBase = $qIdx * 20;
                                        $colStart = $blocoCol * 2 + 1;

                                        $n1 = $linhaBase + $colStart;
                                        $n2 = $linhaBase + $colStart + 1;
                                        $n3 = $linhaBase + $colStart + 10;
                                        $n4 = $linhaBase + $colStart + 11;

                                        $isQuadFull = $quadrante['max'] > 0 && $quadrante['count'] >= $quadrante['max'];
                                        $isBlocked = $isQuadFull;
                                    @endphp
                                    <div style="
                                        border: 1px solid #1a1e27;
                                        background: #11141a;
                                        border-radius: 0.85rem;
                                        padding: 0.85rem 0.6rem;
                                        display: flex;
                                        flex-direction: column;
                                        align-items: center;
                                        justify-content: center;
                                        box-shadow: inset 0 1px 2px rgba(255, 255, 255, 0.03);
                                        position: relative;
                                    ">
                                        {{-- Linha Superior do Bloco 2x2 --}}
                                        <div style="display: flex; gap: 0.6rem; margin-bottom: 0.6rem;">
                                            @include('filament.app.pages.quina.partials.bola-volante', ['numero' => $n1, 'isBlocked' => $isBlocked])
                                            @include('filament.app.pages.quina.partials.bola-volante', ['numero' => $n2, 'isBlocked' => $isBlocked])
                                        </div>
                                        {{-- Linha Inferior do Bloco 2x2 --}}
                                        <div style="display: flex; gap: 0.6rem;">
                                            @include('filament.app.pages.quina.partials.bola-volante', ['numero' => $n3, 'isBlocked' => $isBlocked])
                                            @include('filament.app.pages.quina.partials.bola-volante', ['numero' => $n4, 'isBlocked' => $isBlocked])
                                        </div>
                                    </div>
                                @endfor
                            </div>
                        </div>
                    @endforeach
                </div>

            </div>

            {{-- COLUNA DA DIREITA: CARDS INFORMATIVOS --}}
            <div style="flex: 0 0 360px; max-width: 100%; min-width: 320px; display: flex; flex-direction: column; gap: 1.25rem;">
                
                {{-- CARD 1: PROGRESSO GLOBAL DO BOLÃO --}}
                <div style="background-color: #0d0f12; border: 1px solid #1e2229; border-radius: 0.875rem; padding: 1.25rem;">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem;">
                        <span style="font-size: 0.8rem; font-weight: 700; color: #cbd5e1; text-transform: uppercase; letter-spacing: 0.05em; display: flex; align-items: center; gap: 0.5rem;">
                            <span>📊</span> Progresso do Bolão Quina
                        </span>
                        <span style="font-size: 0.75rem; font-weight: 700; padding: 0.2rem 0.55rem; border-radius: 0.375rem; background: {{ $isLocked ? '#7f1d1d' : '#312e81' }}; color: {{ $isLocked ? '#fca5a5' : '#c7d2fe' }};">
                            {{ $isLocked ? 'Encerrado' : 'Em Andamento' }}
                        </span>
                    </div>

                    {{-- Barra de progresso visual --}}
                    @php
                        $totalMarcadasPool = $stats['total_marcadas_geral'] ?? (count($minhasDezenas) + count($dezenasOutros));
                        $totalMeta = $totalBolao;
                        $pct = ($totalMeta > 0) ? min(100, round(($totalMarcadasPool / $totalMeta) * 100)) : 0;
                    @endphp
                    <div style="margin-bottom: 1.25rem;">
                        <div style="display: flex; justify-content: space-between; font-size: 0.8rem; color: #9ca3af; margin-bottom: 0.4rem;">
                            <span>Pool de Dezenas</span>
                            <span style="font-weight: 700; color: #818cf8;">{{ $totalMarcadasPool }} / {{ $totalMeta }} ({{ $pct }}%)</span>
                        </div>
                        <div style="width: 100%; height: 8px; background: #161b22; border-radius: 9999px; overflow: hidden; border: 1px solid #21262d;">
                            <div style="width: {{ $pct }}%; height: 100%; background: linear-gradient(90deg, #6366f1, #4338ca); border-radius: 9999px; transition: width 0.3s ease;"></div>
                        </div>
                    </div>

                    {{-- Limites por Quadrante --}}
                    <div style="border-top: 1px solid #1e2229; padding-top: 1rem; display: flex; flex-direction: column; gap: 0.65rem;">
                        <span style="font-size: 0.7rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em;">Limites dos Quadrantes</span>
                        
                        <div style="display: flex; align-items: center; justify-content: space-between; font-size: 0.8rem;">
                            <span style="color: #94a3b8;">Q1 (01 – 20)</span>
                            <span style="font-weight: 700; color: {{ $stats['q1'] >= $maxQ1 ? '#818cf8' : '#f1f5f9' }};">
                                {{ $stats['q1'] }} / {{ $maxQ1 }}
                            </span>
                        </div>
                        <div style="display: flex; align-items: center; justify-content: space-between; font-size: 0.8rem;">
                            <span style="color: #94a3b8;">Q2 (21 – 40)</span>
                            <span style="font-weight: 700; color: {{ $stats['q2'] >= $maxQ2 ? '#818cf8' : '#f1f5f9' }};">
                                {{ $stats['q2'] }} / {{ $maxQ2 }}
                            </span>
                        </div>
                        <div style="display: flex; align-items: center; justify-content: space-between; font-size: 0.8rem;">
                            <span style="color: #94a3b8;">Q3 (41 – 60)</span>
                            <span style="font-weight: 700; color: {{ $stats['q3'] >= $maxQ3 ? '#818cf8' : '#f1f5f9' }};">
                                {{ $stats['q3'] }} / {{ $maxQ3 }}
                            </span>
                        </div>
                        <div style="display: flex; align-items: center; justify-content: space-between; font-size: 0.8rem;">
                            <span style="color: #94a3b8;">Q4 (61 – 80)</span>
                            <span style="font-weight: 700; color: {{ $stats['q4'] >= $maxQ4 ? '#818cf8' : '#f1f5f9' }};">
                                {{ $stats['q4'] }} / {{ $maxQ4 }}
                            </span>
                        </div>
                    </div>
                </div>

                {{-- CARD 2: USUÁRIOS E SUAS SELEÇÕES --}}
                <div style="background-color: #0d0f12; border: 1px solid #1e2229; border-radius: 0.875rem; padding: 1.25rem;">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem;">
                        <span style="font-size: 0.8rem; font-weight: 700; color: #cbd5e1; text-transform: uppercase; letter-spacing: 0.05em; display: flex; align-items: center; gap: 0.5rem;">
                            <span>👥</span> Participantes & Cotas
                        </span>
                        <span style="font-size: 0.75rem; color: #64748b;">
                            {{ count($statusUsuarios) }} participantes
                        </span>
                    </div>

                    @if(empty($statusUsuarios))
                        <div style="text-align: center; padding: 1rem; color: #6b7280; font-size: 0.8rem;">
                            Nenhum participante vinculado ainda.
                        </div>
                    @else
                        <div style="display: flex; flex-direction: column; gap: 1rem;">
                            @foreach($statusUsuarios as $u)
                                <div style="
                                    background: {{ $u['is_me'] ? 'rgba(99, 102, 241, 0.08)' : '#11141a' }};
                                    border: 1px solid {{ $u['is_me'] ? 'rgba(99, 102, 241, 0.35)' : '#1e2433' }};
                                    border-radius: 0.65rem;
                                    padding: 0.75rem;
                                    display: flex;
                                    flex-direction: column;
                                    gap: 0.5rem;
                                ">
                                    {{-- Cabeçalho do Usuário --}}
                                    <div style="display: flex; align-items: center; justify-content: space-between;">
                                        <div style="display: flex; align-items: center; gap: 0.4rem;">
                                            <span style="font-size: 0.82rem; font-weight: 700; color: {{ $u['is_me'] ? '#a5b4fc' : '#f1f5f9' }};">
                                                {{ $u['name'] }}
                                            </span>
                                            @if($u['is_me'])
                                                <span style="font-size: 0.65rem; font-weight: 800; background: #312e81; color: #c7d2fe; padding: 0.1rem 0.4rem; border-radius: 0.25rem;">
                                                    VOCÊ
                                                </span>
                                            @endif
                                        </div>
                                        <span style="font-size: 0.72rem; font-weight: 700; color: {{ $u['finalizado'] ? '#818cf8' : '#f59e0b' }};">
                                            {{ count($u['selecionadas']) }} / {{ $u['limite'] }}
                                            {{ $u['finalizado'] ? '✓' : '' }}
                                        </span>
                                    </div>

                                    {{-- Bolinhas das dezenas selecionadas por esse usuário --}}
                                    @if(empty($u['selecionadas']))
                                        <div style="font-size: 0.72rem; color: #64748b; font-style: italic;">
                                            Aguardando seleção...
                                        </div>
                                    @else
                                        <div style="display: flex; gap: 0.35rem; flex-wrap: wrap; align-items: center;">
                                            @foreach(collect($u['selecionadas'])->sort()->values() as $n)
                                                @php
                                                    if ($u['is_me']) {
                                                        $tGrad = 'radial-gradient(circle at 35% 35%, #86efac, #16a34a 55%, #14532d)';
                                                    } else {
                                                        $tGrad = 'radial-gradient(circle at 35% 30%, #f1f5f9 0%, #cbd5e1 32%, #94a3b8 70%, #64748b 100%)';
                                                    }
                                                @endphp
                                                <span 
                                                    style="
                                                        width: 25px;
                                                        height: 25px;
                                                        border-radius: 9999px;
                                                        background: {{ $tGrad }};
                                                        display: inline-flex;
                                                        align-items: center;
                                                        justify-content: center;
                                                        font-weight: 800;
                                                        font-size: 0.7rem;
                                                        color: {{ $u['is_me'] ? '#ffffff' : '#1e293b' }};
                                                        box-shadow: 0 1px 3px rgba(0,0,0,0.5);
                                                        text-shadow: {{ $u['is_me'] ? '0 1px 1px rgba(0,0,0,0.7)' : 'none' }};
                                                    "
                                                    title="Dezena {{ str_pad($n, 2, '0', STR_PAD_LEFT) }}"
                                                >
                                                    {{ str_pad($n, 2, '0', STR_PAD_LEFT) }}
                                                </span>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

            </div>

        </div>

    </div>
@else
    <div style="text-align: center; padding: 3rem; background-color: #09090b; border-radius: 0.75rem; border: 1px solid #1f2937;">
        <p style="color: #9ca3af; font-size: 1.125rem;">Nenhum bolão ativo da Quina encontrado para o seu usuário.</p>
    </div>
@endif
</x-filament-panels::page>