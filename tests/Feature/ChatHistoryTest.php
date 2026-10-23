<?php

declare(strict_types=1);

use App\Models\User;
use Webkul\Mcp\Filament\Pages\AiAssistant;
use Webkul\Mcp\Support\ChatHistoryService;
use Webkul\Mcp\Support\GeminiAssistantService;

it('persists and isolates chat messages by user', function () {
    $firstUser = User::factory()->create();
    $secondUser = User::factory()->create();
    $history = app(ChatHistoryService::class);

    $history->append($firstUser->getKey(), 'user', 'First user question');
    $history->append($firstUser->getKey(), 'assistant', 'First user answer');
    $history->append($secondUser->getKey(), 'user', 'Second user question');

    expect($history->recentForUser($firstUser->getKey()))
        ->toHaveCount(2)
        ->sequence(
            fn ($message) => $message->content->toBe('First user question'),
            fn ($message) => $message->content->toBe('First user answer'),
        )
        ->and($history->recentForUser($secondUser->getKey()))
        ->toHaveCount(1)
        ->sequence(fn ($message) => $message->content->toBe('Second user question'));
});

it('loads persisted history when the assistant page is opened again', function () {
    $user = User::factory()->create();
    $history = app(ChatHistoryService::class);
    $history->append($user->getKey(), 'user', 'Persisted question');
    $history->append($user->getKey(), 'assistant', 'Persisted answer');
    $this->actingAs($user);

    $page = new AiAssistant;
    $page->mount(app(GeminiAssistantService::class), $history);

    expect($page->messages)
        ->toHaveCount(2)
        ->sequence(
            fn ($message) => $message->content->toBe('Persisted question'),
            fn ($message) => $message->content->toBe('Persisted answer'),
        );
});

it('clears only the selected user history', function () {
    $firstUser = User::factory()->create();
    $secondUser = User::factory()->create();
    $history = app(ChatHistoryService::class);
    $history->append($firstUser->getKey(), 'user', 'Delete me');
    $history->append($secondUser->getKey(), 'user', 'Keep me');

    $history->clearForUser($firstUser->getKey());

    expect($history->recentForUser($firstUser->getKey()))->toBeEmpty()
        ->and($history->recentForUser($secondUser->getKey()))->toHaveCount(1);
});
