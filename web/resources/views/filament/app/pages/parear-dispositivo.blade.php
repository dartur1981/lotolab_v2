<x-filament-panels::page>
    <div style="display: flex; flex-direction: column; gap: 1.25rem; max-width: 960px;">

        {{-- Card Principal: Assistente Passo a Passo (Opção 2) --}}
        <div style="background: #111827; border: 1px solid #1f2937; border-radius: 0.875rem; padding: 1.25rem; display: flex; flex-direction: column; gap: 1.25rem;">

            {{-- Navegação das 3 Etapas --}}
            <div style="display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 0.65rem;">
                @php
                    $etapas = [
                        1 => ['titulo' => '1. Parear', 'sub' => 'Código 6 dígitos'],
                        2 => ['titulo' => '2. Conectar', 'sub' => 'Porta de conexão'],
                        3 => ['titulo' => '3. Validar', 'sub' => 'Teste do robô'],
                    ];
                @endphp

                @foreach ($etapas as $num => $info)
                    @php
                        $ativo = ($step === $num);
                        $concluido = ($step > $num);
                        $bg = $ativo ? 'rgba(16, 185, 129, 0.16)' : ($concluido ? 'rgba(56, 189, 248, 0.1)' : '#0b1220');
                        $border = $ativo ? 'rgba(16, 185, 129, 0.55)' : ($concluido ? 'rgba(56, 189, 248, 0.35)' : '#1f2937');
                        $color = $ativo ? '#34d399' : ($concluido ? '#38bdf8' : '#9ca3af');
                    @endphp
                    <button
                        type="button"
                        wire:click="irParaPasso({{ $num }})"
                        style="padding: 0.75rem 0.85rem; border-radius: 0.625rem; border: 1px solid {{ $border }}; background: {{ $bg }}; color: {{ $color }}; cursor: pointer; text-align: center; transition: filter 150ms ease;"
                        onmouseover="this.style.filter='brightness(1.12)'"
                        onmouseout="this.style.filter='none'"
                    >
                        <div style="font-size: 0.875rem; font-weight: 800;">{{ $info['titulo'] }}</div>
                        <div style="font-size: 0.72rem; opacity: 0.8; margin-top: 0.15rem;">{{ $info['sub'] }}</div>
                    </button>
                @endforeach
            </div>

            {{-- ETAPA 1: PAREAR --}}
            @if ($step === 1)
                <div style="display: flex; flex-direction: column; gap: 1rem;">
                    <div style="background: rgba(56, 189, 248, 0.08); border: 1px solid rgba(56, 189, 248, 0.25); border-radius: 0.625rem; padding: 0.85rem 1rem; font-size: 0.825rem; color: #cbd5e1; line-height: 1.5;">
                        No celular Android, abra: <strong style="color: #f8fafc;">Configurações → Opções do Desenvolvedor → Depuração por Wi-Fi → Parear dispositivo com código de pareamento</strong> e preencha os dados exibidos na tela.
                    </div>

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); gap: 0.85rem;">
                        <div>
                            <label style="display: block; font-size: 0.75rem; font-weight: 600; color: #9ca3af; margin-bottom: 0.35rem;">IP do Celular</label>
                            <input
                                type="text"
                                wire:model.defer="ip"
                                placeholder="192.168.0.220"
                                style="width: 100%; padding: 0.6rem 0.75rem; border-radius: 0.5rem; background: #0b1220; border: 1px solid #374151; color: #f9fafb; font-family: ui-monospace, monospace; font-size: 0.875rem;"
                            />
                        </div>

                        <div>
                            <label style="display: block; font-size: 0.75rem; font-weight: 600; color: #9ca3af; margin-bottom: 0.35rem;">Porta de Pareamento</label>
                            <input
                                type="text"
                                wire:model.defer="portaPareamento"
                                placeholder="Ex: 41829"
                                style="width: 100%; padding: 0.6rem 0.75rem; border-radius: 0.5rem; background: #0b1220; border: 1px solid #374151; color: #f9fafb; font-family: ui-monospace, monospace; font-size: 0.875rem;"
                            />
                        </div>

                        <div>
                            <label style="display: block; font-size: 0.75rem; font-weight: 600; color: #9ca3af; margin-bottom: 0.35rem;">Código de Pareamento (6 dígitos)</label>
                            <input
                                type="text"
                                wire:model.defer="codigoPareamento"
                                placeholder="Ex: 482910"
                                maxlength="10"
                                style="width: 100%; padding: 0.6rem 0.75rem; border-radius: 0.5rem; background: #0b1220; border: 1px solid #374151; color: #f9fafb; font-family: ui-monospace, monospace; font-size: 0.95rem; letter-spacing: 0.15em;"
                            />
                        </div>
                    </div>

                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 0.75rem; flex-wrap: wrap; padding-top: 0.25rem;">
                        <button
                            type="button"
                            wire:click="irParaPasso(2)"
                            style="padding: 0.55rem 0.9rem; border-radius: 0.5rem; background: transparent; border: 1px solid #374151; color: #9ca3af; font-size: 0.8rem; font-weight: 600; cursor: pointer;"
                        >
                            Já pareei antes — pular para Etapa 2 →
                        </button>

                        <button
                            type="button"
                            wire:click="parear"
                            wire:loading.attr="disabled"
                            style="padding: 0.6rem 1.25rem; border-radius: 0.5rem; background: #059669; border: none; color: #ffffff; font-size: 0.85rem; font-weight: 700; cursor: pointer;"
                        >
                            <span wire:loading.remove wire:target="parear">Parear e Avançar →</span>
                            <span wire:loading wire:target="parear">Pareando...</span>
                        </button>
                    </div>
                </div>
            @endif

            {{-- ETAPA 2: CONECTAR --}}
            @if ($step === 2)
                <div style="display: flex; flex-direction: column; gap: 1rem;">
                    <div style="background: rgba(56, 189, 248, 0.08); border: 1px solid rgba(56, 189, 248, 0.25); border-radius: 0.625rem; padding: 0.85rem 1rem; font-size: 0.825rem; color: #cbd5e1; line-height: 1.5;">
                        Agora informe a <strong style="color: #f8fafc;">Porta de Conexão</strong> exibida na tela principal de <strong style="color: #f8fafc;">Depuração por Wi-Fi</strong> (em "Endereço IP e porta" — essa porta é diferente da porta de pareamento).
                    </div>

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 0.85rem;">
                        <div>
                            <label style="display: block; font-size: 0.75rem; font-weight: 600; color: #9ca3af; margin-bottom: 0.35rem;">IP do Celular</label>
                            <input
                                type="text"
                                wire:model.defer="ip"
                                placeholder="192.168.0.220"
                                style="width: 100%; padding: 0.6rem 0.75rem; border-radius: 0.5rem; background: #0b1220; border: 1px solid #374151; color: #f9fafb; font-family: ui-monospace, monospace; font-size: 0.875rem;"
                            />
                        </div>

                        <div>
                            <label style="display: block; font-size: 0.75rem; font-weight: 600; color: #9ca3af; margin-bottom: 0.35rem;">Porta de Conexão</label>
                            <input
                                type="text"
                                wire:model.defer="portaConexao"
                                placeholder="Ex: 36451"
                                style="width: 100%; padding: 0.6rem 0.75rem; border-radius: 0.5rem; background: #0b1220; border: 1px solid #374151; color: #f9fafb; font-family: ui-monospace, monospace; font-size: 0.875rem;"
                            />
                        </div>
                    </div>

                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 0.75rem; flex-wrap: wrap; padding-top: 0.25rem;">
                        <button
                            type="button"
                            wire:click="irParaPasso(1)"
                            style="padding: 0.55rem 0.9rem; border-radius: 0.5rem; background: transparent; border: 1px solid #374151; color: #9ca3af; font-size: 0.8rem; font-weight: 600; cursor: pointer;"
                        >
                            ← Voltar para Etapa 1
                        </button>

                        <button
                            type="button"
                            wire:click="conectar"
                            wire:loading.attr="disabled"
                            style="padding: 0.6rem 1.25rem; border-radius: 0.5rem; background: #0284c7; border: none; color: #ffffff; font-size: 0.85rem; font-weight: 700; cursor: pointer;"
                        >
                            <span wire:loading.remove wire:target="conectar">Conectar e Validar →</span>
                            <span wire:loading wire:target="conectar">Conectando...</span>
                        </button>
                    </div>
                </div>
            @endif

            {{-- ETAPA 3: VALIDAR --}}
            @if ($step === 3)
                <div style="display: flex; flex-direction: column; gap: 1rem;">
                    @if ($ultimoTeste && !empty($ultimoTeste['sucesso']))
                        <div style="background: rgba(16, 185, 129, 0.12); border: 1px solid rgba(16, 185, 129, 0.45); border-radius: 0.75rem; padding: 1rem 1.15rem; display: flex; align-items: center; justify-content: space-between; gap: 1rem; flex-wrap: wrap;">
                            <div style="display: flex; flex-direction: column; gap: 0.25rem;">
                                <div style="font-size: 0.95rem; font-weight: 800; color: #34d399;">
                                    ✓ Aparelho pronto para o Robô!
                                </div>
                                <div style="font-size: 0.8rem; font-family: ui-monospace, monospace; color: #d1d5db;">
                                    {{ $ultimoTeste['serial'] ?? '' }} • Modelo: {{ $ultimoTeste['model'] ?? '-' }}
                                    @if (!empty($ultimoTeste['width']) && !empty($ultimoTeste['height']))
                                        • Tela: {{ $ultimoTeste['width'] }}×{{ $ultimoTeste['height'] }}
                                    @endif
                                    • {{ !empty($ultimoTeste['screen_on']) ? 'Tela ligada' : 'Tela desligada' }}
                                </div>
                            </div>
                        </div>
                    @else
                        <div style="background: rgba(245, 158, 11, 0.1); border: 1px solid rgba(245, 158, 11, 0.4); border-radius: 0.75rem; padding: 1rem 1.15rem; font-size: 0.85rem; color: #fde68a;">
                            Clique em <strong>Testar Comunicação do Robô</strong> abaixo para validar o dispositivo conectado.
                        </div>
                    @endif

                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 0.75rem; flex-wrap: wrap;">
                        <button
                            type="button"
                            wire:click="irParaPasso(1)"
                            style="padding: 0.55rem 0.9rem; border-radius: 0.5rem; background: transparent; border: 1px solid #374151; color: #9ca3af; font-size: 0.8rem; font-weight: 600; cursor: pointer;"
                        >
                            Novo Pareamento
                        </button>

                        <button
                            type="button"
                            wire:click="testar"
                            wire:loading.attr="disabled"
                            style="padding: 0.6rem 1.25rem; border-radius: 0.5rem; background: #059669; border: none; color: #ffffff; font-size: 0.85rem; font-weight: 700; cursor: pointer;"
                        >
                            <span wire:loading.remove wire:target="testar">Testar Comunicação do Robô</span>
                            <span wire:loading wire:target="testar">Testando...</span>
                        </button>
                    </div>
                </div>
            @endif

            {{-- Mini Console de Retorno do ADB --}}
            <div style="background: #070b14; border: 1px solid {{ $consoleErro ? 'rgba(239, 68, 68, 0.45)' : '#1f2937' }}; border-radius: 0.5rem; padding: 0.7rem 0.9rem; font-family: ui-monospace, monospace; font-size: 0.78rem; color: {{ $consoleErro ? '#fca5a5' : '#9ca3af' }}; white-space: pre-wrap;">&gt; {{ $consoleOutput }}</div>
        </div>

        {{-- Card de Dispositivos Conectados no Container Python --}}
        <div style="background: #111827; border: 1px solid #1f2937; border-radius: 0.875rem; padding: 1rem 1.25rem; display: flex; flex-direction: column; gap: 0.85rem;">
            <div style="display: flex; align-items: center; justify-content: space-between; gap: 0.75rem; flex-wrap: wrap;">
                <div style="font-size: 0.85rem; font-weight: 700; color: #f3f4f6;">
                    Dispositivos Conectados no ADB ({{ count($devices) }})
                </div>
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <button
                        type="button"
                        wire:click="atualizarDispositivos"
                        wire:loading.attr="disabled"
                        style="padding: 0.35rem 0.75rem; border-radius: 0.45rem; background: #1f2937; border: 1px solid #374151; color: #e5e7eb; font-size: 0.75rem; font-weight: 600; cursor: pointer;"
                    >
                        Atualizar lista
                    </button>
                    @if (!empty($devices))
                        <button
                            type="button"
                            wire:click="desconectar"
                            style="padding: 0.35rem 0.75rem; border-radius: 0.45rem; background: rgba(239, 68, 68, 0.12); border: 1px solid rgba(239, 68, 68, 0.35); color: #fca5a5; font-size: 0.75rem; font-weight: 600; cursor: pointer;"
                        >
                            Desconectar todos
                        </button>
                    @endif
                </div>
            </div>

            @if (empty($devices))
                <div style="font-size: 0.8rem; color: #6b7280; padding: 0.35rem 0;">
                    Nenhum dispositivo conectado no momento. Use o assistente acima para parear e conectar seu celular.
                </div>
            @else
                <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                    @foreach ($devices as $dev)
                        @php
                            $online = (($dev['status'] ?? '') === 'device');
                        @endphp
                        <div style="display: flex; align-items: center; justify-content: space-between; gap: 0.75rem; flex-wrap: wrap; padding: 0.65rem 0.85rem; border-radius: 0.5rem; background: #0b1220; border: 1px solid #1f2937;">
                            <div style="display: flex; align-items: center; gap: 0.65rem; flex-wrap: wrap;">
                                <span style="width: 0.55rem; height: 0.55rem; border-radius: 9999px; background: {{ $online ? '#34d399' : '#fbbf24' }};"></span>
                                <span style="font-family: ui-monospace, monospace; font-size: 0.825rem; font-weight: 700; color: #f9fafb;">{{ $dev['serial'] ?? '-' }}</span>
                                @if (!empty($dev['model']))
                                    <span style="font-size: 0.75rem; color: #9ca3af;">({{ $dev['model'] }})</span>
                                @endif
                                <span style="font-size: 0.7rem; font-weight: 700; padding: 0.15rem 0.5rem; border-radius: 9999px; background: {{ $online ? 'rgba(16, 185, 129, 0.15)' : 'rgba(245, 158, 11, 0.15)' }}; color: {{ $online ? '#34d399' : '#fbbf24' }};">
                                    {{ $online ? 'ONLINE' : strtoupper($dev['status'] ?? 'DESCONHECIDO') }}
                                </span>
                            </div>

                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                                <button
                                    type="button"
                                    wire:click="testar('{{ $dev['serial'] ?? '' }}')"
                                    style="padding: 0.3rem 0.65rem; border-radius: 0.4rem; background: rgba(56, 189, 248, 0.12); border: 1px solid rgba(56, 189, 248, 0.35); color: #38bdf8; font-size: 0.75rem; font-weight: 600; cursor: pointer;"
                                >
                                    Testar
                                </button>
                                <button
                                    type="button"
                                    wire:click="desconectar('{{ $dev['serial'] ?? '' }}')"
                                    style="padding: 0.3rem 0.65rem; border-radius: 0.4rem; background: rgba(239, 68, 68, 0.12); border: 1px solid rgba(239, 68, 68, 0.35); color: #fca5a5; font-size: 0.75rem; font-weight: 600; cursor: pointer;"
                                >
                                    Desconectar
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-filament-panels::page>
