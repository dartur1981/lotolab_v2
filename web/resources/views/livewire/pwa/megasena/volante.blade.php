<div 
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
        getBallStyle(num) {
            if (this.isLocked) {
                if (this.minhas.includes(num)) {
                    return 'background: #059669; color: white; outline: 2px solid #10b981; outline-offset: 2px;';
                }
                return 'opacity: 0.4; cursor: not-allowed;';
            }

            if (this.minhas.includes(num)) {
                return 'background: radial-gradient(circle at 35% 35%, #86efac, #16a34a 55%, #14532d); color: white; outline: 2px solid #22c55e; outline-offset: 2px; box-shadow: 0 0 14px rgba(34,197,94,0.6);';
            }
            if (this.outros.includes(num)) {
                return 'background: #334155; color: #94a3b8; opacity: 0.5; cursor: not-allowed;';
            }
            
            const temp = this.tendencias[num] || 'frio';
            if (temp === 'quente') {
                return 'background: radial-gradient(circle at 35% 35%, #fca5a5, #dc2626 55%, #7f1d1d); color: white;';
            }
            if (temp === 'morno') {
                return 'background: radial-gradient(circle at 35% 35%, #fde68a, #d97706 55%, #78350f); color: white;';
            }
            return 'background: radial-gradient(circle at 35% 35%, #93c5fd, #1d4ed8 55%, #1e1b4b); color: white;';
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
                :class="toast.type === 'success' ? 'bg-emerald-600 text-white' : 'bg-rose-600 text-white'"
                class="px-4 py-2 rounded-xl text-xs font-bold shadow-xl border border-white/20 transition-all duration-300 transform translate-y-0"
            ></div>
        </template>
    </div>

    <!-- Top Bar / Header de Status da Cota -->
    <div class="flex justify-between items-end px-2">
        <div>
            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Sua Cota na Mega-Sena</span>
            <div class="text-sm font-bold text-white mt-0.5">
                <template x-if="(limitePessoal - minhas.length) > 0">
                    <span>Faltam <strong class="text-emerald-400 text-base" x-text="limitePessoal - minhas.length"></strong> dezenas</span>
                </template>
                <template x-if="(limitePessoal - minhas.length) <= 0">
                    <span class="text-emerald-400 font-bold flex items-center gap-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        Cota Completa!
                    </span>
                </template>
            </div>
        </div>

        <div class="text-right text-xs text-slate-400 font-medium mb-1">
            <span>{{ count($minhasDezenas) }} / {{ $limitePessoal }} marcadas</span>
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
            <span class="w-3.5 h-3.5 rounded-full bg-gradient-to-br from-blue-300 to-blue-600 inline-block shadow-sm"></span>
            <span>Fria</span>
        </div>
        <div class="flex items-center gap-1.5">
            <span class="w-3.5 h-3.5 rounded-full bg-gradient-to-br from-emerald-300 to-emerald-600 inline-block shadow-sm ring-1 ring-emerald-400"></span>
            <span class="text-emerald-400 font-bold">Minha</span>
        </div>
    </div>

    <!-- Volante 1 a 60 Dividido em 3 Quadrantes -->
    <div class="space-y-4">

        <!-- Quadrante 1 (01 a 20) -->
        <fieldset class="border-2 border-sky-800/80 rounded-2xl p-3 bg-[#162032] relative shadow-lg">
            <legend class="text-xs font-bold text-sky-400 px-2.5 bg-[#162032] rounded-md border border-sky-800/60 shadow-sm flex items-center gap-1">
                <span>Quadrante 1 (01 a 20)</span>
                <span class="text-[10px] text-slate-400 font-normal">({{ $q1Count }}/{{ $bolaoAtivo['max_quadrante_1'] ?? 'Auto' }})</span>
            </legend>
            <div class="grid grid-cols-5 gap-2 justify-items-center pt-2">
                @for ($n = 1; $n <= 20; $n++)
                    <button 
                        type="button" 
                        wire:click="toggleDezena({{ $n }})" 
                        :style="getBallStyle({{ $n }})"
                        class="w-11 h-11 rounded-full flex items-center justify-center font-extrabold text-sm shadow-md transition-all active:scale-90"
                    >
                        {{ str_pad($n, 2, '0', STR_PAD_LEFT) }}
                    </button>
                @endfor
            </div>
        </fieldset>

        <!-- Quadrante 2 (21 a 40) -->
        <fieldset class="border-2 border-indigo-800/80 rounded-2xl p-3 bg-[#162032] relative shadow-lg">
            <legend class="text-xs font-bold text-indigo-400 px-2.5 bg-[#162032] rounded-md border border-indigo-800/60 shadow-sm flex items-center gap-1">
                <span>Quadrante 2 (21 a 40)</span>
                <span class="text-[10px] text-slate-400 font-normal">({{ $q2Count }}/{{ $bolaoAtivo['max_quadrante_2'] ?? 'Auto' }})</span>
            </legend>
            <div class="grid grid-cols-5 gap-2 justify-items-center pt-2">
                @for ($n = 21; $n <= 40; $n++)
                    <button 
                        type="button" 
                        wire:click="toggleDezena({{ $n }})" 
                        :style="getBallStyle({{ $n }})"
                        class="w-11 h-11 rounded-full flex items-center justify-center font-extrabold text-sm shadow-md transition-all active:scale-90"
                    >
                        {{ str_pad($n, 2, '0', STR_PAD_LEFT) }}
                    </button>
                @endfor
            </div>
        </fieldset>

        <!-- Quadrante 3 (41 a 60) -->
        <fieldset class="border-2 border-purple-800/80 rounded-2xl p-3 bg-[#162032] relative shadow-lg">
            <legend class="text-xs font-bold text-purple-400 px-2.5 bg-[#162032] rounded-md border border-purple-800/60 shadow-sm flex items-center gap-1">
                <span>Quadrante 3 (41 a 60)</span>
                <span class="text-[10px] text-slate-400 font-normal">({{ $q3Count }}/{{ $bolaoAtivo['max_quadrante_3'] ?? 'Auto' }})</span>
            </legend>
            <div class="grid grid-cols-5 gap-2 justify-items-center pt-2">
                @for ($n = 41; $n <= 60; $n++)
                    <button 
                        type="button" 
                        wire:click="toggleDezena({{ $n }})" 
                        :style="getBallStyle({{ $n }})"
                        class="w-11 h-11 rounded-full flex items-center justify-center font-extrabold text-sm shadow-md transition-all active:scale-90"
                    >
                        {{ str_pad($n, 2, '0', STR_PAD_LEFT) }}
                    </button>
                @endfor
            </div>
        </fieldset>

    </div>

</div>
