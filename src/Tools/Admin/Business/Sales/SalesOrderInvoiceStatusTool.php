<?php

declare(strict_types=1);

namespace Webkul\Mcp\Tools\Admin\Business\Sales;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use Webkul\Mcp\Support\BusinessToolService;
use Webkul\PluginManager\Package;

#[IsReadOnly]
#[IsIdempotent]
final class SalesOrderInvoiceStatusTool extends Tool
{
    protected string $description = <<<'MARKDOWN'
        Return sales orders with the invoices officially linked through sales_order_invoices.
        Includes order state, invoice status, customer, salesperson, every linked invoice,
        invoice posting/payment states, totals, residual amounts and due dates.
        Use this for any question connecting a sales order to its invoices or payment status.
    MARKDOWN;

    public function __construct(private readonly BusinessToolService $businessToolService) {}

    public function name(): string
    {
        return 'sales_order_invoice_status';
    }

    public function shouldRegister(Request $request): bool
    {
        return Package::isPluginInstalled('sales') && Package::isPluginInstalled('accounts');
    }

    public function handle(Request $request): Response
    {
        $filters = $request->validate([
            'order_query'        => ['nullable', 'string', 'max:200'],
            'order_state'        => ['nullable', 'string', 'in:draft,sent,sale,cancel'],
            'invoice_status'     => ['nullable', 'string', 'in:up_selling,invoiced,to_invoice,no'],
            'payment_state'      => ['nullable', 'string', 'in:not_paid,in_payment,paid,partial,reversed,blocked,invoicing_legacy'],
            'date_from'          => ['nullable', 'date_format:Y-m-d'],
            'date_to'            => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'only_with_invoices' => ['nullable', 'boolean'],
            'limit'              => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        return Response::json($this->businessToolService->salesOrderInvoiceStatus($filters));
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'order_query'        => $schema->string()->description('Order number/reference or customer name.'),
            'order_state'        => $schema->string()->enum(['draft', 'sent', 'sale', 'cancel']),
            'invoice_status'     => $schema->string()->enum(['up_selling', 'invoiced', 'to_invoice', 'no']),
            'payment_state'      => $schema->string()->enum(['not_paid', 'in_payment', 'paid', 'partial', 'reversed', 'blocked', 'invoicing_legacy']),
            'date_from'          => $schema->string()->description('Order date from, YYYY-MM-DD.'),
            'date_to'            => $schema->string()->description('Order date through, YYYY-MM-DD.'),
            'only_with_invoices' => $schema->boolean()->default(false),
            'limit'              => $schema->integer()->min(1)->max(100)->default(25),
        ];
    }
}
