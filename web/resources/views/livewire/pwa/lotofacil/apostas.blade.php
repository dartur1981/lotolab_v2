<div class="space-y-4 py-2 flex flex-col flex-1 h-full">

    <!-- Card de Cabeçalho da Lista -->
    <div class="bg-[#162032] shrink-0 border border-slate-800/80 rounded-2xl p-5 shadow-xl">
        <h2 class="text-xl font-extrabold text-white tracking-tight">
            Apostas Realizadas no Bolão
        </h2>
        <p class="text-xs text-slate-400 mt-1">
            Visualização das dezenas selecionadas por cada participante neste bolão.
        </p>
    </div>

    <!-- Lista de Apostas por Participante -->
    <div class="bg-[#162032] border border-slate-800/80 rounded-2xl p-4 shadow-lg flex-1 flex flex-col">
        <div class="space-y-4 flex-1 overflow-y-auto pr-1">
            @forelse($participantesApostas as $p)
                <div class="border-b border-slate-800/60 pb-4 last:border-0 last:pb-0">
                    <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-full bg-gradient-to-tr from-purple-600 to-emerald-500 flex items-center justify-center font-bold text-xs text-white shadow">
                            {{ strtoupper(substr($p['name'], 0, 1)) }}
                        </div>
                        <div>
                            <span class="text-sm font-bold text-white block leading-tight">
                                {{ $p['name'] }}
                                @if($p['is_me'])
                                    <span class="text-[10px] bg-emerald-950 text-emerald-400 border border-emerald-800/60 px-1.5 py-0.5 rounded ml-1">Você</span>
                                @endif
                            </span>
                            <span class="text-[11px] text-slate-400 font-medium">
                                {{ $p['qtd'] }} dezenas escolhidas
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Chips das Dezenas Escolhidas -->
                @if(count($p['numeros']) > 0)
                    <div class="flex flex-wrap gap-1.5 pt-1">
                        @foreach($p['numeros'] as $num)
                            <span class="w-7 h-7 rounded-full bg-[#202C40] border border-slate-700/60 text-xs font-bold text-emerald-400 flex items-center justify-center shadow-inner">
                                {{ str_pad($num, 2, '0', STR_PAD_LEFT) }}
                            </span>
                        @endforeach
                    </div>
                @else
                    <span class="text-xs italic text-slate-500 block pt-1">Nenhuma dezena selecionada ainda.</span>
                @endif
                </div>
            @empty
                <div class="text-center py-4 flex-1 flex items-center justify-center">
                    <p class="text-xs text-slate-400">Nenhuma aposta encontrada para este bolão.</p>
                </div>
            @endforelse
        </div>
    </div>

</div>
