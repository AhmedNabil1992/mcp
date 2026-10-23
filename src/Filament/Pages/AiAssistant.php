<?php

declare(strict_types=1);

namespace Webkul\Mcp\Filament\Pages;

use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Gate;
use Throwable;
use Webkul\Mcp\Support\ChatHistoryService;
use Webkul\Mcp\Support\GeminiAssistantService;
use Webkul\Support\Enums\NavigationGroup;

class AiAssistant extends Page
{
    use HasPageShield;

    private const AI_HISTORY_LIMIT = 50;

    protected string $view = 'mcp::filament.pages.ai-assistant';

    protected static ?string $slug = 'ai-assistant';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-sparkles';

    protected static ?int $navigationSort = 1;

    public string $message = '';

    /** @var array<int, array{id:int|string, role:string, content:string, time:string}> */
    public array $messages = [];

    protected static function getPagePermission(): ?string
    {
        return 'page_mcp_ai_assistant';
    }

    public static function getNavigationLabel(): string
    {
        return __('mcp::filament/pages/ai-assistant.navigation');
    }

    public static function getNavigationGroup(): string|\UnitEnum
    {
        return NavigationGroup::Help;
    }

    public function getTitle(): string|Htmlable
    {
        return __('mcp::filament/pages/ai-assistant.title');
    }

    public function getSubheading(): ?string
    {
        return __('mcp::filament/pages/ai-assistant.subheading');
    }

    public function mount(GeminiAssistantService $gemini, ChatHistoryService $history): void
    {
        $this->messages = $history->recentForUser($this->authenticatedUserId());

        if ($this->messages !== []) {
            return;
        }

        $this->messages[] = [
            'id'      => 'welcome',
            'role'    => 'assistant',
            'content' => $gemini->isConfigured()
                ? __('mcp::filament/pages/ai-assistant.welcome.ready')
                : __('mcp::filament/pages/ai-assistant.welcome.missing-key'),
            'time' => now()->format('H:i'),
        ];
    }

    public function askQuestion(string $question): void
    {
        $this->message = $question;
        $this->sendMessage();
    }

    public function sendMessage(): void
    {
        Gate::authorize('page_mcp_ai_assistant');
        $input = mb_substr(trim($this->message), 0, 4000);

        if ($input === '') {
            return;
        }

        $historyService = app(ChatHistoryService::class);
        $userId = $this->authenticatedUserId();
        $this->messages[] = $historyService->append($userId, 'user', $input);
        $this->message = '';

        try {
            $history = array_map(fn (array $item): array => [
                'role'    => $item['role'],
                'content' => $item['content'],
            ], array_slice($this->messages, -self::AI_HISTORY_LIMIT));
            $result = app(GeminiAssistantService::class)->chat($history);

            if (filled($result['error'] ?? null)) {
                throw new \RuntimeException((string) $result['error']);
            }

            $this->messages[] = $historyService->append(
                $userId,
                'assistant',
                (string) ($result['content'] ?? ''),
            );
        } catch (Throwable $exception) {
            report($exception);
            Notification::make()->danger()
                ->title(__('mcp::filament/pages/ai-assistant.errors.request-failed'))
                ->body($exception->getMessage())
                ->send();
        }
    }

    public function clearChat(): void
    {
        Gate::authorize('page_mcp_ai_assistant');
        app(ChatHistoryService::class)->clearForUser($this->authenticatedUserId());
        $this->messages = [];
        $this->mount(app(GeminiAssistantService::class), app(ChatHistoryService::class));
    }

    /** @return array<string, mixed> */
    public function createVoiceSession(): array
    {
        Gate::authorize('page_mcp_ai_assistant');

        return app(GeminiAssistantService::class)->createLiveSession();
    }

    public function executeVoiceTool(string $name, array $arguments = []): mixed
    {
        Gate::authorize('page_mcp_ai_assistant');

        if (! preg_match('/^[a-z0-9_\-]+$/i', $name)) {
            throw new \InvalidArgumentException('Invalid tool name.');
        }

        return app(GeminiAssistantService::class)->executeRegisteredTool($name, $arguments);
    }

    public function recordVoiceTurn(string $userTranscript, string $assistantTranscript): void
    {
        Gate::authorize('page_mcp_ai_assistant');

        $userTranscript = trim($userTranscript);
        $assistantTranscript = trim($assistantTranscript);

        if ($userTranscript !== '') {
            $this->messages[] = app(ChatHistoryService::class)->append(
                $this->authenticatedUserId(),
                'user',
                mb_substr($userTranscript, 0, 4000),
                'voice',
            );
        }

        if ($assistantTranscript !== '') {
            $this->messages[] = app(ChatHistoryService::class)->append(
                $this->authenticatedUserId(),
                'assistant',
                mb_substr($assistantTranscript, 0, 12000),
                'voice',
            );
        }
    }

    private function authenticatedUserId(): int
    {
        $userId = auth()->id();

        if ($userId === null) {
            throw new AuthenticationException;
        }

        return (int) $userId;
    }
}
