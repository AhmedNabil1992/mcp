<x-filament-panels::page>
    <div class="flex flex-col h-[calc(100vh-14rem)] bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm overflow-hidden">
        {{-- Header Bar --}}
        <div class="px-6 py-4 bg-gray-50/80 dark:bg-gray-800/60 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-primary-600 to-indigo-500 flex items-center justify-center text-white shadow-md shadow-primary-500/20">
                    <x-filament::icon icon="heroicon-o-sparkles" class="w-6 h-6" />
                </div>
                <div>
                    <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        Aureus AI Assistant
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400">
                            MCP Connected
                        </span>
                    </h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">مدعوم بـ Google Gemini وموصول بأدوات الـ ERP الحية</p>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <x-filament::button
                    color="gray"
                    size="sm"
                    icon="heroicon-o-arrow-path"
                    wire:click="clearChat"
                    wire:loading.attr="disabled"
                >
                    محادثة جديدة
                </x-filament::button>
            </div>
        </div>

        {{-- Quick Prompts (Chips) --}}
        <div class="px-6 py-2.5 bg-gray-50/40 dark:bg-gray-800/30 border-b border-gray-100 dark:border-gray-800 flex items-center gap-2 overflow-x-auto text-xs scrollbar-none">
            <span class="font-medium text-gray-400 whitespace-nowrap">أسئلة مقترحة:</span>
            <button
                type="button"
                wire:click="askQuestion('ما هو ملخص أداء المبيعات وأحدث الطلبات؟')"
                class="px-3 py-1.5 rounded-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 hover:border-primary-500 hover:text-primary-600 dark:hover:border-primary-400 dark:hover:text-primary-400 transition-colors whitespace-nowrap text-gray-700 dark:text-gray-300 shadow-2xs"
            >
                📊 ملخص المبيعات
            </button>
            <button
                type="button"
                wire:click="askQuestion('ما هي الفواتير المتأخرة وإجمالي المستحقات؟')"
                class="px-3 py-1.5 rounded-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 hover:border-primary-500 hover:text-primary-600 dark:hover:border-primary-400 dark:hover:text-primary-400 transition-colors whitespace-nowrap text-gray-700 dark:text-gray-300 shadow-2xs"
            >
                💳 الفواتير المتأخرة والتحصيلات
            </button>
            <button
                type="button"
                wire:click="askQuestion('ما هي المنتجات التي اقتربت من النفاد وتحتاج إعادة طلب؟')"
                class="px-3 py-1.5 rounded-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 hover:border-primary-500 hover:text-primary-600 dark:hover:border-primary-400 dark:hover:text-primary-400 transition-colors whitespace-nowrap text-gray-700 dark:text-gray-300 shadow-2xs"
            >
                📦 نواقص المخزن والتنبيهات
            </button>
            <button
                type="button"
                wire:click="askQuestion('اعطني إحصائيات الموظفين وتوزيعهم على الأقسام')"
                class="px-3 py-1.5 rounded-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 hover:border-primary-500 hover:text-primary-600 dark:hover:border-primary-400 dark:hover:text-primary-400 transition-colors whitespace-nowrap text-gray-700 dark:text-gray-300 shadow-2xs"
            >
                👥 إحصائيات الموظفين والأقسام
            </button>
            <button
                type="button"
                wire:click="askQuestion('ما هو وضع العملاء المحتملين (Leads) ومصادرهم؟')"
                class="px-3 py-1.5 rounded-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 hover:border-primary-500 hover:text-primary-600 dark:hover:border-primary-400 dark:hover:text-primary-400 transition-colors whitespace-nowrap text-gray-700 dark:text-gray-300 shadow-2xs"
            >
                🧲 العملاء المحتملين (Leads)
            </button>
            <button
                type="button"
                wire:click="askQuestion('اعطني ملخص تراخيص البرامج والاشتراكات السحابية')"
                class="px-3 py-1.5 rounded-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 hover:border-primary-500 hover:text-primary-600 dark:hover:border-primary-400 dark:hover:text-primary-400 transition-colors whitespace-nowrap text-gray-700 dark:text-gray-300 shadow-2xs"
            >
                💻 التراخيص والأنظمة (Software)
            </button>
            <button
                type="button"
                wire:click="askQuestion('ما هي إحصائيات شبكات الواي فاي والكروت المصدرة؟')"
                class="px-3 py-1.5 rounded-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 hover:border-primary-500 hover:text-primary-600 dark:hover:border-primary-400 dark:hover:text-primary-400 transition-colors whitespace-nowrap text-gray-700 dark:text-gray-300 shadow-2xs"
            >
                📶 شبكات الواي فاي والكروت (WiFi)
            </button>
            <button
                type="button"
                wire:click="askQuestion('ما هي المشاريع النشطة والمهام المتأخرة؟')"
                class="px-3 py-1.5 rounded-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 hover:border-primary-500 hover:text-primary-600 dark:hover:border-primary-400 dark:hover:text-primary-400 transition-colors whitespace-nowrap text-gray-700 dark:text-gray-300 shadow-2xs"
            >
                🎯 المشاريع والمهام
            </button>
        </div>

        {{-- Chat Messages Scroll Area --}}
        <div
            id="chat-messages-container"
            class="flex-1 p-6 overflow-y-auto space-y-5 bg-gray-50 dark:bg-gray-950"
        >
            @foreach ($messages as $msg)
                @if ($msg['role'] === 'user')
                    <div class="flex items-start justify-end gap-3">
                        <div class="max-w-2xl px-4 py-3 rounded-2xl rounded-tr-xs shadow-sm text-sm leading-relaxed" style="background-color: #4f46e5 !important; color: #ffffff !important;">
                            <div class="whitespace-pre-wrap font-medium select-text" style="color: #ffffff !important;">{{ $msg['content'] }}</div>
                            <div class="text-[10px] mt-1.5 text-left" style="color: #e0e7ff !important;">{{ $msg['time'] }}</div>
                        </div>
                        <div class="w-8 h-8 rounded-full flex items-center justify-center shrink-0 text-xs font-bold" style="background-color: #e0e7ff !important; color: #3730a3 !important; border: 1px solid #c7d2fe !important;">
                            {{ auth()->user()?->name ? mb_substr(auth()->user()->name, 0, 1) : 'U' }}
                        </div>
                    </div>
                @else
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 rounded-xl bg-gradient-to-tr from-indigo-600 to-primary-600 text-white flex items-center justify-center shrink-0 shadow-sm">
                            <x-filament::icon icon="heroicon-o-sparkles" class="w-4 h-4" />
                        </div>
                        <div class="max-w-3xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 text-gray-900 dark:text-gray-100 px-5 py-4 rounded-2xl rounded-tl-xs shadow-2xs text-sm leading-relaxed ring-1 ring-gray-950/5 dark:ring-white/10">
                            <div class="prose prose-sm dark:prose-invert max-w-none break-words space-y-2 text-gray-800 dark:text-gray-200">
                                {!! Str::markdown($msg['content']) !!}
                            </div>
                            <div class="text-[10px] text-gray-400 dark:text-gray-500 mt-2 text-right">{{ $msg['time'] }}</div>
                        </div>
                    </div>
                @endif
            @endforeach

            {{-- Live Loading Indicator --}}
            <div wire:loading wire:target="sendMessage, askQuestion" class="flex items-start gap-3">
                <div class="w-8 h-8 rounded-xl bg-gradient-to-tr from-indigo-600 to-primary-600 text-white flex items-center justify-center shrink-0 animate-pulse">
                    <x-filament::icon icon="heroicon-o-sparkles" class="w-4 h-4" />
                </div>
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 px-4 py-3 rounded-2xl rounded-tl-xs shadow-2xs flex items-center gap-3 text-sm text-gray-700 dark:text-gray-300 ring-1 ring-gray-950/5 dark:ring-white/10">
                    <span class="inline-block w-2 h-2 rounded-full bg-indigo-600 animate-bounce"></span>
                    <span class="inline-block w-2 h-2 rounded-full bg-primary-600 animate-bounce [animation-delay:0.2s]"></span>
                    <span class="inline-block w-2 h-2 rounded-full bg-emerald-600 animate-bounce [animation-delay:0.4s]"></span>
                    <span class="text-xs font-medium">جاري استعلام بيانات الـ ERP والتفكير...</span>
                </div>
            </div>
        </div>

        {{-- Input Bar --}}
        <div class="p-4 bg-white dark:bg-gray-900 border-t border-gray-200 dark:border-gray-800">
            <form wire:submit="sendMessage" class="flex items-center gap-2">
                <div class="relative flex-1">
                    <input
                        type="text"
                        wire:model="message"
                        placeholder="اسأل المساعد الذكي عن أي شيء في نظام AureusERP..."
                        class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400"
                        wire:loading.attr="disabled"
                        autofocus
                    />
                </div>

                <x-filament::button
                    type="submit"
                    size="lg"
                    icon="heroicon-m-paper-airplane"
                    wire:loading.attr="disabled"
                    class="rounded-xl shadow-md"
                >
                    إرسال
                </x-filament::button>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('livewire:initialized', () => {
            const scrollBottom = () => {
                const container = document.getElementById('chat-messages-container');
                if (container) {
                    container.scrollTop = container.scrollHeight;
                }
            };
            scrollBottom();
            Livewire.hook('morph.updated', () => {
                scrollBottom();
            });
        });
    </script>
</x-filament-panels::page>
