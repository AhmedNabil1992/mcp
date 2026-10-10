<?php

namespace Webkul\Mcp;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Webkul\Mcp\Filament\Pages\AiAssistant;
use Webkul\PluginManager\Package;

class McpPlugin implements Plugin
{
    public function getId(): string
    {
        return 'mcp';
    }

    public static function make(): static
    {
        return app(static::class);
    }

    public function register(Panel $panel): void
    {
        if (! Package::isPluginInstalled($this->getId())) {
            return;
        }

        if ($panel->getId() === 'admin') {
            $panel->pages([
                AiAssistant::class,
            ]);
        }
    }

    public function boot(Panel $panel): void
    {
        //
    }
}
