<div class="space-y-4 py-2">

    <!-- Card de Cabeçalho dos Resultados -->
    <div class="bg-[#162032] border border-slate-800/80 rounded-2xl p-5 shadow-xl">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-extrabold text-white tracking-tight">
                Estatísticas & Resultados
            </h2>
            <span class="text-[11px] font-semibold px-2 py-0.5 rounded bg-slate-800 text-slate-400">
                {{ $hasResultado ? 'Apurado' : 'Aguardando Sorteio' }}
            </span>
        </div>
        <p class="text-xs text-slate-400 mt-1">
            Conferência dos acertos e desempenho detalhado dos jogos do bolão.
        </p>
    </div>

    <!-- Resumo dos Prêmios da Lotofácil -->
    <div class="bg-[#162032] border border-slate-800/80 rounded-2xl p-5 shadow-xl space-y-3">
        <h3 class="text-sm font-bold text-white uppercase tracking-wider text-slate-400 mb-2">
            Desempenho Geral (Lotofácil)
        </h3>

        <div class="flex items-center justify-between p-3 rounded-xl bg-[#202C40] border border-slate-800/50">
            <span class="text-xs font-bold text-slate-300">15 Acertos (Premiação Máxima)</span>
            <span class="text-base font-extrabold text-emerald-400">{{ $resumoResultados['acertos_15'] }}</span>
        </div>

        <div class="flex items-center justify-between p-3 rounded-xl bg-[#202C40] border border-slate-800/50">
            <span class="text-xs font-bold text-slate-300">14 Acertos</span>
            <span class="text-base font-extrabold text-purple-400">{{ $resumoResultados['acertos_14'] }}</span>
        </div>

        <div class="flex items-center justify-between p-3 rounded-xl bg-[#202C40] border border-slate-800/50">
            <span class="text-xs font-bold text-slate-300">13 Acertos</span>
            <span class="text-base font-extrabold text-amber-400">{{ $resumoResultados['acertos_13'] }}</span>
        </div>

        <div class="flex items-center justify-between p-3 rounded-xl bg-[#202C40] border border-slate-800/50">
            <span class="text-xs font-bold text-slate-300">12 Acertos</span>
            <span class="text-base font-extrabold text-sky-400">{{ $resumoResultados['acertos_12'] }}</span>
        </div>

        <div class="flex items-center justify-between p-3 rounded-xl bg-[#202C40] border border-slate-800/50">
            <span class="text-xs font-bold text-slate-300">11 Acertos</span>
            <span class="text-base font-extrabold text-slate-400">{{ $resumoResultados['acertos_11'] }}</span>
        </div>
    </div>

    @if(!$hasResultado)
        <div class="bg-[#162032] border border-slate-800/80 rounded-2xl p-5 shadow-xl text-center">
            <p class="text-xs text-slate-400">
                O sorteio oficial ainda não foi realizado. Assim que as dezenas forem apuradas, os acertos aparecerão aqui automaticamente.
            </p>
        </div>
    @endif

</div>
