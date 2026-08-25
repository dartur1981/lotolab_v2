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
            let cursorStyle = this.isLocked ? 'cursor: not-allowed;' : '';
            let baseGray = 'background: radial-gradient(circle at 35% 35%, #d1d5db, #9ca3af 60%, #6b7280); color: #374151;';
            
            if (this.isLocked) {
                if (this.minhas.includes(num)) {
                    return ` outline: 2px solid #9ca3af; outline-offset: 2px; box-shadow: 0 0 12px rgba(156,163,175,0.5); `;
                }
                return ` opacity: 0.65; `;
            }

            if (this.minhas.includes(num)) {
                return `background: radial-gradient(circle at 35% 35%, #86efac, #16a34a 55%, #14532d); color: white; outline: 2px solid #22c55e; outline-offset: 2px; box-shadow: 0 0 12px rgba(34,197,94,0.5); `;
            }
            if (this.outros.includes(num)) {
                return ` opacity: 0.65; cursor: not-allowed;`;
            }
            
            const temp = this.tendencias[num] || 'frio';
            if (temp === 'quente') {
                return `background: radial-gradient(circle at 35% 35%, #fca5a5, #dc2626 55%, #7f1d1d); color: white; `;
            }
            if (temp === 'morno') {
                return `background: radial-gradient(circle at 35% 35%, #fde68a, #d97706 55%, #78350f); color: white; `;
            }
            return `background: radial-gradient(circle at 35% 35%, #93c5fd, #1d4ed8 55%, #1e1b4b); color: white; `;
        }
    }"
    x-on:toast.window="showToast($event.detail.message, $event.detail.type)"
    class="space-y-4 py-2"
>
    <!-- Top Bar / Header de Status da Cota -->
    <div class="flex justify-between items-end px-2">
        <div>
            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Sua Cota no Bolão</span>
            <div class="text-sm font-bold text-white mt-0.5">
                <template x-if="(limitePessoal - minhas.length) > 0">
                    <span>Faltam <strong class="text-[#00C853] text-base" x-text="limitePessoal - minhas.length"></strong> dezenas</span>
                </template>
                <template x-if="(limitePessoal - minhas.length) <= 0">
                    <span class="text-[#00C853] font-bold flex items-center gap-1">
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

    <!-- Sugestão de Dezena Casada (Dica de Ouro) -->
    @if ($sugestaoAtual)
        <div class="bg-gradient-to-br from-indigo-950 to-indigo-900 border border-indigo-700/50 rounded-xl p-3 flex items-center justify-between shadow-lg shadow-indigo-900/40 animate-pulse mb-2">
            <div class="flex items-center gap-3">
                <span class="text-2xl drop-shadow-md">💡</span>
                <div>
                    <p class="m-0 text-[10px] text-indigo-300 font-black uppercase tracking-widest">Dica de Ouro</p>
                    <p class="m-0 text-xs text-indigo-100 font-medium mt-0.5 leading-snug">
                        A dezena <strong class="text-indigo-400 font-black text-sm">{{ str_pad($sugestaoAtual['sugerida'], 2, '0', STR_PAD_LEFT) }}</strong> costuma ser<br>sorteada junto com a {{ str_pad($sugestaoAtual['marcada'], 2, '0', STR_PAD_LEFT) }}.
                    </p>
                </div>
            </div>
            <button 
                type="button" 
                wire:click="aceitarSugestao" 
                class="bg-indigo-600 hover:bg-indigo-500 text-white border-none py-2 px-3 rounded-lg font-bold text-xs cursor-pointer shadow-md active:scale-95 transition-all flex items-center gap-1"
            >
                <span>+</span> <span>{{ str_pad($sugestaoAtual['sugerida'], 2, '0', STR_PAD_LEFT) }}</span>
            </button>
        </div>
    @endif

    <!-- Volante 5x5 com Fieldsets Aninhados e Bolas Coloridas por Temperatura -->
    <div class="bg-[#162032] border border-slate-800/80 rounded-2xl p-4 shadow-xl">
        
        <fieldset class="border-2 border-purple-800/80 rounded-2xl py-6 px-3 relative shadow-md">
            <legend class="text-xs font-bold text-purple-400 px-2.5 bg-[#162032] rounded-md border border-purple-900/60 shadow-sm">
                Moldura ({{ $countMoldura }}/{{ $maxMoldura }})
            </legend>

            <!-- Grade 5x5 -->
            <div class="grid grid-cols-5 gap-x-2 gap-y-3.5 items-center justify-items-center">
                
                <!-- Linha 1 da Moldura (01 a 05) -->
                @foreach([1, 2, 3, 4, 5] as $i)
                    <button 
                        type="button"
                        wire:click="toggleDezena({{ $i }})"
                        :style="getBallStyle({{ $i }})"
                        class="w-11 h-11 rounded-full text-sm font-black flex items-center justify-center transition-all duration-150 active:scale-90 cursor-pointer shadow-md"
                    >
                        {{ str_pad($i, 2, '0', STR_PAD_LEFT) }}
                    </button>
                @endforeach

                <!-- Linha 2 Esquerda: 06 -->
                <button 
                    type="button"
                    wire:click="toggleDezena(6)"
                    :style="getBallStyle(6)"
                    class="w-11 h-11 rounded-full text-sm font-black flex items-center justify-center transition-all duration-150 active:scale-90 cursor-pointer shadow-md"
                >
                    06
                </button>

                <!-- FIELDSET REAL DO MIOLO (Com largura e altura ligeiramente expandidas) -->
                <fieldset class="col-start-2 col-span-3 row-start-2 row-span-3 border-2 border-amber-600/80 rounded-2xl p-3 bg-[#121C2B]/90 grid grid-cols-3 gap-2.5 justify-items-center relative -mx-2.5 -my-2.5 shadow-lg">
                    <legend class="text-[10px] font-extrabold text-amber-400 px-2 py-0.5 bg-[#162032] rounded-md border border-amber-800/80 shadow">
                        Miolo ({{ $countMiolo }}/{{ $maxMiolo }})
                    </legend>

                    @foreach([7, 8, 9, 12, 13, 14, 17, 18, 19] as $i)
                        <button 
                            type="button"
                            wire:click="toggleDezena({{ $i }})"
                            :style="getBallStyle({{ $i }})"
                            class="w-11 h-11 rounded-full text-sm font-black flex items-center justify-center transition-all duration-150 active:scale-90 cursor-pointer shadow-md"
                        >
                            {{ str_pad($i, 2, '0', STR_PAD_LEFT) }}
                        </button>
                    @endforeach
                </fieldset>

                <!-- Linha 2 Direita: 10 -->
                <div class="col-start-5 row-start-2">
                    <button 
                        type="button"
                        wire:click="toggleDezena(10)"
                        :style="getBallStyle(10)"
                        class="w-11 h-11 rounded-full text-sm font-black flex items-center justify-center transition-all duration-150 active:scale-90 cursor-pointer shadow-md"
                    >
                        10
                    </button>
                </div>

                <!-- Linha 3 Esquerda: 11 -->
                <div class="col-start-1 row-start-3">
                    <button 
                        type="button"
                        wire:click="toggleDezena(11)"
                        :style="getBallStyle(11)"
                        class="w-11 h-11 rounded-full text-sm font-black flex items-center justify-center transition-all duration-150 active:scale-90 cursor-pointer shadow-md"
                    >
                        11
                    </button>
                </div>

                <!-- Linha 3 Direita: 15 -->
                <div class="col-start-5 row-start-3">
                    <button 
                        type="button"
                        wire:click="toggleDezena(15)"
                        :style="getBallStyle(15)"
                        class="w-11 h-11 rounded-full text-sm font-black flex items-center justify-center transition-all duration-150 active:scale-90 cursor-pointer shadow-md"
                    >
                        15
                    </button>
                </div>

                <!-- Linha 4 Esquerda: 16 -->
                <div class="col-start-1 row-start-4">
                    <button 
                        type="button"
                        wire:click="toggleDezena(16)"
                        :style="getBallStyle(16)"
                        class="w-11 h-11 rounded-full text-sm font-black flex items-center justify-center transition-all duration-150 active:scale-90 cursor-pointer shadow-md"
                    >
                        16
                    </button>
                </div>

                <!-- Linha 4 Direita: 20 -->
                <div class="col-start-5 row-start-4">
                    <button 
                        type="button"
                        wire:click="toggleDezena(20)"
                        :style="getBallStyle(20)"
                        class="w-11 h-11 rounded-full text-sm font-black flex items-center justify-center transition-all duration-150 active:scale-90 cursor-pointer shadow-md"
                    >
                        20
                    </button>
                </div>

                <!-- Linha 5 da Moldura (21 a 25) -->
                @foreach([21, 22, 23, 24, 25] as $i)
                    <button 
                        type="button"
                        wire:click="toggleDezena({{ $i }})"
                        :style="getBallStyle({{ $i }})"
                        class="w-11 h-11 rounded-full text-sm font-black flex items-center justify-center transition-all duration-150 active:scale-90 cursor-pointer shadow-md"
                    >
                        {{ str_pad($i, 2, '0', STR_PAD_LEFT) }}
                    </button>
                @endforeach

            </div>

        </fieldset>

    </div>

    <!-- Legenda Completa de Temperaturas -->
    <div class="bg-[#162032] border border-slate-800/80 rounded-2xl p-3.5 shadow-xl">
        <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block mb-2 text-center">
            Legenda das Bolas
        </span>
        <div class="grid grid-cols-5 gap-1.5 text-center text-[10px] font-bold">
            <div class="flex flex-col items-center gap-1">
                <span class="w-5 h-5 rounded-full shadow" style="background: radial-gradient(circle at 35% 35%, #fca5a5, #dc2626 55%, #7f1d1d);"></span>
                <span class="text-red-400">Quente</span>
            </div>
            <div class="flex flex-col items-center gap-1">
                <span class="w-5 h-5 rounded-full shadow" style="background: radial-gradient(circle at 35% 35%, #fde68a, #d97706 55%, #78350f);"></span>
                <span class="text-amber-400">Morno</span>
            </div>
            <div class="flex flex-col items-center gap-1">
                <span class="w-5 h-5 rounded-full shadow" style="background: radial-gradient(circle at 35% 35%, #93c5fd, #1d4ed8 55%, #1e1b4b);"></span>
                <span class="text-blue-400">Frio</span>
            </div>
            <div class="flex flex-col items-center gap-1">
                <span class="w-5 h-5 rounded-full shadow ring-2 ring-emerald-500" style="background: radial-gradient(circle at 35% 35%, #86efac, #16a34a 55%, #14532d);"></span>
                <span class="text-emerald-400">Você</span>
            </div>
            <div class="flex flex-col items-center gap-1">
                <span class="w-5 h-5 rounded-full shadow opacity-70" style="background: radial-gradient(circle at 35% 35%, #d1d5db, #9ca3af 60%, #6b7280);"></span>
                <span class="text-slate-400">Outros</span>
            </div>
        </div>
    </div>

    <!-- Container de Toasts de Notificação acima do menu inferior -->
    <div class="fixed bottom-20 left-1/2 -translate-x-1/2 w-11/12 max-w-sm space-y-2 z-50 pointer-events-none">
        <template x-for="t in toasts" :key="t.id">
            <div 
                x-text="t.message"
                :class="t.type === 'error' ? 'bg-rose-600 text-white shadow-rose-600/30' : 'bg-sky-600 text-white shadow-sky-600/30'"
                class="p-3.5 rounded-xl font-semibold text-xs text-center shadow-lg transition-all transform animate-bounce pointer-events-auto"
            ></div>
        </template>
    </div>
</div>

