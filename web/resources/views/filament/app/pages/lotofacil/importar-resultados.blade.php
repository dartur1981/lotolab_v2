<x-filament-panels::page>
    <form wire:submit="save">
        {{ $this->form }}
    </form>

    @if ($processLogs)
        <div class="mt-8" @if($isProcessing) wire:poll.2s="updateLogs" @endif>
            <x-filament::section>
                <x-slot name="heading">
                    Log em tempo real
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
                    <pre class="font-mono font-normal whitespace-pre-wrap break-all" style="font-size: 0.8rem; line-height: 1.2; color: #6b7280;">{{ $processLogs }}</pre>
                </div>
            </x-filament::section>
        </div>
    @endif

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
</x-filament-panels::page>
