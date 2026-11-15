<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            Evolução dos Ciclos (Mega-Sena)
        </x-slot>
        <x-slot name="description">
            Acompanhe o preenchimento cronológico das 60 dezenas da Mega-Sena concurso a concurso.
        </x-slot>

        <div style="margin-bottom: 1.5rem; max-width: 340px;">
            {{ $this->form }}
        </div>

        @if(empty($fileiras))
            <div class="p-4 text-sm text-gray-500 text-center">
                Nenhum ciclo da Mega-Sena cadastrado ou histórico ainda não importado.
            </div>
        @else
            <div style="width: 100%; overflow-x: auto; background-color: var(--fi-bg); border-radius: 0.75rem; border: 1px solid var(--fi-border); padding: 0.5rem;">
                <table style="width: 100%; border-collapse: collapse; text-align: center; font-family: ui-sans-serif, system-ui, sans-serif; white-space: nowrap;">
                    <thead>
                        <tr>
                            <th style="padding: 1rem; color: var(--fi-text-subdued); font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 2px solid var(--fi-border); text-align: left;">
                                Concurso
                            </th>
                            @for($i = 1; $i <= 60; $i++)
                                <th style="padding: 0.75rem 0.2rem; color: var(--fi-text-subdued); font-size: 0.75rem; font-weight: 700; border-bottom: 2px solid var(--fi-border);">
                                    {{ str_pad($i, 2, '0', STR_PAD_LEFT) }}
                                </th>
                            @endfor
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($fileiras as $index => $fileira)
                            <tr style="border-bottom: 1px solid var(--fi-border); transition: background-color 0.2s ease;">
                                <td style="padding: 0.75rem 1rem; font-weight: 700; color: var(--fi-text); font-size: 0.875rem; text-align: left;">
                                    {{ $fileira['concurso'] }}
                                </td>
                                @for($i = 1; $i <= 60; $i++)
                                    @php
                                        $isAcumulado = in_array($i, $fileira['acumulado']);
                                        $isSorteioAtual = in_array($i, $fileira['dezenas_sorteio']);
                                        $isRepetida = $isSorteioAtual && in_array($i, $fileira['dezenas_anteriores'] ?? []);
                                    @endphp
                                    <td style="padding: 0.4rem 0.15rem;">
                                        @if($isRepetida)
                                            <div style="margin: 0 auto; display: flex; align-items: center; justify-content: center; width: 26px; height: 26px; background-color: #f97316; color: #ffffff; border-radius: 9999px; font-weight: 800; font-size: 0.75rem;">
                                                {{ str_pad($i, 2, '0', STR_PAD_LEFT) }}
                                            </div>
                                        @elseif($isSorteioAtual)
                                            <div style="margin: 0 auto; display: flex; align-items: center; justify-content: center; width: 26px; height: 26px; background-color: #209869; color: #ffffff; border-radius: 9999px; font-weight: 800; font-size: 0.75rem;">
                                                {{ str_pad($i, 2, '0', STR_PAD_LEFT) }}
                                            </div>
                                        @elseif($isAcumulado)
                                            <div style="margin: 0 auto; display: flex; align-items: center; justify-content: center; width: 26px; height: 26px; background-color: #059669; color: #ffffff; border-radius: 9999px; font-weight: 800; font-size: 0.75rem; opacity: 0.7;">
                                                {{ str_pad($i, 2, '0', STR_PAD_LEFT) }}
                                            </div>
                                        @else
                                            <div style="margin: 0 auto; display: flex; align-items: center; justify-content: center; width: 26px; height: 26px; color: var(--fi-text-subdued); opacity: 0.25; font-weight: 500; font-size: 0.75rem;">
                                                {{ str_pad($i, 2, '0', STR_PAD_LEFT) }}
                                            </div>
                                        @endif
                                    </td>
                                @endfor
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
