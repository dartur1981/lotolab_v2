<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            Evolução do Ciclo
        </x-slot>
        <x-slot name="description">
            Acompanhe o preenchimento cronológico do ciclo concurso após concurso.
        </x-slot>

        <div style="margin-bottom: 1.5rem; max-width: 320px;">
            {{ $this->form }}
        </div>

        @if(empty($fileiras))
            <div class="p-4 text-sm text-gray-500 text-center">
                Nenhum dado disponível para este ciclo.
            </div>
        @else
            <div style="width: 100%; overflow-x: auto; background-color: var(--fi-bg); border-radius: 0.75rem; border: 1px solid var(--fi-border); padding: 0.5rem;">
                <table style="width: 100%; border-collapse: collapse; text-align: center; font-family: ui-sans-serif, system-ui, sans-serif; white-space: nowrap;">
                    <thead>
                        <tr>
                            <th style="padding: 1rem; color: var(--fi-text-subdued); font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 2px solid var(--fi-border); text-align: left;">
                                Concurso
                            </th>
                            @for($i = 1; $i <= 25; $i++)
                                <th style="padding: 1rem 0.25rem; color: var(--fi-text-subdued); font-size: 0.75rem; font-weight: 700; border-bottom: 2px solid var(--fi-border); width: 3.5%;">
                                    {{ str_pad($i, 2, '0', STR_PAD_LEFT) }}
                                </th>
                            @endfor
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($fileiras as $index => $fileira)
                            <tr style="border-bottom: 1px solid var(--fi-border); transition: background-color 0.2s ease;" onmouseover="this.style.backgroundColor='var(--fi-bg-subdued)'" onmouseout="this.style.backgroundColor='transparent'">
                                <td style="padding: 1rem; font-weight: 700; color: var(--fi-text); font-size: 0.875rem; text-align: left;">
                                    {{ $fileira['concurso'] }}
                                </td>
                                @for($i = 1; $i <= 25; $i++)
                                    @php
                                        $isAcumulado = in_array($i, $fileira['acumulado']);
                                    @endphp
                                    <td style="padding: 0.5rem 0.25rem;">
                                        @if($isAcumulado)
                                            <div style="margin: 0 auto; display: flex; align-items: center; justify-content: center; width: 32px; height: 32px; background-color: #10b981; color: #ffffff; border-radius: 9999px; font-weight: 800; font-size: 0.8rem; box-shadow: 0 4px 6px -1px rgba(16, 185, 129, 0.3), 0 2px 4px -1px rgba(16, 185, 129, 0.2);">
                                                {{ str_pad($i, 2, '0', STR_PAD_LEFT) }}
                                            </div>
                                        @else
                                            <div style="margin: 0 auto; display: flex; align-items: center; justify-content: center; width: 32px; height: 32px; color: var(--fi-text-subdued); opacity: 0.3; font-weight: 500; font-size: 0.8rem;">
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

            <div style="display: flex; flex-wrap: wrap; gap: 2rem; align-items: center; margin-top: 1.5rem; border-top: 1px dashed var(--fi-border); padding-top: 1.5rem; font-size: 0.875rem;">
                <span style="font-weight: 700; color: var(--fi-text); text-transform: uppercase; letter-spacing: 0.05em; font-size: 0.75rem;">Legenda:</span>
                <div style="display: flex; align-items: center; gap: 0.75rem; color: var(--fi-text-subdued);">
                    <div style="background-color: #10b981; color: white; border-radius: 9999px; display: inline-flex; align-items: center; justify-content: center; width: 28px; height: 28px; font-weight: 800; font-size: 0.75rem; box-shadow: 0 2px 4px rgba(16, 185, 129, 0.3);">01</div>
                    <span style="font-weight: 500;">Sorteada (Acumulado)</span>
                </div>
                <div style="display: flex; align-items: center; gap: 0.75rem; color: var(--fi-text-subdued);">
                    <div style="color: var(--fi-text-subdued); opacity: 0.4; display: inline-flex; align-items: center; justify-content: center; width: 28px; height: 28px; font-size: 0.75rem; font-weight: 500;">01</div>
                    <span style="font-weight: 500;">Pendente</span>
                </div>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
