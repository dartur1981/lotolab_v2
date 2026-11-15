<div class="flex-1 flex flex-col items-center pt-6 pb-2 w-full max-w-sm mx-auto h-full">

    <div class="text-center mb-6 shrink-0">
        <h1 class="text-3xl font-black text-emerald-400 tracking-tight">
            Seus Bolões Ativos
        </h1>
        <p class="text-xs text-slate-400 mt-1">
            Selecione o bolão da Mega-Sena para continuar
        </p>
    </div>

    <div class="w-full flex-1 flex flex-col bg-[#162032] border border-slate-800/80 rounded-2xl p-5 shadow-2xl space-y-3 overflow-y-auto">
        @forelse($boloes as $b)
            <button 
                wire:click="selecionarBolao({{ $b['id'] }})"
                class="w-full text-left p-4 rounded-xl bg-[#243044] border border-slate-700/60 hover:border-emerald-500/50 hover:bg-[#2A3850] transition group active:scale-[0.98]"
            >
                <div class="flex items-center justify-between mb-1">
                    <span class="text-xs font-mono font-bold text-emerald-400">#{{ $b['id'] }}</span>
                    <span class="text-[10px] uppercase font-bold px-2 py-0.5 rounded bg-emerald-500/20 text-emerald-300 border border-emerald-600/40">
                        {{ $b['status'] == 2 ? 'Encerrado' : 'Ativo' }}
                    </span>
                </div>
                <h3 class="text-base font-bold text-white group-hover:text-emerald-400 transition truncate">
                    {{ $b['nome'] }}
                </h3>
                <div class="flex items-center justify-between text-xs text-slate-400 mt-2 pt-2 border-t border-slate-700/40">
                    <span>Pool: <strong class="text-white">{{ $b['total_numeros'] }} dezenas</strong></span>
                    <span>Concurso: <strong class="text-white">{{ $b['concurso_alvo'] ?? 'N/A' }}</strong></span>
                </div>
            </button>
        @empty
            <div class="text-center py-12">
                <span class="text-3xl block mb-2">📋</span>
                <p class="text-sm font-semibold text-white">Nenhum bolão pendente</p>
                <p class="text-xs text-slate-400 mt-1">Você já preencheu suas dezenas ou não há bolões ativos.</p>
                <a href="/pwa/megasena/dashboard" class="inline-block mt-4 text-xs font-bold text-emerald-400 underline">
                    Ir para o Dashboard
                </a>
            </div>
        @endforelse
    </div>

</div>
