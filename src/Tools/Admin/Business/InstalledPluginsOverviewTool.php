<?php

declare(strict_types=1);

namespace Webkul\Mcp\Tools\Admin\Business;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Schema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use Webkul\Mcp\Servers\ErpAgentServer;
use Webkul\PluginManager\Models\Plugin;

#[IsReadOnly]
#[IsIdempotent]
class InstalledPluginsOverviewTool extends Tool
{
    protected string $description = <<<'MARKDOWN'
        Discover which AureusERP plugins are installed and active and which MCP business tools are available.
        Use this before answering capability questions or when the user asks about a module that may not be installed.
    MARKDOWN;

    public function name(): string
    {
        return 'installed_plugins_overview';
    }

    public function handle(Request $request): Response
    {
        if (! Schema::hasTable('plugins')) {
            return Response::error('Plugin metadata table is unavailable.');
        }

        $plugins = Plugin::query()
            ->where('is_installed', true)
            ->orderBy('name')
            ->get(['name', 'summary', 'latest_version', 'is_active']);

        $tools = collect(ErpAgentServer::getRegisteredTools())
            ->map(function (string $toolClass): ?string {
                try {
                    return (string) (app($toolClass)->toArray()['name'] ?? class_basename($toolClass));
                } catch (\Throwable) {
                    return null;
                }
            })
            ->filter()
            ->values();

        return Response::json([
            'installed_plugins' => $plugins->map(fn (Plugin $plugin): array => [
                'name' => $plugin->name,
                'active' => (bool) $plugin->is_active,
                'version' => $plugin->latest_version,
                'summary' => $plugin->summary,
            ])->all(),
            'available_mcp_tools' => $tools->all(),
            'coverage_note' => 'A plugin can be installed even when it has no dedicated business metric yet. Use universal search only for its allowlisted entities.',
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
