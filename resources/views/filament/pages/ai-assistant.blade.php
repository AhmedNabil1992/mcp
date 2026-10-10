<x-filament-panels::page>
    <div class="mx-auto flex h-[calc(100vh-14rem)] w-full max-w-6xl flex-col overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-white/10 dark:bg-gray-900">
        <div class="flex items-center justify-between gap-4 border-b border-gray-200 bg-gray-50/80 px-6 py-4 dark:border-white/10 dark:bg-white/5">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-primary-600 text-white"><x-filament::icon icon="heroicon-o-sparkles" class="h-6 w-6" /></div>
                <div>
                    <h2 class="font-semibold text-gray-950 dark:text-white">{{ __('mcp::filament/pages/ai-assistant.heading') }}</h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('mcp::filament/pages/ai-assistant.connected') }}</p>
                </div>
            </div>
            <x-filament::button color="gray" size="sm" icon="heroicon-o-arrow-path" wire:click="clearChat">{{ __('mcp::filament/pages/ai-assistant.clear') }}</x-filament::button>
        </div>

        <div class="flex gap-2 overflow-x-auto border-b border-gray-100 px-6 py-3 text-xs dark:border-white/10">
            @foreach(__('mcp::filament/pages/ai-assistant.prompts') as $prompt)
                <button type="button" wire:click="askQuestion({{ Illuminate\Support\Js::from($prompt) }})" class="whitespace-nowrap rounded-full border border-gray-200 px-3 py-1.5 text-gray-700 transition hover:border-primary-500 hover:text-primary-600 dark:border-white/10 dark:text-gray-300">{{ $prompt }}</button>
            @endforeach
        </div>

        <div id="mcp-ai-messages" class="flex-1 space-y-5 overflow-y-auto bg-gray-50 p-6 dark:bg-gray-950">
            @foreach($messages as $index => $item)
                <div wire:key="mcp-ai-message-{{ $index }}" class="flex {{ $item['role'] === 'user' ? 'justify-end' : 'justify-start' }}">
                    <div class="max-w-[88%] rounded-2xl px-4 py-3 text-sm leading-7 {{ $item['role'] === 'user' ? 'bg-primary-600 text-white' : 'bg-white text-gray-900 shadow-sm ring-1 ring-gray-950/5 dark:bg-white/5 dark:text-white dark:ring-white/10' }}">
                        @if($item['role'] === 'assistant')
                            <div class="prose prose-sm max-w-none dark:prose-invert">{!! Str::markdown($item['content']) !!}</div>
                        @else
                            <div class="whitespace-pre-wrap">{{ $item['content'] }}</div>
                        @endif
                        <div class="mt-1 text-[10px] opacity-70">{{ $item['time'] }}</div>
                    </div>
                </div>
            @endforeach
            <div wire:loading wire:target="sendMessage,askQuestion" class="text-sm text-gray-500">{{ __('mcp::filament/pages/ai-assistant.thinking') }}</div>
        </div>

        <form wire:submit="sendMessage" class="border-t border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-gray-900">
            <div class="flex items-end gap-3">
                <textarea wire:model="message" rows="2" maxlength="4000" required class="block w-full resize-none rounded-xl border-0 bg-gray-50 px-4 py-3 text-sm text-gray-950 ring-1 ring-inset ring-gray-950/10 focus:ring-2 focus:ring-primary-600 dark:bg-white/5 dark:text-white dark:ring-white/10" placeholder="{{ __('mcp::filament/pages/ai-assistant.placeholder') }}"></textarea>
                <x-filament::button type="submit" icon="heroicon-o-paper-airplane" wire:loading.attr="disabled" wire:target="sendMessage">{{ __('mcp::filament/pages/ai-assistant.send') }}</x-filament::button>
            </div>
            <p class="mt-2 text-xs text-gray-500">{{ __('mcp::filament/pages/ai-assistant.read-only') }}</p>
        </form>
    </div>

    <script>
        document.addEventListener('livewire:initialized', () => {
            const scroll = () => { const element = document.getElementById('mcp-ai-messages'); if (element) element.scrollTop = element.scrollHeight; };
            scroll();
            Livewire.hook('morph.updated', scroll);
        });
    </script>
</x-filament-panels::page>
