<div class="flex flex-col items-center justify-center min-h-screen pt-4 pb-12">
    <div class="w-full max-w-sm mb-8 text-center">
        <h1 class="text-3xl font-black text-white mb-2">
            Meus Bolões
        </h1>
        <p class="text-slate-400 text-sm">
            Selecione o bolão ativo para continuar.
        </p>
    </div>

    <div class="w-full space-y-4">
        @forelse($boloes as $bolao)
            <button 
                wire:click="selecionarBolao({{ $bolao['id'] }})"
                class="w-full text-left bg-[#162032] border border-slate-700 hover:border-brand-green/50 hover:bg-[#1C283F] p-4 rounded-xl shadow-lg transition-all active:scale-[0.98] group flex items-center justify-between"
            >
                <div>
                    <h3 class="text-white font-bold text-lg group-hover:text-brand-green transition-colors">
                        {{ $bolao['nome'] }}
                    </h3>
                    <p class="text-slate-400 text-xs mt-1">
                        {{ $bolao['total_numeros'] }} dezenas &bull; Máx Moldura: {{ $bolao['max_moldura'] }}
                    </p>
                </div>
                
                <div class="w-8 h-8 rounded-full bg-slate-800/50 flex items-center justify-center group-hover:bg-brand-green/20 group-hover:text-brand-green text-slate-500 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </div>
            </button>
        @empty
            <div class="bg-[#162032] border border-slate-700/50 p-6 rounded-xl text-center shadow">
                <svg class="w-12 h-12 text-slate-500 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <h3 class="text-white font-bold text-lg mb-1">Nenhum Bolão Ativo</h3>
                <p class="text-slate-400 text-sm">
                    Você não está participando de nenhum bolão ativo no momento.
                </p>
            </div>
        @endforelse
    </div>
</div>
