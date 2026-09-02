<?php

namespace Webkul\Mcp\Filament\Pages;

use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Webkul\Mcp\Support\GeminiAssistantService;
use Webkul\Support\Enums\NavigationGroup;

class AiAssistant extends Page
{
    protected string $view = 'mcp::filament.pages.ai-assistant';

    protected static ?string $slug = 'ai-assistant';

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-sparkles';

    protected static ?int $navigationSort = 1;

    public static function getNavigationIcon(): string | BackedEnum | Htmlable | null
    {
        return 'heroicon-o-sparkles';
    }

    public string $message = '';

    /**
     * @var array<int, array{role: string, content: string, time: string}>
     */
    public array $messages = [];

    public bool $isLoading = false;

    public static function getNavigationLabel(): string
    {
        return __('المساعد الذكي (AI)');
    }

    public static function getNavigationGroup(): string|\UnitEnum
    {
        return NavigationGroup::Dashboard;
    }

    public function getTitle(): string
    {
        return __('المساعد الذكي للأعمال (Aureus AI Assistant)');
    }

    public function getHeading(): string
    {
        return __('المساعد الذكي (Aureus ERP AI)');
    }

    public function getSubheading(): ?string
    {
        return __('اسأل المساعد الذكي عن المبيعات، الحسابات، المخازن، الفواتير، الموظفين أو أي سجل في النظام.');
    }

    public function mount(): void
    {
        $gemini = app(GeminiAssistantService::class);

        if (! $gemini->isConfigured()) {
            $this->messages[] = [
                'role'    => 'assistant',
                'content' => "مرحباً بك! 👋\n\nيرجى العلم أن مفتاح `GEMINI_API_KEY` غير مفعل حالياً في ملف `.env`.\nبعد إضافة المفتاح سأتمكن من الإجابة على استفساراتك وتحليل بيانات الـ ERP في الوقت الفعلي.",
                'time'    => now()->format('H:i'),
            ];
        } else {
            $this->messages[] = [
                'role'    => 'assistant',
                'content' => "أهلاً بك! أنا مساعد AureusERP الذكي المدعوم بـ Google Gemini و MCP 🚀\n\nيمكنني مساعدتك في استعراض التقارير، البحث عن الفواتير أو الموظفين، تحليل أداء المبيعات، ومتابعة المخزون في أي وقت.\nكيف يمكنني مساعدتك اليوم؟",
                'time'    => now()->format('H:i'),
            ];
        }
    }

    public function askQuestion(string $question): void
    {
        $this->message = $question;
        $this->sendMessage();
    }

    public function sendMessage(): void
    {
        $input = trim($this->message);

        if ($input === '') {
            return;
        }

        $this->messages[] = [
            'role'    => 'user',
            'content' => $input,
            'time'    => now()->format('H:i'),
        ];

        $this->message = '';
        $this->isLoading = true;

        $history = array_map(fn ($m) => [
            'role'    => $m['role'],
            'content' => $m['content'],
        ], $this->messages);

        /** @var GeminiAssistantService $gemini */
        $gemini = app(GeminiAssistantService::class);

        $result = $gemini->chat($history);

        if (isset($result['error']) && ! empty($result['error'])) {
            $this->messages[] = [
                'role'    => 'assistant',
                'content' => "⚠️ **تنبيه:** {$result['error']}",
                'time'    => now()->format('H:i'),
            ];

            Notification::make()
                ->title(__('تعذر استكمال الرد من المساعد الذكي'))
                ->body($result['error'])
                ->danger()
                ->send();
        } else {
            $this->messages[] = [
                'role'    => 'assistant',
                'content' => $result['content'],
                'time'    => now()->format('H:i'),
            ];
        }

        $this->isLoading = false;
    }

    public function clearChat(): void
    {
        $this->messages = [];
        $this->mount();

        Notification::make()
            ->title(__('تم مسح سجل المحادثة بنجاح'))
            ->success()
            ->send();
    }
}
