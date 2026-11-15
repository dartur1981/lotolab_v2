<div class="space-y-4 py-2">

    @if(!$bolaoAtivo)
        <div class="bg-[#162032] border border-slate-800 rounded-2xl p-8 text-center my-6">
            <span class="text-4xl block mb-3">🎫</span>
            <h2 class="text-lg font-bold text-white">Nenhum Bolão Ativo</h2>
            <p class="text-xs text-slate-400 mt-1">Você não possui bolões da Mega-Sena aguardando seleção.</p>
        </div>
    @else
        <!-- Card de Status do Bolão -->
        <div class="bg-gradient-to-br from-[#162032] to-[#1e2d44] border border-slate-700/80 rounded-2xl p-5 shadow-xl relative overflow-hidden">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold uppercase tracking-wider text-emerald-400">Mega-Sena</span>
                <span class="text-[10px] font-black uppercase px-2.5 py-0.5 rounded-full {{ $isEncerrado ? 'bg-amber-500/20 text-amber-300 border border-amber-500/40' : 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/40' }}">
                    {{ $isEncerrado ? 'Fechado' : 'Em Seleção' }}
                </span>
            </div>

            <h2 class="text-xl font-black text-white truncate">
                {{ $bolaoAtivo['nome'] }}
            </h2>
            <p class="text-xs text-slate-400 mt-0.5">
                Concurso Alvo: <strong class="text-white">{{ $bolaoAtivo['concurso_alvo'] ?? 'A Definir' }}</strong>
            </p>

            <!-- Barra de Progresso do Pool Geral -->
            <div class="mt-4 pt-3 border-t border-slate-700/50">
                <div class="flex justify-between items-center text-xs mb-1.5">
                    <span class="text-slate-300 font-medium">Progresso do Pool</span>
                    <span class="font-bold text-emerald-400">{{ count($poolDezenas) }} de {{ $totalNumeros }} dezenas ({{ $progressoPercent }}%)</span>
                </div>
                <div class="w-full bg-slate-900 rounded-full h-2.5 overflow-hidden">
                    <div class="bg-gradient-to-r from-emerald-500 to-emerald-400 h-2.5 rounded-full transition-all duration-500" style="width: {{ $progressoPercent }}%"></div>
                </div>
            </div>
        </div>

        <!-- Card de Ação Rápida: Minha Cota -->
        <div class="bg-[#162032] border border-slate-800 rounded-2xl p-5 shadow-lg">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Sua Cota Pessoal</span>
                @if($userHasBet)
                    <span class="text-[10px] font-bold text-emerald-400 bg-emerald-950/60 border border-emerald-800/80 px-2 py-0.5 rounded-md flex items-center gap-1">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        Completa
                    </span>
                @else
                    <span class="text-[10px] font-bold text-amber-400 bg-amber-950/60 border border-amber-800/80 px-2 py-0.5 rounded-md">
                        Pendente
                    </span>
                @endif
            </div>

            <div class="flex items-baseline justify-between mt-1">
                <div>
                    <span class="text-3xl font-black text-white">{{ $minhasMarcadasCount }}</span>
                    <span class="text-sm font-semibold text-slate-400">/ {{ $limitePessoal }} dezenas</span>
                </div>
                <span class="text-xs text-slate-400">
                    {{ $apostaramCount }} de {{ $qtdParticipantes }} participantes marcaram
                </span>
            </div>

            @if(!empty($minhasDezenas))
                <div class="mt-3 pt-3 border-t border-slate-800">
                    <span class="text-[11px] text-slate-400 block mb-1.5 font-medium">Suas dezenas escolhidas:</span>
                    <div class="flex flex-wrap gap-1.5">
                        @foreach($minhasDezenas as $d)
                            <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-emerald-600 text-white font-bold text-xs shadow-sm shadow-emerald-900 border border-emerald-400/60">
                                {{ str_pad($d, 2, '0', STR_PAD_LEFT) }}
                            </span>
                        @endforeach
                    </div>
                </div>
            @endif

            <a 
                href="/pwa/megasena/selecao" 
                class="mt-4 w-full bg-emerald-600 hover:bg-emerald-500 active:scale-95 text-white font-bold py-3 px-4 rounded-xl shadow-lg shadow-emerald-900/30 transition flex items-center justify-center space-x-2 text-sm"
            >
                <span>{{ $userHasBet ? 'Alterar Minhas Dezenas' : 'Abrir Volante e Escolher Dezenas' }}</span>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path></svg>
            </a>
        </div>

        <!-- Card de Resumo Estatístico do Bolão -->
        <div class="bg-[#162032] border border-slate-800 rounded-2xl p-4 shadow-lg">
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-3">Informações do Bolão</h3>
            <div class="grid grid-cols-2 gap-2 text-xs">
                <div class="p-2.5 rounded-xl bg-[#243044]/60 border border-slate-700/50">
                    <span class="text-slate-400 block text-[10px]">Cota Individual</span>
                    <strong class="text-white text-sm font-bold">R$ {{ number_format($bolaoAtivo['valor_cota'] ?? 0, 2, ',', '.') }}</strong>
                </div>
                <div class="p-2.5 rounded-xl bg-[#243044]/60 border border-slate-700/50">
                    <span class="text-slate-400 block text-[10px]">Valor Total</span>
                    <strong class="text-white text-sm font-bold">R$ {{ number_format($bolaoAtivo['valor_total'] ?? 0, 2, ',', '.') }}</strong>
                </div>
                <div class="p-2.5 rounded-xl bg-[#243044]/60 border border-slate-700/50">
                    <span class="text-slate-400 block text-[10px]">Limite Q1 (01-20)</span>
                    <strong class="text-white text-sm font-bold">{{ $bolaoAtivo['max_quadrante_1'] ?? 'Auto' }}</strong>
                </div>
                <div class="p-2.5 rounded-xl bg-[#243044]/60 border border-slate-700/50">
                    <span class="text-slate-400 block text-[10px]">Limite Q2 (21-40)</span>
                    <strong class="text-white text-sm font-bold">{{ $bolaoAtivo['max_quadrante_2'] ?? 'Auto' }}</strong>
                </div>
            </div>
        </div>
    @endif

</div>
