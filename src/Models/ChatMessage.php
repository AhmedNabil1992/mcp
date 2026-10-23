<?php

declare(strict_types=1);

namespace Webkul\Mcp\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Security\Models\User;

final class ChatMessage extends Model
{
    protected $table = 'mcp_chat_messages';

    protected $fillable = [
        'user_id',
        'role',
        'channel',
        'content',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
