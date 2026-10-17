<?php

use App\Models\User;
use Webkul\Mcp\Servers\DevAgentServer;
use Webkul\Mcp\Servers\ErpAgentServer;
use Webkul\Mcp\Tools\Admin\Business\Accounts\InvoiceListTool;
use Webkul\Mcp\Tools\Admin\Business\BusinessDataCatalogTool;
use Webkul\Mcp\Tools\Admin\Business\Employees\EmployeeSummaryTool;
use Webkul\Mcp\Tools\Admin\Business\Partners\PartnerInsightsTool;
use Webkul\Mcp\Tools\Admin\Business\QueryBusinessDataTool;
use Webkul\Mcp\Tools\Admin\Business\Sales\SalesOrderInvoiceStatusTool;
use Webkul\Mcp\Tools\Admin\Business\Sales\SalesPipelineSummaryTool;
use Webkul\Mcp\Tools\Admin\Business\UniversalSearchRecordsTool;
use Webkul\Mcp\Tools\Dev\ListPluginsTool;
use Webkul\Mcp\Tools\Dev\PluginSummaryTool;

it('returns plugin list from the developer server tool', function () {
    $user = User::factory()->create();

    $response = DevAgentServer::actingAs($user)->tool(ListPluginsTool::class, [
        'query' => 'mcp',
        'limit' => 10,
    ]);

    $response
        ->assertOk()
        ->assertSee('plugins');
});

it('returns plugin summary error for unknown plugin', function () {
    $user = User::factory()->create();

    $response = DevAgentServer::actingAs($user)->tool(PluginSummaryTool::class, [
        'plugin' => 'plugin-that-does-not-exist',
    ]);

    $response->assertHasErrors();
});

it('returns invoice list payload for erp agent server', function () {
    $user = User::factory()->create();

    $response = ErpAgentServer::actingAs($user)->tool(InvoiceListTool::class);

    $response
        ->assertOk()
        ->assertSee('invoice_list');
});

it('returns sales pipeline payload for erp agent server', function () {
    $user = User::factory()->create();

    $response = ErpAgentServer::actingAs($user)->tool(SalesPipelineSummaryTool::class);

    $response
        ->assertOk()
        ->assertSee('sales_pipeline_summary');
});

it('returns employee summary payload for erp agent server', function () {
    $user = User::factory()->create();

    $response = ErpAgentServer::actingAs($user)->tool(EmployeeSummaryTool::class);

    $response
        ->assertOk()
        ->assertSee('employee_summary');
});

it('returns partner insights payload for erp agent server', function () {
    $user = User::factory()->create();

    $response = ErpAgentServer::actingAs($user)->tool(PartnerInsightsTool::class);

    $response
        ->assertOk()
        ->assertSee('partner_insights');
});

it('executes universal search records tool without error', function () {
    $user = User::factory()->create();

    $response = ErpAgentServer::actingAs($user)->tool(UniversalSearchRecordsTool::class, [
        'entity' => 'partners',
        'query'  => 'test',
        'limit'  => 5,
    ]);

    $response
        ->assertOk()
        ->assertSee('entity');
});

it('exposes the safe business data catalog', function () {
    $user = User::factory()->create();

    $response = ErpAgentServer::actingAs($user)->tool(BusinessDataCatalogTool::class, [
        'entity' => 'products',
    ]);

    $response
        ->assertOk()
        ->assertSee('products')
        ->assertSee('columns');
});

it('runs a generic read only business count', function () {
    $user = User::factory()->create();

    $response = ErpAgentServer::actingAs($user)->tool(QueryBusinessDataTool::class, [
        'entity'    => 'products',
        'operation' => 'count',
    ]);

    $response
        ->assertOk()
        ->assertSee('count')
        ->assertSee('value');
});

it('does not expose protected business columns', function () {
    $user = User::factory()->create();

    $response = ErpAgentServer::actingAs($user)->tool(QueryBusinessDataTool::class, [
        'entity'    => 'software_licenses',
        'operation' => 'list',
        'columns'   => ['serial_number'],
    ]);

    $response->assertHasErrors();
});

it('returns sales orders with their officially linked invoices', function () {
    $user = User::factory()->create();

    $response = ErpAgentServer::actingAs($user)->tool(SalesOrderInvoiceStatusTool::class, [
        'only_with_invoices' => true,
        'limit'              => 5,
    ]);

    $response
        ->assertOk()
        ->assertSee('orders')
        ->assertSee('unique_linked_invoice_count');
});
