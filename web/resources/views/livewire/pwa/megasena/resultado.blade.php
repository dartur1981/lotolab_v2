<div class="space-y-4 py-2">

    <div class="px-2">
        <h2 class="text-xl font-black text-white">Último Resultado Oficial</h2>
        <p class="text-xs text-slate-400 mt-0.5">Resultado da Mega-Sena da Caixa Econômica Federal</p>
    </div>

    @if($ultimoSorteio)
        <div class="bg-gradient-to-br from-[#162032] to-[#1e2d44] border border-slate-700/80 rounded-2xl p-5 shadow-xl text-center">
            <div class="flex items-center justify-between mb-4 text-xs">
                <span class="text-emerald-400 font-bold uppercase tracking-wider">Concurso {{ $ultimoSorteio['concurso'] }}</span>
                <span class="text-slate-400">{{ $ultimoSorteio['data'] }}</span>
            </div>

            <span class="text-xs text-slate-300 block mb-3 font-semibold uppercase tracking-wider">Dezenas Sorteadas</span>
            <div class="flex justify-center flex-wrap gap-2 mb-2">
                @foreach($ultimoSorteio['dezenas'] as $d)
                    <span class="inline-flex items-center justify-center w-11 h-11 rounded-full bg-gradient-to-br from-emerald-400 to-emerald-600 text-white font-black text-base shadow-lg shadow-emerald-900/60 ring-2 ring-emerald-300/40">
                        {{ str_pad($d, 2, '0', STR_PAD_LEFT) }}
                    </span>
                @endforeach
            </div>
        </div>
    @else
        <div class="bg-[#162032] border border-slate-800 rounded-2xl p-8 text-center text-slate-400 text-xs">
            Nenhum resultado registrado ainda. Importe o histórico no painel administrativo.
        </div>
    @endif

</div>
