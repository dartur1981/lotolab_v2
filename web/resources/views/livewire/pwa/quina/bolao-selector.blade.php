<div class="flex items-center justify-center gap-2 max-w-md mx-auto w-full">
    <div class="flex items-center justify-center gap-2 min-w-0">
        <span class="w-2.5 h-2.5 rounded-full bg-indigo-400 animate-pulse shrink-0"></span>
        <span class="text-xs font-black uppercase tracking-wider text-indigo-400 shrink-0">Quina:</span>
        @if(count($boloes) > 1)
            <select 
                wire:model.live="bolaoSelecionadoId" 
                class="bg-[#243044] text-white text-xs font-semibold rounded-lg px-2.5 py-1 border border-slate-700/80 outline-none truncate max-w-[220px]"
            >
                @foreach($boloes as $b)
                    <option value="{{ $b['id'] }}">{{ $b['nome'] }}</option>
                @endforeach
            </select>
        @elseif(count($boloes) === 1)
            <span class="text-xs font-bold text-white truncate max-w-[220px]">{{ $boloes[0]['nome'] }}</span>
        @else
            <span class="text-xs text-slate-400">Nenhum bolão ativo</span>
        @endif
    </div>
</div>
