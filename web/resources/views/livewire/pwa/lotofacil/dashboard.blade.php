<div class="space-y-4 py-2">

    <!-- Card Principal do Bolão -->
    <div class="bg-[#162032] border border-slate-800/80 rounded-2xl p-5 shadow-xl">
        <h2 class="text-2xl font-black text-white tracking-tight">
            {{ $bolaoAtivo['nome'] ?? 'C7051_27-06-2026' }}
        </h2>
        
        <div class="mt-1.5 mb-4">
            @if($isEncerrado)
                <span class="inline-flex items-center px-3 py-1 rounded-md text-xs font-bold bg-purple-950 text-purple-300 border border-purple-800/50">
                    Seleções Concluídas
                </span>
            @else
                <span class="inline-flex items-center px-3 py-1 rounded-md text-xs font-bold bg-emerald-950 text-emerald-300 border border-emerald-800/50">
                    Em Aberto
                </span>
            @endif
        </div>

        <!-- 3 Estatísticas em Colunas -->
        <div class="grid grid-cols-3 gap-3">
            <div class="bg-[#202C40] rounded-xl p-3 text-center border border-slate-800/50">
                <span class="block text-xl font-extrabold text-white">{{ $totalNumeros }}</span>
                <span class="text-[11px] text-slate-400 font-medium leading-tight block mt-0.5">Dezenas do Bolão</span>
            </div>
            <div class="bg-[#202C40] rounded-xl p-3 text-center border border-slate-800/50">
                <span class="block text-xl font-extrabold text-white">{{ $qtdParticipantes }}</span>
                <span class="text-[11px] text-slate-400 font-medium leading-tight block mt-0.5">Participantes</span>
            </div>
            <div class="bg-[#202C40] rounded-xl p-3 text-center border border-slate-800/50">
                <span class="block text-xl font-extrabold text-white">{{ $porPessoa }}</span>
                <span class="text-[11px] text-slate-400 font-medium leading-tight block mt-0.5">Por pessoa</span>
            </div>
        </div>
    </div>

    @if(!$userHasBet && !$isEncerrado)
    <!-- Call to Action: Apostar -->
    <div class="bg-[#125B30] border border-[#1C733F] rounded-2xl p-5 shadow-xl">
        <h3 class="text-lg font-extrabold text-white tracking-tight mb-1">Você ainda não apostou</h3>
        <p class="text-sm text-emerald-50/90 mb-4 font-medium">Faça sua seleção de números agora mesmo.</p>
        <a href="/pwa/lotofacil/selecao" class="block w-full py-3 px-4 text-center bg-[#00C853] hover:bg-[#00E676] active:bg-[#00A847] text-white font-bold rounded-xl transition shadow-lg shadow-[#00C853]/20 active:scale-[0.98]">
            Fazer minha seleção
        </a>
    </div>
    @endif

    <!-- Progresso de Seleções -->
    <div class="bg-[#162032] border border-slate-800/80 rounded-2xl p-5 shadow-xl">
        <h3 class="text-lg font-bold text-white mb-2">
            Progresso de Seleções
        </h3>

        <div class="flex justify-between items-center text-xs font-semibold text-slate-300 mb-2">
            <span>{{ $apostaramCount }} de {{ $qtdParticipantes }} participantes completaram</span>
            <span class="text-white font-bold">{{ $progressoPercent }}%</span>
        </div>

        <div class="w-full h-3 bg-slate-950 rounded-full overflow-hidden border border-slate-800/60 p-0.5">
            <div 
                class="h-full bg-gradient-to-r from-purple-600 to-purple-400 rounded-full transition-all duration-500" 
                style="width: {{ $progressoPercent }}%"
            ></div>
        </div>
    </div>

    <!-- Card Status do Bolão -->
    <div class="bg-[#162032] border border-slate-800/80 rounded-2xl p-5 shadow-xl">
        <h3 class="text-base font-bold text-white mb-1">
            {{ $isEncerrado ? 'Todas as cotas foram preenchidas' : 'Aguardando seleção de dezenas' }}
        </h3>
        <p class="text-xs text-slate-400 leading-relaxed">
            {{ $isEncerrado ? 'Todas as dezenas foram distribuídas entre os participantes. Aguardando a realização do sorteio oficial.' : 'Algumas dezenas ainda estão disponíveis. Acesse a guia Seleção para escolher seus números.' }}
        </p>
    </div>

    <!-- Resumo do Resultado (Premiações da Lotofácil: 15, 14, 13, 12 e 11 acertos) -->
    <div class="bg-[#162032] border border-slate-800/80 rounded-2xl p-5 shadow-xl">
        <div class="flex justify-between items-center mb-3">
            <h3 class="text-lg font-bold text-white">
                Resumo do Resultado
            </h3>
            <a href="/pwa/lotofacil/resultado" class="text-xs font-semibold text-sky-400 hover:text-sky-300 transition">
                Ver todos →
            </a>
        </div>

        <!-- Grade de Premiações da Lotofácil -->
        <div class="grid grid-cols-2 gap-3">
            <div class="bg-[#202C40] rounded-xl p-3.5 text-center border border-slate-800/50">
                <span class="block text-2xl font-black text-emerald-400">{{ $resumoResultados['acertos_15'] }}</span>
                <span class="text-[11px] font-bold text-slate-300 mt-1 flex items-center justify-center gap-1">
                    🏆 15 Acertos
                </span>
            </div>

            <div class="bg-[#202C40] rounded-xl p-3.5 text-center border border-slate-800/50">
                <span class="block text-2xl font-black text-purple-400">{{ $resumoResultados['acertos_14'] }}</span>
                <span class="text-[11px] font-bold text-slate-300 mt-1 flex items-center justify-center gap-1">
                    ⭐ 14 Acertos
                </span>
            </div>

            <div class="bg-[#202C40] rounded-xl p-3.5 text-center border border-slate-800/50">
                <span class="block text-2xl font-black text-amber-400">{{ $resumoResultados['acertos_13'] }}</span>
                <span class="text-[11px] font-bold text-slate-300 mt-1 flex items-center justify-center gap-1">
                    🏅 13 Acertos
                </span>
            </div>

            <div class="bg-[#202C40] rounded-xl p-3.5 text-center border border-slate-800/50">
                <span class="block text-2xl font-black text-sky-400">{{ $resumoResultados['acertos_12'] }}</span>
                <span class="text-[11px] font-bold text-slate-300 mt-1 flex items-center justify-center gap-1">
                    🎯 12 Acertos
                </span>
            </div>
        </div>

        <div class="mt-3 bg-[#202C40] rounded-xl p-3 text-center border border-slate-800/50 flex items-center justify-between px-4">
            <span class="text-xs font-bold text-slate-300 flex items-center gap-1">
                🎰 11 Acertos
            </span>
            <span class="text-lg font-black text-slate-400">{{ $resumoResultados['acertos_11'] }}</span>
        </div>
    </div>

</div>
