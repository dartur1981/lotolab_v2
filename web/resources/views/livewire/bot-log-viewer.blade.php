<x-filament-widgets::widget class="fi-wi-bot-log">
    @if(!empty($logContent))
        <div wire:poll.1s>
            <x-filament::section>
                <x-slot name="heading">
                    Log de Execução do Robô
                </x-slot>
                
                <div class="p-4 bg-gray-900 rounded-xl shadow-inner custom-scrollbar" 
                     style="height: 15rem; overflow-y: auto; overflow-x: hidden;"
                     x-data="{}"
                     x-init="
                        const observer = new MutationObserver(() => {
                            $el.scrollTop = $el.scrollHeight;
                        });
                        observer.observe($el, { childList: true, subtree: true, characterData: true });
                     ">
                    <pre class="font-mono font-normal whitespace-pre-wrap break-all" style="font-size: 0.8rem; line-height: 1.2; color: #6b7280;">{{ $logContent }}</pre>
                </div>
            </x-filament::section>

            <style>
                .custom-scrollbar::-webkit-scrollbar {
                    width: 8px;
                }
                .custom-scrollbar::-webkit-scrollbar-track {
                    background: #1f2937; /* gray-800 */
                    border-radius: 8px;
                }
                .custom-scrollbar::-webkit-scrollbar-thumb {
                    background: #4b5563; /* gray-600 */
                    border-radius: 8px;
                }
                .custom-scrollbar::-webkit-scrollbar-thumb:hover {
                    background: #6b7280; /* gray-500 */
                }
            </style>
        </div>
    @else
        <div wire:poll.1s style="display:none;"></div>
    @endif
</x-filament-widgets::widget>
