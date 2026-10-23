<?php

declare(strict_types=1);

namespace Webkul\Mcp\Support;

use Webkul\Mcp\Models\ChatMessage;

final class ChatHistoryService
{
    /**
     * @return array<int, array{id:int, role:string, content:string, time:string}>
     */
    public function recentForUser(int $userId, int $limit = 200): array
    {
        return ChatMessage::query()
            ->where('user_id', $userId)
            ->latest('id')
            ->limit(min(max($limit, 1), 500))
            ->get()
            ->reverse()
            ->map(fn (ChatMessage $message): array => $this->toDisplayMessage($message))
            ->values()
            ->all();
    }

    /** @return array{id:int, role:string, content:string, time:string} */
    public function append(int $userId, string $role, string $content, string $channel = 'text'): array
    {
        if (! in_array($role, ['user', 'assistant'], true)) {
            throw new \InvalidArgumentException("Unsupported chat role [{$role}].");
        }

        if (! in_array($channel, ['text', 'voice'], true)) {
            throw new \InvalidArgumentException("Unsupported chat channel [{$channel}].");
        }

        $message = ChatMessage::query()->create([
            'user_id' => $userId,
            'role'    => $role,
            'channel' => $channel,
            'content' => $content,
        ]);

        return $this->toDisplayMessage($message);
    }

    public function clearForUser(int $userId): void
    {
        ChatMessage::query()->where('user_id', $userId)->delete();
    }

    /** @return array{id:int, role:string, content:string, time:string} */
    private function toDisplayMessage(ChatMessage $message): array
    {
        return [
            'id'      => (int) $message->id,
            'role'    => (string) $message->role,
            'content' => (string) $message->content,
            'time'    => $message->created_at?->format('H:i') ?? now()->format('H:i'),
        ];
    }
}
