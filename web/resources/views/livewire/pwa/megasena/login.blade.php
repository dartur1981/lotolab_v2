<div wire:poll.5s class="flex-1 flex flex-col items-center pt-6 pb-2 w-full max-w-sm mx-auto h-full">

    <!-- Header / Branding Mega-Sena -->
    <div class="text-center mb-6 shrink-0">
        <div class="inline-flex items-center justify-center w-12 h-12 rounded-2xl bg-gradient-to-br from-emerald-400 to-emerald-600 text-white font-black text-xl shadow-lg shadow-emerald-900/50 mb-2">
            MS
        </div>
        <div class="flex items-center justify-center gap-2 flex-wrap mb-1">
            <h1 class="text-3xl font-extrabold text-emerald-400 tracking-tight">
                Mega-Sena Bolão
            </h1>
            @if(isset($totalBoloesAbertos) && $totalBoloesAbertos > 0)
                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-950/80 text-emerald-300 border border-emerald-500/50 shadow-sm shadow-emerald-950/50">
                    <span class="relative flex h-2 w-2">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-400"></span>
                    </span>
                    <span>{{ $totalBoloesAbertos }} {{ $totalBoloesAbertos == 1 ? 'BOLÃO ABERTO' : 'BOLÕES ABERTOS' }}</span>
                </span>
            @endif
        </div>
        <p class="text-xs text-slate-400 font-normal">
            LotoLab — Acesso do Participante
        </p>
    </div>

    <!-- Card Principal -->
    <div class="w-full flex-1 flex flex-col bg-[#162032] border border-slate-800/80 rounded-2xl p-6 shadow-2xl shadow-black/40 min-h-0">
        
        @if(!$selectedUserId)
            <!-- Passo 1: Seleção de Usuário -->
            <h2 class="text-xl font-bold text-white text-center mb-4 tracking-tight shrink-0">
                Selecione o seu nome
            </h2>
            
            <div class="space-y-2.5 flex-1 overflow-y-auto pr-1">
                @forelse($participantes as $participante)
                    <button 
                        wire:click="selectUser({{ $participante->id }})"
                        class="w-full text-left px-4 py-3 rounded-xl bg-[#243044] border border-slate-700/60 hover:bg-[#2A3850] hover:border-emerald-500/50 transition flex items-center space-x-3 group active:scale-[0.98]"
                    >
                        <div class="w-10 h-10 rounded-full bg-slate-700 flex items-center justify-center text-emerald-400 font-bold shrink-0">
                            {{ strtoupper(substr($participante->name, 0, 1)) }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-white font-semibold truncate group-hover:text-emerald-400 transition text-sm">
                                {{ $participante->name }}
                            </p>
                            <p class="text-[11px] text-slate-400 truncate">
                                {{ $participante->email }}
                            </p>
                        </div>
                        <div class="text-slate-500 group-hover:text-emerald-400 transition">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                            </svg>
                        </div>
                    </button>
                @empty
                    <div class="text-center py-8 px-4 bg-[#1e293b]/50 rounded-2xl border border-slate-700/50">
                        <div class="w-12 h-12 mx-auto mb-3 rounded-full bg-emerald-500/20 flex items-center justify-center text-emerald-400">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        </div>
                        <p class="text-white font-bold text-sm">Seleções Finalizadas!</p>
                        <p class="text-slate-400 text-xs mt-1">Todos os participantes já selecionaram suas dezenas ou não há bolões em andamento.</p>
                    </div>
                @endforelse
            </div>

        @else
            <!-- Passo 2: Confirmação -->
            <h2 class="text-xl font-bold text-white text-center mb-4 tracking-tight">
                Confirme seu Acesso
            </h2>
            
            <div class="flex flex-col items-center justify-center bg-[#243044] border border-emerald-500/30 rounded-xl p-5 mb-5">
                <div class="w-16 h-16 rounded-full bg-emerald-500/20 flex items-center justify-center text-emerald-400 font-bold text-2xl mb-2">
                    {{ strtoupper(substr($selectedUser->name, 0, 1)) }}
                </div>
                <h3 class="text-lg font-bold text-white text-center">
                    {{ $selectedUser->name }}
                </h3>
                <p class="text-xs text-slate-400 text-center">
                    {{ $selectedUser->email }}
                </p>
            </div>

            <div class="space-y-3 mt-auto">
                <button 
                    wire:click="login"
                    class="w-full bg-emerald-600 hover:bg-emerald-500 active:scale-95 text-white font-bold py-3 px-4 rounded-xl shadow-lg shadow-emerald-900/30 transition flex items-center justify-center space-x-2"
                >
                    <span>Entrar no Bolão</span>
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-4 h-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                    </svg>
                </button>

                <button 
                    wire:click="cancelSelection"
                    class="w-full bg-transparent hover:bg-slate-800 text-slate-400 hover:text-white font-medium py-2.5 px-4 rounded-xl text-sm transition"
                >
                    Não sou eu (voltar)
                </button>
            </div>
        @endif

        <div class="mt-4 pt-3 border-t border-slate-800/80 text-center">
            <a href="/pwa" class="text-xs text-slate-500 hover:text-slate-400">
                &larr; Voltar para seleção de loterias
            </a>
        </div>
    </div>

</div>
