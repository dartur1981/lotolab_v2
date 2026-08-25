<div class="flex-1 flex flex-col items-center pt-6 pb-2 w-full max-w-sm mx-auto h-full">

    <!-- Header / Branding Lotofácil -->
    <div class="text-center mb-6 shrink-0">
        <h1 class="text-4xl font-extrabold text-[#00C853] tracking-tight">
            Bolão Lotofácil
        </h1>
        <p class="text-sm text-slate-400 mt-2 font-normal">
            LotoLab — Participante
        </p>
    </div>

    <!-- Card Principal -->
    <div class="w-full flex-1 flex flex-col bg-[#162032] border border-slate-800/80 rounded-2xl p-6 shadow-2xl shadow-black/40 min-h-0">
        
        @if(!$selectedUserId)
            <!-- Passo 1: Seleção de Usuário -->
            <h2 class="text-2xl font-bold text-white text-center mb-6 tracking-tight shrink-0">
                Quem é você?
            </h2>
            
            <div class="space-y-3 flex-1 overflow-y-auto pr-2 custom-scrollbar">
                @forelse($participantes as $participante)
                    <button 
                        wire:click="selectUser({{ $participante->id }})"
                        class="w-full text-left px-4 py-3 rounded-xl bg-[#243044] border border-slate-700/60 hover:bg-[#2A3850] hover:border-[#00C853]/50 transition flex items-center space-x-3 group"
                    >
                        <div class="w-10 h-10 rounded-full bg-slate-700 flex items-center justify-center text-[#00C853] font-bold shrink-0">
                            {{ strtoupper(substr($participante->name, 0, 1)) }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-white font-semibold truncate group-hover:text-[#00C853] transition">
                                {{ $participante->name }}
                            </p>
                            <p class="text-xs text-slate-400 truncate">
                                {{ $participante->email }}
                            </p>
                        </div>
                        <div class="text-slate-500 group-hover:text-[#00C853] transition">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                            </svg>
                        </div>
                    </button>
                @empty
                    <div class="text-center py-6">
                        <p class="text-slate-400 text-sm">Nenhum participante encontrado para o bolão ativo.</p>
                    </div>
                @endforelse
            </div>
            
        @else
            <!-- Passo 2: Confirmação -->
            <h2 class="text-xl font-bold text-white text-center mb-6 tracking-tight">
                Confirme seu Acesso
            </h2>
            
            <div class="flex flex-col items-center justify-center bg-[#243044] border border-[#00C853]/30 rounded-xl p-6 mb-6">
                <div class="w-16 h-16 rounded-full bg-[#00C853]/20 flex items-center justify-center text-[#00C853] font-bold text-2xl mb-3">
                    {{ strtoupper(substr($selectedUser->name, 0, 1)) }}
                </div>
                <p class="text-white font-bold text-lg text-center">
                    {{ $selectedUser->name }}
                </p>
                <p class="text-sm text-slate-400 mt-1">
                    {{ $selectedUser->email }}
                </p>
            </div>

            <div class="space-y-3">
                <button 
                    wire:click="login" 
                    wire:loading.attr="disabled"
                    class="w-full py-3 px-4 rounded-xl bg-[#00C853] hover:bg-[#00E676] active:bg-[#00A847] text-white font-bold text-base shadow-lg shadow-[#00C853]/25 active:scale-[0.98] transition flex items-center justify-center space-x-2"
                >
                    <span wire:loading.remove wire:target="login">Entrar no App</span>
                    <span wire:loading wire:target="login" class="inline-block animate-spin rounded-full h-4 w-4 border-2 border-white border-t-transparent"></span>
                </button>
                
                <button 
                    wire:click="cancelSelection" 
                    wire:loading.attr="disabled"
                    class="w-full py-3 px-4 rounded-xl bg-transparent border border-slate-700 text-slate-300 hover:bg-slate-800 hover:text-white font-semibold text-base transition flex items-center justify-center"
                >
                    Voltar
                </button>
            </div>
        @endif
        
    </div>

    <!-- Rodapé -->
    <div class="text-center mt-6 shrink-0">
        <p class="text-xs text-slate-500/80">
            © 2026 LotoLab. Todos os direitos reservados.
        </p>
    </div>

</div>
