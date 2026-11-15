<x-filament-panels::page>
@if($bolaoAtivo)
    <div wire:poll.2s="carregarDezenasOutros" style="display: flex; flex-direction: column; gap: 1.5rem;">
        
        @if(count($meusBoloes) > 1)
        <div style="background-color: var(--fi-bg); border: 1px solid var(--fi-border); padding: 1rem; border-radius: 0.75rem;">
            {{ $this->form }}
        </div>
        @endif

        @php
            $userIds = DB::table('megasena_bolao_user')->where('megasena_bolao_id', $bolaoAtivo->id)->orderBy('id')->pluck('user_id')->toArray();
            $isUltimo = end($userIds) == auth()->id();
            $qtd = count($userIds);
            $totalBolao = $bolaoAtivo->total_numeros;
            $porPessoa = (int) floor($totalBolao / $qtd);
            $sobra = (int) ($totalBolao % $qtd);
            $meuLimite = $isUltimo ? ($porPessoa + $sobra) : $porPessoa;
            $selecionados = $minhasDezenas;

            $maxQ1 = $bolaoAtivo->max_quadrante_1 ?? 2;
            $maxQ2 = $bolaoAtivo->max_quadrante_2 ?? 2;
            $maxQ3 = $bolaoAtivo->max_quadrante_3 ?? 2;

            $quadrantesDef = [
                [
                    'id' => 1,
                    'label' => '1 – 20',
                    'count' => $stats['q1'],
                    'max' => $maxQ1,
                    'sub_range' => range(1, 5),
                ],
                [
                    'id' => 2,
                    'label' => '21 – 40',
                    'count' => $stats['q2'],
                    'max' => $maxQ2,
                    'sub_range' => range(6, 10),
                ],
                [
                    'id' => 3,
                    'label' => '41 – 60',
                    'count' => $stats['q3'],
                    'max' => $maxQ3,
                    'sub_range' => range(11, 15),
                ],
            ];
        @endphp

        {{-- Avisos de Status --}}
        @if ($isLocked)
            <div style="border-radius: 0.75rem; background-color: rgba(220, 38, 38, 0.2); border: 1px solid #b91c1c; padding: 0.75rem; color: #fca5a5; font-size: 0.875rem; font-weight: 500; text-align: center;">
                🔒 Bolão Encerrado. O pool de dezenas está completo e pronto para geração de jogos.
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

        {{-- Barra de Resumo Superior --}}
        <div style="display: flex; align-items: center; justify-content: space-between; background-color: #0d0f12; border: 1px solid #1e2229; padding: 1rem 1.25rem; border-radius: 0.875rem; flex-wrap: wrap; gap: 0.75rem;">
            <div>
                <p style="font-size: 0.875rem; color: #9ca3af; margin: 0;">
                    Bolão: <strong style="color: white;">{{ $bolaoAtivo->nome }}</strong> · Cota: <strong style="color: #34d399;">{{ $meuLimite }} dezenas</strong>
                </p>
                <p style="font-size: 0.75rem; color: #6b7280; margin: 0.25rem 0 0 0;">
                    Distribuição ideal: até 2 dezenas por quadrante e no máximo 1 por sub-quadrante.
                </p>
            </div>
            <div style="display: flex; align-items: center; gap: 1rem;">
                <div style="font-size: 0.875rem; color: #d1d5db; display: flex; gap: 0.75rem;">
                    <span>P/I: <strong>{{ $stats['pares'] }}P / {{ $stats['impares'] }}I</strong></span>
                    <span>Soma: <strong>{{ $stats['soma'] }}</strong></span>
                </div>
                <span style="font-size: 1.125rem; font-weight: 800; color: {{ count($selecionados) >= $meuLimite ? '#10b981' : '#34d399' }}; background: #161b22; padding: 0.25rem 0.75rem; border-radius: 0.5rem; border: 1px solid #21262d;">
                    {{ count($selecionados) }} / {{ $meuLimite }}
                </span>
            </div>
        </div>

        {{-- CONTAINER DOS 3 QUADRANTES CONFORME A IMAGEM --}}
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
                        <span style="color: {{ $quadrante['count'] > $quadrante['max'] ? '#f87171' : ($quadrante['count'] == $quadrante['max'] ? '#34d399' : '#64748b') }}; font-weight: 700;">
                            {{ $quadrante['count'] }}/{{ $quadrante['max'] }}
                        </span>
                    </div>

                    {{-- 5 Sub-Quadrantes (Blocos 2x2: 2 dezenas na linha de cima, 2 na linha de baixo) --}}
                    <div style="
                        display: grid;
                        grid-template-columns: repeat(5, minmax(0, 1fr));
                        gap: 0.85rem;
                        width: 100%;
                    ">
                        @for($col = 1; $col <= 5; $col++)
                            @php
                                $subGlobalIdx = ($quadrante['id'] - 1) * 5 + $col;
                                $qIdx = $quadrante['id'] - 1; // 0, 1, 2
                                $blocoCol = $col - 1; // 0, 1, 2, 3, 4
                                $linhaBase = $qIdx * 20;
                                $colStart = $blocoCol * 2 + 1;

                                $n1 = $linhaBase + $colStart;
                                $n2 = $linhaBase + $colStart + 1;
                                $n3 = $linhaBase + $colStart + 10;
                                $n4 = $linhaBase + $colStart + 11;

                                $subCount = $stats['subs'][$subGlobalIdx] ?? 0;
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
                                    @include('filament.app.pages.megasena.partials.bola-volante', ['numero' => $n1])
                                    @include('filament.app.pages.megasena.partials.bola-volante', ['numero' => $n2])
                                </div>
                                {{-- Linha Inferior do Bloco 2x2 --}}
                                <div style="display: flex; gap: 0.6rem;">
                                    @include('filament.app.pages.megasena.partials.bola-volante', ['numero' => $n3])
                                    @include('filament.app.pages.megasena.partials.bola-volante', ['numero' => $n4])
                                </div>
                            </div>
                        @endfor
                    </div>
                </div>
            @endforeach
        </div>

    </div>
@else
    <div style="text-align: center; padding: 3rem; background-color: #09090b; border-radius: 0.75rem; border: 1px solid #1f2937;">
        <p style="color: #9ca3af; font-size: 1.125rem;">Nenhum bolão ativo da Mega-Sena encontrado para o seu usuário.</p>
    </div>
@endif
</x-filament-panels::page>
