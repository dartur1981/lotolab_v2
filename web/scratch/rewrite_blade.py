import os

blade_content = r"""<x-filament-panels::page>
@if(!$bolaoAtivo)
    {{-- LISTAGEM DE BOLÕES --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($meusBoloes as $bolao)
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6 flex flex-col justify-between border border-gray-200 dark:border-gray-700">
                <div>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">{{ $bolao->nome }}</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                        Total de Números: <strong>{{ $bolao->total_numeros }}</strong> <br>
                        Participantes: <strong>{{ $bolao->users->count() }}</strong>
                    </p>
                </div>
                <button wire:click="entrarBolao({{ $bolao->id }})" class="w-full bg-primary-600 hover:bg-primary-500 text-white font-bold py-2 px-4 rounded-lg transition">
                    Entrar no Bolão
                </button>
            </div>
        @empty
            <div class="col-span-full text-center py-12 text-gray-500">
                Você ainda não participa de nenhum bolão.
            </div>
        @endforelse
    </div>
@else
    <div wire:poll.2s="refreshBoard" class="space-y-4">
        <div class="flex gap-6 items-start">
            <style>
            @keyframes pulse-pending {
                0%, 100% { opacity: 1; transform: scale(1); }
                50%       { opacity: 0.4; transform: scale(0.75); }
            }
            </style>

            {{-- COLUNA ESQUERDA: bolas --}}
            <div class="shrink-0 space-y-4">

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

                {{-- Aviso de bloqueio (se já selecionou tudo) --}}
                @if (count($selecionados) >= $qtdMaxima)
                    <div class="rounded-xl bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-700 p-3 text-green-700 dark:text-green-300 text-sm font-medium text-center">
                        ✅ Seleção completa.
                    </div>
                @endif

                {{-- Aviso: último participante com números extras --}}
                @if ($isUltimo && $sobra > 0)
                    <div class="rounded-xl bg-amber-50 dark:bg-amber-900/30 border border-amber-300 dark:border-amber-600 p-3 text-amber-800 dark:text-amber-300 text-sm font-medium text-center">
                        ⚠️ Você é o último a apostar. Como sobram <strong>{{ $sobra }}</strong> número(s) não divisíveis, você escolherá <strong>{{ $qtdMaxima }}</strong> números nesta rodada.
                    </div>
                @endif

                {{-- Cabeçalho --}}
                <div class="flex items-center justify-between">
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Selecione <strong>{{ $qtdMaxima }}</strong> números
                        (Moldura: <strong>{{ $stats['moldura'] }}</strong>/{{ $bolaoAtivo->max_moldura }} · Miolo: <strong>{{ $stats['miolo'] }}</strong>/{{ $bolaoAtivo->max_miolo }}).
                    </p>
                    <div class="flex items-center gap-4">
                        <span class="text-sm font-semibold whitespace-nowrap {{ count($selecionados) >= $qtdMaxima ? 'text-green-600' : 'text-gray-500 dark:text-gray-400' }}">
                            {{ count($selecionados) }} / {{ $qtdMaxima }}
                        </span>
                        <button wire:click="voltar" class="text-xs text-primary-500 hover:underline">Sair do Bolão</button>
                    </div>
                </div>

                {{-- Grid de bolas dividido em blocos - Adaptado da Quina para Lotofácil --}}
                <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
                @foreach ([[1,25,'1 – 25']] as [$ini, $fim, $titulo])
                <fieldset class="border border-gray-300 dark:border-gray-600 rounded-xl px-4 pt-4 pb-4">
                    <legend class="px-2 text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-widest">
                        {{ $titulo }} &nbsp;<span class="{{ count($selecionados) === $qtdMaxima ? 'text-green-500' : '' }}">{{ count($selecionados) }}/{{ $qtdMaxima }}</span>
                    </legend>

                    {{-- Sub-grupos de 4 números (2×2) --}}
                    <div class="flex gap-3 flex-wrap justify-center">
                        @foreach (array_chunk(range($ini, $fim), 4) as $sqIdx => $subGrupo)
                            @php
                                $sqIni    = $subGrupo[0];
                                $sqFim    = $subGrupo[count($subGrupo)-1];
                                $selSq    = count(array_filter($selecionados, fn($n) => $n >= $sqIni && $n <= $sqFim));
                                $ocupSq   = count(array_filter($dezenasOutros, fn($n) => $n >= $sqIni && $n <= $sqFim));
                                $totalSq  = $selSq + $ocupSq;
                            @endphp
                        <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-2" style="background: rgba(0,0,0,0.02);">
                            <div class="grid gap-2" style="grid-template-columns: repeat(2, 45px);">
                                @foreach ($subGrupo as $numero)
                                    @php
                                        $desab   = in_array($numero, $dezenasOutros);
                                        $marcado = in_array($numero, $selecionados);
                                        $tempRaw = $tendencias[$numero] ?? 'frio';
                                        $temp    = $tempRaw == 'morna' ? 'morno' : ($tempRaw == 'fria' ? 'frio' : 'quente');
                                        
                                        $lotado   = !$desab && !$marcado && (count($selecionados) >= $qtdMaxima);

                                        $gradient = match(true) {
                                            $desab   => 'radial-gradient(circle at 35% 35%, #d1d5db, #9ca3af 60%, #6b7280)',
                                            $marcado => 'radial-gradient(circle at 35% 35%, #86efac, #16a34a 55%, #14532d)',
                                            default  => match($temp) {
                                                'quente' => 'radial-gradient(circle at 35% 35%, #fca5a5, #dc2626 55%, #7f1d1d)',
                                                'morno'  => 'radial-gradient(circle at 35% 35%, #fde68a, #d97706 55%, #78350f)',
                                                default  => 'radial-gradient(circle at 35% 35%, #93c5fd, #1d4ed8 55%, #1e1b4b)',
                                            },
                                        };

                                        $ringStyle = $marcado ? 'outline: 2px solid #22c55e; outline-offset: 2px;' : '';
                                        $cursor    = ($desab || $lotado) ? 'cursor:not-allowed;' : 'cursor:pointer;';
                                        $opacity   = $lotado ? 'opacity:0.45;' : 'opacity:1;';
                                    @endphp

                                    <button
                                        @if (!$desab && !$lotado)
                                            wire:click="toggleDezena({{ $numero }})"
                                        @endif
                                        style="
                                            width: 45px; height: 45px;
                                            border-radius: 50%;
                                            background: {{ $gradient }};
                                            box-shadow: none;
                                            border: none;
                                            position: relative;
                                            overflow: hidden;
                                            transition: transform 0.12s ease;
                                            {{ $ringStyle }} {{ $cursor }} {{ $opacity }}
                                        "
                                        class="select-none {{ (!$desab && !$lotado) ? 'hover:scale-110 active:scale-95' : '' }} {{ $marcado ? 'scale-110' : '' }}"
                                    >
                                        <span style="position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;line-height:1;gap:1px;">
                                            <span style="font-weight:900;font-size:14px;color:{{ $desab ? '#9ca3af' : 'white' }};text-shadow:0 1px 3px rgba(0,0,0,0.5);letter-spacing:-0.5px;">
                                                {{ str_pad($numero, 2, '0', STR_PAD_LEFT) }}
                                            </span>
                                        </span>
                                    </button>
                                @endforeach
                            </div>
                        </div>
                        @endforeach
                    </div>
                </fieldset>
                @endforeach
                </div>

                {{-- Bolas selecionadas + confirmar --}}
                @if (count($selecionados) > 0)
                    <div class="flex items-center gap-3 flex-wrap border-t pt-4">
                        <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Sua aposta:</span>
                        <div class="flex gap-2 flex-wrap">
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
            <div class="flex-1 min-w-0 space-y-4 hidden md:block">

                {{-- Legenda --}}
                @php
                    $cntQuente = collect($selecionados)->filter(fn($n) => ($tendencias[$n] ?? '') === 'quente')->count();
                    $cntMorno  = collect($selecionados)->filter(fn($n) => ($tendencias[$n] ?? '') === 'morna')->count();
                    $cntFrio   = collect($selecionados)->filter(fn($n) => ($tendencias[$n] ?? '') === 'fria')->count();
                    $cntSel    = count($selecionados);
                @endphp
                <fieldset class="border border-gray-300 dark:border-gray-600 rounded-xl px-4 pt-4 pb-4">
                    <legend class="px-2 text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-widest">Legenda</legend>
                    <div class="flex flex-col gap-3">
                        @foreach ([
                            ['Quente',      'radial-gradient(circle at 35% 35%, #fca5a5, #dc2626 55%, #7f1d1d)', $cntQuente],
                            ['Morno',       'radial-gradient(circle at 35% 35%, #fde68a, #d97706 55%, #78350f)', $cntMorno],
                            ['Frio',        'radial-gradient(circle at 35% 35%, #93c5fd, #1d4ed8 55%, #1e1b4b)', $cntFrio],
                            ['Selecionado', 'radial-gradient(circle at 35% 35%, #86efac, #16a34a 55%, #14532d)', $cntSel],
                            ['Ocupado',     'radial-gradient(circle at 35% 35%, #d1d5db, #9ca3af 60%, #6b7280)', null],
                        ] as [$label, $grad, $cnt])
                            <span class="flex items-center gap-3 text-sm text-gray-600 dark:text-gray-400">
                                <span style="width:20px;height:20px;border-radius:50%;background:{{ $grad }};display:inline-block;flex-shrink:0;"></span>
                                <span class="flex-1">{{ $label }}</span>
                                @if ($cnt !== null && $cnt > 0)
                                    <span class="text-sm font-bold text-gray-400 dark:text-gray-500">({{ $cnt }})</span>
                                @endif
                            </span>
                        @endforeach
                    </div>
                </fieldset>
                
                {{-- Widget de limites Globais do Bolão --}}
                <fieldset class="border border-gray-300 dark:border-gray-600 rounded-xl px-4 pt-4 pb-4 mt-4">
                    <legend class="px-2 text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-widest">Limites Globais</legend>
                    <div class="flex flex-col gap-2">
                        <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                            <span class="font-semibold w-14 dark:text-gray-300">Moldura</span>
                            <span class="font-bold text-gray-700 dark:text-gray-200">{{ $stats['moldura'] }}</span>
                            <span class="text-gray-400 w-12 text-right">/ {{ $bolaoAtivo->max_moldura }}</span>
                        </div>
                        <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                            <span class="font-semibold w-14 dark:text-gray-300">Miolo</span>
                            <span class="font-bold text-gray-700 dark:text-gray-200">{{ $stats['miolo'] }}</span>
                            <span class="text-gray-400 w-12 text-right">/ {{ $bolaoAtivo->max_miolo }}</span>
                        </div>
                    </div>
                </fieldset>

            </div>
        </div>
    </div>
@endif
</x-filament-panels::page>
"""

with open(r"D:\SISTEMAS-PRIVATE\lotolab_app_v2\web\resources\views\filament\app\pages\lotofacil\selecionar-numeros.blade.php", "w", encoding="utf-8") as f:
    f.write(blade_content)

print("Blade file successfully rewritten.")
