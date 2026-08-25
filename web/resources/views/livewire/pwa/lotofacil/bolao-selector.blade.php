<div class="w-full flex justify-center px-4">
    @if(count($boloes) > 1)
        <div class="relative w-full max-w-sm">
            <select wire:model.live="bolaoSelecionadoId" class="block w-full appearance-none bg-[#162032] border border-slate-700 hover:border-slate-500 px-4 py-2 pr-8 rounded-lg shadow leading-tight focus:outline-none focus:shadow-outline text-white font-semibold text-sm">
                @foreach($boloes as $b)
                    <option value="{{ $b['id'] }}">{{ $b['nome'] }} (Concurso {{ $b['concurso_alvo'] ?? 'N/A' }})</option>
                @endforeach
            </select>
            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-slate-400">
                <svg class="fill-current h-4 w-4" xmlns="http://www.3.org/2000/svg" viewBox="0 0 20 20"><path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"/></svg>
            </div>
        </div>
    @elseif(count($boloes) == 1)
        <div class="text-white font-bold text-sm bg-[#162032] border border-slate-700 px-4 py-2 rounded-lg text-center shadow max-w-sm w-full truncate">
            {{ $boloes[0]['nome'] }}
        </div>
    @else
        <div class="text-slate-400 font-bold text-sm bg-[#162032] border border-slate-700 px-4 py-2 rounded-lg text-center shadow max-w-sm w-full truncate">
            Nenhum bolão ativo
        </div>
    @endif
</div>
