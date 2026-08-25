<x-filament-panels::page>
@if($bolaoAtivo)
    <div wire:poll.2s="refreshBoard" style="display: flex; flex-direction: column; gap: 1.5rem;">
        
        @if(count($meusBoloes) > 1)
        <div style="background-color: var(--fi-bg); border: 1px solid var(--fi-border); padding: 1rem; border-radius: 0.75rem; box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);">
            {{ $this->form }}
        </div>
        @endif

        <div style="display: flex; gap: 1.5rem; align-items: flex-start; flex-wrap: wrap;">
            
            {{-- COLUNA ESQUERDA: bolas --}}
            <div style="flex-shrink: 0; display: flex; flex-direction: column; gap: 1rem; width: 100%; max-width: 100%; flex: 1;">

                @php
                    $usersIds = DB::table('lotofacil_bolao_user')->where('lotofacil_bolao_id', $bolaoAtivo->id)->orderBy('id')->pluck('user_id')->toArray();
                    $isUltimo = end($usersIds) == auth()->id();
                    $qtd = count($usersIds);
                    $totalBolao = $bolaoAtivo->total_numeros;
                    $porPessoa = floor($totalBolao / $qtd);
                    $sobra = $totalBolao % $qtd;
                    $meuLimite = $isUltimo ? ($porPessoa + $sobra) : $porPessoa;
                    $selecionadasCount = count($minhasDezenas);
                    $selecionados = $minhasDezenas;
                    $qtdMaxima = $meuLimite;
                @endphp

                {{-- Aviso de bloqueio --}}
                @if ($isLocked)
                    <div style="border-radius: 0.75rem; background-color: rgba(220, 38, 38, 0.3); border: 1px solid #b91c1c; padding: 0.75rem; color: #fca5a5; font-size: 0.875rem; font-weight: 500; text-align: center;">
                        🔒 Bolão Encerrado. Não é possível alterar dezenas.
                    </div>
                @elseif (count($selecionados) >= $qtdMaxima)
                    <div style="border-radius: 0.75rem; background-color: rgba(6, 78, 59, 0.3); border: 1px solid #047857; padding: 0.75rem; color: #6ee7b7; font-size: 0.875rem; font-weight: 500; text-align: center;">
                        ✅ Seleção completa.
                    </div>
                @endif

                {{-- Aviso: participante com números extras --}}
                @if ($isUltimo && $sobra > 0)
                    <div style="border-radius: 0.75rem; background-color: rgba(120, 53, 15, 0.3); border: 1px solid #d97706; padding: 0.75rem; color: #fcd34d; font-size: 0.875rem; font-weight: 500; text-align: center;">
                        ⚠️ A sua cota absorveu os números extras (sobra da divisão do bolão). Por isso, você pode escolher <strong>{{ $qtdMaxima }}</strong> números no total.
                    </div>
                @endif

                {{-- Cabeçalho --}}
                <div style="display: flex; align-items: center; justify-content: space-between; background-color: #09090b; border: 1px solid #1f2937; padding: 1rem; border-radius: 0.75rem;">
                    <p style="font-size: 0.875rem; color: #9ca3af; margin: 0;">
                        Selecione <strong style="color: white;">{{ $qtdMaxima }}</strong> números
                        (Moldura: <strong style="color: {{ $stats['moldura'] >= $bolaoAtivo->max_moldura ? '#ef4444' : '#d1d5db' }}">{{ $stats['moldura'] }}</strong>/{{ $bolaoAtivo->max_moldura }} · 
                        Miolo: <strong style="color: {{ $stats['miolo'] >= $bolaoAtivo->max_miolo ? '#ef4444' : '#d1d5db' }}">{{ $stats['miolo'] }}</strong>/{{ $bolaoAtivo->max_miolo }}).
                    </p>
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <span style="font-size: 0.875rem; font-weight: 600; white-space: nowrap; color: {{ count($selecionados) >= $qtdMaxima ? '#10b981' : '#9ca3af' }};">
                            {{ count($selecionados) }} / {{ $qtdMaxima }}
                        </span>
                    </div>
                </div>

                {{-- Sugestão de Dezena Casada --}}
                @if ($sugestaoAtual)
                    <div style="background: linear-gradient(135deg, #1e1b4b, #312e81); border: 1px solid #4338ca; border-radius: 0.75rem; padding: 1rem; display: flex; align-items: center; justify-content: space-between; box-shadow: 0 4px 12px rgba(49, 46, 129, 0.5); margin-bottom: 0.5rem;" class="animate-pulse">
                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                            <span style="font-size: 1.5rem;">💡</span>
                            <div>
                                <p style="margin: 0; font-size: 0.75rem; color: #a5b4fc; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em;">Dica de Ouro</p>
                                <p style="margin: 0; font-size: 0.875rem; color: white;">A dezena <strong style="color: #818cf8; font-size: 1.1rem;">{{ str_pad($sugestaoAtual['sugerida'], 2, '0', STR_PAD_LEFT) }}</strong> costuma ser sorteada junto com a {{ str_pad($sugestaoAtual['marcada'], 2, '0', STR_PAD_LEFT) }}.</p>
                            </div>
                        </div>
                        <button wire:click="aceitarSugestao" style="background-color: #4f46e5; color: white; border: none; padding: 0.5rem 1rem; border-radius: 0.5rem; font-weight: 600; font-size: 0.875rem; cursor: pointer; box-shadow: 0 2px 4px rgba(0,0,0,0.2);">
                            + Adicionar {{ str_pad($sugestaoAtual['sugerida'], 2, '0', STR_PAD_LEFT) }}
                        </button>
                    </div>
                @endif

                {{-- VOLANTE 5x5 COM MOLDURA E MIOLO PERFEITAMENTE ALINHADOS --}}
                <fieldset style="border: 1px solid #4b5563; border-radius: 1rem; padding: 2rem; width: max-content; margin: 0 auto; position: relative; background-color: #050505;">
                    <legend style="padding: 0 0.75rem; font-size: 0.875rem; font-weight: 700; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.1em; text-align: center;">Moldura</legend>

                    <div style="position: relative;">
                        <!-- FIELDSET DO MIOLO -->
                        <fieldset style="position: absolute; border: 1px solid #6b7280; border-radius: 0.75rem; pointer-events: none; display: flex; justify-content: center; top: 54px; bottom: 54px; left: 66px; right: 66px; z-index: 0; margin: 0; padding: 0;">
                            <legend style="padding: 0 0.5rem; font-size: 0.625rem; font-weight: 700; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.1em; text-align: center; margin-top: -6px;">Miolo</legend>
                        </fieldset>

                        <!-- GRID 5x5 DAS 25 BOLAS -->
                        <div style="display: grid; grid-template-columns: repeat(5, 50px); gap: 32px; position: relative; z-index: 10;">
                            @for($n=1; $n<=25; $n++)
                                @php
                                    $desab   = in_array($n, $dezenasOutros);
                                    $marcado = in_array($n, $minhasDezenas);
                                    $tempRaw = strtolower($tendencias[$n] ?? 'frio');
                                    $temp = match($tempRaw) {
                                        'quente' => 'quente',
                                        'morna', 'morno' => 'morno',
                                        default => 'frio',
                                    };

                                    $lotado = !$desab && !$marcado && (count($minhasDezenas) >= $meuLimite);

                                    $gradient = match(true) {
                                        $isLocked => 'radial-gradient(circle at 35% 35%, #d1d5db, #9ca3af 60%, #6b7280)',
                                        $desab   => 'radial-gradient(circle at 35% 35%, #d1d5db, #9ca3af 60%, #6b7280)',
                                        $marcado => 'radial-gradient(circle at 35% 35%, #86efac, #16a34a 55%, #14532d)',
                                        default  => match($temp) {
                                            'quente' => 'radial-gradient(circle at 35% 35%, #fca5a5, #dc2626 55%, #7f1d1d)',
                                            'morno'  => 'radial-gradient(circle at 35% 35%, #fde68a, #d97706 55%, #78350f)',
                                            default  => 'radial-gradient(circle at 35% 35%, #93c5fd, #1d4ed8 55%, #1e1b4b)',
                                        },
                                    };

                                    $ringStyle = $marcado ? ($isLocked ? 'outline: 2px solid #9ca3af; outline-offset: 2px;' : 'outline: 2px solid #22c55e; outline-offset: 2px;') : '';
                                    $isDisabled = $desab || $lotado || $isLocked;
                                    $cursor    = $isDisabled ? 'cursor:not-allowed;' : 'cursor:pointer;';
                                    $opacity   = ($lotado && !$isLocked) ? 'opacity:0.45;' : 'opacity:1;';
                                @endphp
                                <button
                                    @if (!$isDisabled)
                                        wire:click="toggleDezena({{ $n }})"
                                    @endif
                                    style="
                                        width: 50px; height: 50px;
                                        border-radius: 50%;
                                        background: {{ $gradient }};
                                        box-shadow: none;
                                        border: none;
                                        position: relative;
                                        overflow: hidden;
                                        transition: transform 0.12s ease;
                                        display: flex;
                                        align-items: center;
                                        justify-content: center;
                                        {{ $ringStyle }} {{ $cursor }} {{ $opacity }}
                                    "
                                >
                                    <span style="font-weight:900;font-size:16px;color:{{ $desab ? '#9ca3af' : 'white' }};text-shadow:0 1px 3px rgba(0,0,0,0.5);letter-spacing:-0.5px;">
                                        {{ str_pad($n, 2, '0', STR_PAD_LEFT) }}
                                    </span>
                                </button>
                            @endfor
                        </div>
                    </div>
                </fieldset>
                
                {{-- Bolas selecionadas + confirmar --}}
                @if (count($selecionados) > 0)
                    <div style="display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap; border-top: 1px solid #1f2937; padding-top: 1rem; margin-top: 1rem;">
                        <span style="font-size: 0.75rem; font-weight: 600; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.05em;">Sua aposta:</span>
                        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                            @foreach (collect($selecionados)->sort()->values() as $n)
                                <span style="width:40px;height:40px;border-radius:50%;background:radial-gradient(circle at 35% 35%, #86efac, #16a34a 55%, #14532d);display:inline-flex;align-items:center;justify-content:center;font-weight:900;font-size:14px;color:white;text-shadow:0 1px 3px rgba(0,0,0,0.4);">
                                    {{ str_pad($n, 2, '0', STR_PAD_LEFT) }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                @endif

            </div>

            {{-- COLUNA DIREITA: legenda + widgets --}}
            <div style="display: flex; flex-direction: column; gap: 1rem; min-width: 250px;">

                {{-- Legenda --}}
                @php
                    $cntQuente = collect($selecionados)->filter(fn($n) => ($tendencias[$n] ?? '') === 'quente')->count();
                    $cntMorno  = collect($selecionados)->filter(fn($n) => ($tendencias[$n] ?? '') === 'morna')->count();
                    $cntFrio   = collect($selecionados)->filter(fn($n) => ($tendencias[$n] ?? '') === 'fria')->count();
                    $cntSel    = count($selecionados);
                @endphp
                <fieldset style="border: 1px solid #4b5563; border-radius: 0.75rem; padding: 1rem;">
                    <legend style="padding: 0 0.5rem; font-size: 0.75rem; font-weight: 600; color: #9ca3af;">Legenda das Bolas</legend>
                    <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                        @foreach ([
                            ['Quente',      'radial-gradient(circle at 35% 35%, #fca5a5, #dc2626 55%, #7f1d1d)'],
                            ['Morno',       'radial-gradient(circle at 35% 35%, #fde68a, #d97706 55%, #78350f)'],
                            ['Frio',        'radial-gradient(circle at 35% 35%, #93c5fd, #1d4ed8 55%, #1e1b4b)'],
                            ['Você',        'radial-gradient(circle at 35% 35%, #86efac, #16a34a 55%, #14532d)'],
                            ['Outros',      'radial-gradient(circle at 35% 35%, #d1d5db, #9ca3af 60%, #6b7280)'],
                        ] as [$label, $grad])
                            <span style="display: flex; align-items: center; gap: 0.75rem; font-size: 0.875rem; color: #9ca3af;">
                                <span style="width:20px;height:20px;border-radius:50%;background:{{ $grad }};display:inline-block;flex-shrink:0;"></span>
                                <span style="flex: 1;">{{ $label }}</span>
                            </span>
                        @endforeach
                    </div>
                </fieldset>
                
                {{-- Widget de limites Globais do Bolão --}}
                <fieldset style="border: 1px solid #4b5563; border-radius: 0.75rem; padding: 1rem;">
                    <legend style="padding: 0 0.5rem; font-size: 0.75rem; font-weight: 600; color: #9ca3af;">Limites Globais</legend>
                    <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                        <div style="display: flex; align-items: center; justify-content: space-between; font-size: 0.75rem; color: #9ca3af;">
                            <span style="font-weight: 600; width: 4rem; color: #d1d5db;">Moldura</span>
                            <span style="font-weight: 700; color: #e5e7eb;">{{ $stats['moldura'] }}</span>
                            <span style="color: #9ca3af; width: 3rem; text-align: right;">/ {{ $bolaoAtivo->max_moldura }}</span>
                        </div>
                        <div style="display: flex; align-items: center; justify-content: space-between; font-size: 0.75rem; color: #9ca3af;">
                            <span style="font-weight: 600; width: 4rem; color: #d1d5db;">Miolo</span>
                            <span style="font-weight: 700; color: #e5e7eb;">{{ $stats['miolo'] }}</span>
                            <span style="color: #9ca3af; width: 3rem; text-align: right;">/ {{ $bolaoAtivo->max_miolo }}</span>
                        </div>
                    </div>
                </fieldset>

                {{-- Usuários que já selecionaram --}}
                @php
                    $finalizados = collect($statusUsuarios)->filter(fn($u) => $u['finalizado']);
                    $pendentes   = collect($statusUsuarios)->filter(fn($u) => !$u['finalizado']);
                @endphp
                
                @if($finalizados->isNotEmpty())
                <fieldset style="border: 1px solid #4b5563; border-radius: 0.75rem; padding: 1rem;">
                    <legend style="padding: 0 0.5rem; font-size: 0.75rem; font-weight: 600; color: #10b981;">Apostas Registradas</legend>
                    <div style="display: flex; flex-direction: column; gap: 1rem;">
                        @foreach($finalizados as $u)
                            <div>
                                <div style="font-size: 0.75rem; font-weight: 600; color: #d1d5db; margin-bottom: 0.25rem;">
                                    {{ $u['name'] }}
                                </div>
                                <div style="display: flex; gap: 0.25rem; flex-wrap: wrap;">
                                    @foreach(collect($u['selecionadas'])->sort()->values() as $n)
                                        <span style="width:24px;height:24px;border-radius:50%;background:radial-gradient(circle at 35% 35%, #86efac, #16a34a 55%, #14532d);display:inline-flex;align-items:center;justify-content:center;font-weight:800;font-size:10px;color:white;">
                                            {{ str_pad($n, 2, '0', STR_PAD_LEFT) }}
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </fieldset>
                @endif

                {{-- Usuários pendentes --}}
                @if($pendentes->isNotEmpty())
                <fieldset style="border: 1px solid #4b5563; border-radius: 0.75rem; padding: 1rem;">
                    <legend style="padding: 0 0.5rem; font-size: 0.75rem; font-weight: 600; color: #f59e0b;">Aguardando Seleção</legend>
                    <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                        @foreach($pendentes as $u)
                            <div style="display: flex; align-items: center; justify-content: space-between; font-size: 0.75rem; color: #9ca3af;">
                                <span style="font-weight: 600; color: #d1d5db;">{{ $u['name'] }}</span>
                                <span style="color: #6b7280;">
                                    {{ count($u['selecionadas']) }} / {{ $u['limite'] }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </fieldset>
                @endif

            </div>
        </div>
    </div>
@endif
</x-filament-panels::page>
