<div 
    wire:poll.3s="refreshBoard"
    x-data="{
        minhas: @entangle('minhasDezenas'),
        outros: @entangle('dezenasOutros'),
        isLocked: @entangle('isLocked'),
        tendencias: @js($tendencias),
        limitePessoal: @entangle('limitePessoal'),
        toasts: [],
        showToast(message, type = 'error') {
            const id = Date.now();
            this.toasts.push({ id, message, type });
            setTimeout(() => {
                this.toasts = this.toasts.filter(t => t.id !== id);
            }, 3000);
        },
        getBallStyle(num, isBlocked = false) {
            if (this.isLocked) {
                if (this.minhas.includes(num)) {
                    return 'background: #4338ca; color: white; outline: 2px solid #818cf8; outline-offset: 2px;';
                }
                return 'background: #131722; color: #334155; opacity: 0.35; cursor: not-allowed; border: 1px solid #1e2433;';
            }

            if (this.minhas.includes(num)) {
                return 'background: radial-gradient(circle at 35% 35%, #86efac, #16a34a 55%, #14532d); color: white; outline: 2px solid #22c55e; outline-offset: 2px; box-shadow: 0 0 12px rgba(34,197,94,0.6); cursor: pointer;';
            }
            if (this.outros.includes(num)) {
                return 'background: radial-gradient(circle at 35% 30%, #f1f5f9 0%, #cbd5e1 32%, #94a3b8 70%, #64748b 100%); color: #475569; cursor: not-allowed; box-shadow: 0 2px 4px rgba(0,0,0,0.4);';
            }

            if (isBlocked) {
                return 'background: #131722; color: #334155; opacity: 0.35; cursor: not-allowed; border: 1px solid #1e2433;';
            }
            
            const temp = this.tendencias[num] || 'frio';
            if (temp === 'quente') {
                return 'background: radial-gradient(circle at 35% 35%, #fca5a5, #dc2626 55%, #7f1d1d); color: white; cursor: pointer; box-shadow: 0 2px 5px rgba(220,38,38,0.4);';
            }
            if (temp === 'morno') {
                return 'background: radial-gradient(circle at 35% 35%, #fde68a, #d97706 55%, #78350f); color: white; cursor: pointer; box-shadow: 0 2px 5px rgba(217,119,6,0.4);';
            }
            return 'background: radial-gradient(circle at 35% 35%, #818cf8, #4338ca 55%, #1e1b4b); color: white; cursor: pointer; box-shadow: 0 2px 5px rgba(67,56,202,0.4);';
        }
    }"
    x-on:toast.window="showToast($event.detail.message, $event.detail.type)"
    class="space-y-4 py-2"
>
    <!-- Toast Notifications Mobile -->
    <div class="fixed top-4 left-4 right-4 z-50 pointer-events-none flex flex-col items-center gap-2">
        <template x-for="toast in toasts" :key="toast.id">
            <div 
                x-text="toast.message" 
                :class="toast.type === 'success' ? 'bg-indigo-600 text-white' : (toast.type === 'info' ? 'bg-slate-700 text-white' : 'bg-rose-600 text-white')"
                class="px-4 py-2.5 rounded-xl text-xs font-bold shadow-2xl border border-white/20 transition-all duration-300 transform translate-y-0 text-center max-w-sm pointer-events-auto"
            ></div>
        </template>
    </div>

    <!-- Top Bar / Header de Status da Cota e Regras -->
    <div class="bg-[#162032] border border-slate-800 rounded-2xl p-3 shadow-lg space-y-2">
        <div class="flex justify-between items-center">
            <div>
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Sua Cota na Quina</span>
                <div class="text-sm font-bold text-white mt-0.5">
                    <template x-if="(limitePessoal - minhas.length) > 0">
                        <span>Faltam <strong class="text-indigo-400 text-base" x-text="limitePessoal - minhas.length"></strong> dezenas</span>
                    </template>
                    <template x-if="(limitePessoal - minhas.length) <= 0">
                        <span class="text-emerald-400 font-bold flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            Cota Completa!
                        </span>
                    </template>
                </div>
            </div>

            <div class="text-right">
                <span class="text-xs font-extrabold px-3 py-1.5 rounded-xl bg-[#243044] border border-slate-700/80 text-indigo-400">
                    {{ count($minhasDezenas) }} / {{ $limitePessoal }} marcadas
                </span>
            </div>
        </div>

        <div class="pt-2 border-t border-slate-800/80 flex items-center justify-between text-[11px] text-slate-300">
            <span class="text-slate-400">Regra de Distribuição:</span>
            <span class="font-semibold text-indigo-400">
                Máx. {{ $maxSub }} por Sub-Quadrante (2x2)
            </span>
        </div>
    </div>

    <!-- Legenda de Cores por Temperatura -->
    <div class="bg-[#162032] border border-slate-800 rounded-xl p-2.5 flex items-center justify-around text-[10px] text-slate-300 font-medium">
        <div class="flex items-center gap-1.5">
            <span class="w-3.5 h-3.5 rounded-full bg-gradient-to-br from-red-400 to-red-600 inline-block shadow-sm"></span>
            <span>Quente</span>
        </div>
        <div class="flex items-center gap-1.5">
            <span class="w-3.5 h-3.5 rounded-full bg-gradient-to-br from-amber-300 to-amber-600 inline-block shadow-sm"></span>
            <span>Morna</span>
        </div>
        <div class="flex items-center gap-1.5">
            <span class="w-3.5 h-3.5 rounded-full bg-gradient-to-br from-indigo-400 to-indigo-700 inline-block shadow-sm"></span>
            <span>Fria</span>
        </div>
        <div class="flex items-center gap-1.5">
            <span class="w-3.5 h-3.5 rounded-full bg-gradient-to-br from-emerald-300 to-emerald-600 inline-block shadow-sm ring-1 ring-emerald-400"></span>
            <span class="text-emerald-400 font-bold">Minha</span>
        </div>
        <div class="flex items-center gap-1.5">
            <span class="w-3.5 h-3.5 rounded-full bg-gradient-to-br from-slate-200 to-slate-500 inline-block shadow-sm"></span>
            <span>Outro</span>
        </div>
    </div>

    <!-- Os 4 Quadrantes Empilhados Verticalmente (um abaixo do outro) com 5 Sub-Quadrantes (Blocos 2x2) cada -->
    <div class="space-y-5">
        @foreach($quadrantesDef as $q)
            @php
                $qColor = match($q['id']) {
                    1 => ['border' => 'border-indigo-800/80', 'text' => 'text-indigo-400', 'badge' => 'bg-indigo-950/80 text-indigo-300 border-indigo-700/60', 'dot' => 'bg-indigo-400'],
                    2 => ['border' => 'border-blue-800/80', 'text' => 'text-blue-400', 'badge' => 'bg-blue-950/80 text-blue-300 border-blue-700/60', 'dot' => 'bg-blue-400'],
                    3 => ['border' => 'border-violet-800/80', 'text' => 'text-violet-400', 'badge' => 'bg-violet-950/80 text-violet-300 border-violet-700/60', 'dot' => 'bg-violet-400'],
                    default => ['border' => 'border-purple-800/80', 'text' => 'text-purple-400', 'badge' => 'bg-purple-950/80 text-purple-300 border-purple-700/60', 'dot' => 'bg-purple-400'],
                };
            @endphp

            <div class="border-2 {{ $qColor['border'] }} rounded-2xl p-3 bg-[#111827] shadow-xl relative">
                
                <!-- Cabeçalho do Quadrante -->
                <div class="flex items-center justify-between mb-3 px-1">
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full {{ $qColor['dot'] }}"></span>
                        <h3 class="text-xs font-black uppercase tracking-wider {{ $qColor['text'] }}">
                            {{ $q['label'] }}
                        </h3>
                    </div>
                    
                    <span class="text-[11px] font-bold px-2.5 py-0.5 rounded-full border {{ $qColor['badge'] }}">
                        {{ $q['count'] }} / {{ $q['max'] }}
                    </span>
                </div>

                <!-- Grid de 5 Sub-Quadrantes (Blocos 2x2: duas dezenas em cima, duas embaixo) -->
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-2.5">
                    @for($subIdx = $q['sub_start']; $subIdx <= $q['sub_end']; $subIdx++)
                        @php
                            $qIdx = $q['id'] - 1; // 0, 1, 2, 3
                            $blocoCol = ($subIdx - 1) % 5; // 0, 1, 2, 3, 4
                            $linhaBase = $qIdx * 20;
                            $colStart = $blocoCol * 2 + 1;

                            $n1 = $linhaBase + $colStart;
                            $n2 = $linhaBase + $colStart + 1;
                            $n3 = $linhaBase + $colStart + 10;
                            $n4 = $linhaBase + $colStart + 11;

                            $subCount = $stats['subs'][$subIdx] ?? 0;
                            $isFull = $subCount >= $maxSub && $maxSub > 0;
                            $isQuadFull = $q['max'] > 0 && $q['count'] >= $q['max'];
                            $isBlocked = ($isFull || $isQuadFull);
                        @endphp

                        <div class="bg-[#162032] border {{ $isFull ? 'border-indigo-500/70 shadow-indigo-950/50' : 'border-slate-800/90' }} rounded-xl p-2 flex flex-col items-center justify-between shadow-md transition-all">
                            
                            <!-- Header do Sub-Quadrante -->
                            <div class="w-full flex items-center justify-between text-[10px] mb-2 px-1">
                                <span class="font-bold text-slate-400">Sub-{{ $subIdx }}</span>
                                <span class="font-extrabold {{ $isFull ? 'text-indigo-400' : ($subCount > 0 ? 'text-amber-400' : 'text-slate-500') }}">
                                    {{ $subCount }}/{{ $maxSub }}
                                </span>
                            </div>

                            <!-- Grade 2x2 do Sub-Quadrante -->
                            <div class="grid grid-cols-2 gap-2 justify-items-center">
                                @foreach([$n1, $n2, $n3, $n4] as $dezena)
                                    <button 
                                        type="button" 
                                        wire:click="toggleDezena({{ $dezena }})" 
                                        :style="getBallStyle({{ $dezena }}, {{ $isBlocked ? 'true' : 'false' }})"
                                        class="w-10 h-10 rounded-full flex items-center justify-center font-extrabold text-xs shadow-md transition-all active:scale-90"
                                    >
                                        {{ str_pad($dezena, 2, '0', STR_PAD_LEFT) }}
                                    </button>
                                @endforeach
                            </div>

                        </div>
                    @endfor
                </div>

            </div>
        @endforeach
    </div>

</div>
