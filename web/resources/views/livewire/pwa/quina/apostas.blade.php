<div class="space-y-4 py-2">

    <div class="px-2">
        <h2 class="text-xl font-black text-white">Apostas do Bolão</h2>
        <p class="text-xs text-slate-400 mt-0.5">Veja as seleções de cada participante e as cartelas geradas</p>
    </div>

    <!-- Lista de Participantes e Suas Dezenas -->
    <div class="space-y-3">
        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 px-2">Dezenas dos Participantes</h3>
        
        @forelse($participantesApostas as $p)
            <div class="bg-[#162032] border {{ $p['is_me'] ? 'border-indigo-500/60 ring-1 ring-indigo-500/30' : 'border-slate-800' }} rounded-2xl p-4 shadow-lg">
                <div class="flex items-center justify-between mb-2">
                    <div class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full {{ $p['is_me'] ? 'bg-indigo-400' : 'bg-slate-500' }}"></span>
                        <strong class="text-sm font-bold text-white">{{ $p['name'] }}</strong>
                        @if($p['is_me'])
                            <span class="text-[10px] font-bold text-indigo-400 bg-indigo-950/60 border border-indigo-800/80 px-1.5 py-0.2 rounded">Você</span>
                        @endif
                    </div>
                    <span class="text-xs text-slate-400 font-semibold">{{ $p['qtd'] }} dezenas</span>
                </div>

                @if(!empty($p['numeros']))
                    <div class="flex flex-wrap gap-1.5 mt-2">
                        @foreach($p['numeros'] as $n)
                            <span class="inline-flex items-center justify-center w-8 h-8 rounded-full {{ $p['is_me'] ? 'bg-indigo-600 text-white font-bold' : 'bg-slate-700 text-slate-200 font-semibold' }} text-xs shadow-sm">
                                {{ str_pad($n, 2, '0', STR_PAD_LEFT) }}
                            </span>
                        @endforeach
                    </div>
                @else
                    <p class="text-xs text-slate-500 italic mt-1">Ainda não escolheu suas dezenas</p>
                @endif
            </div>
        @empty
            <div class="bg-[#162032] border border-slate-800 rounded-2xl p-6 text-center text-slate-400 text-xs">
                Nenhum participante encontrado.
            </div>
        @endforelse
    </div>

    <!-- Jogos de 5 dezenas gerados pelo Fechamento (Duques + Ternos) -->
    @if(!empty($cartelasGeradas))
        <div class="space-y-3 pt-3">
            <h3 class="text-xs font-bold uppercase tracking-wider text-indigo-400 px-2">Cartelas Geradas (Fechamento)</h3>
            
            <div class="space-y-2">
                @foreach($cartelasGeradas as $idx => $cartela)
                    <div class="bg-[#162032] border border-slate-800 rounded-xl p-3 flex items-center justify-between shadow-md">
                        <div class="flex flex-col">
                            <span class="text-xs font-mono font-bold text-slate-400">#{{ str_pad($idx + 1, 2, '0', STR_PAD_LEFT) }}</span>
                            @if(!empty($cartela['grupos']))
                                <span class="text-[10px] text-indigo-400 font-bold">{{ $cartela['grupos'] }}</span>
                            @endif
                        </div>
                        <div class="flex gap-1.5">
                            @foreach($cartela['dezenas'] as $cd)
                                <span class="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-indigo-950 text-indigo-300 font-bold text-xs border border-indigo-800">
                                    {{ str_pad($cd, 2, '0', STR_PAD_LEFT) }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

</div>
