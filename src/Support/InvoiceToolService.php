<?php

namespace Webkul\Mcp\Support;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Webkul\Account\Models\Move;
use Webkul\Invoice\Models\Invoice;
use Webkul\Mcp\Support\Concerns\HasQueryHelpers;
use Webkul\Product\Enums\ProductType;
use Webkul\Product\Services\ProductCostCalculator;

class InvoiceToolService
{
    use HasQueryHelpers;

    public function monthlySalesProfitability(): array
    {
        $today = Carbon::today();
        $start = $today->copy()->startOfMonth()->toDateString();
        $end = $today->copy()->endOfMonth()->toDateString();

        $moves = Move::query()
            ->with(['invoiceLines.product.uom', 'invoiceLines.uom'])
            ->where('state', 'posted')
            ->whereIn('move_type', ['out_invoice', 'out_receipt', 'out_refund'])
            ->whereBetween('invoice_date', [$start, $end])
            ->get();

        $costCalculator = app(ProductCostCalculator::class);
        $revenue = 0.0;
        $tax = 0.0;
        $total = 0.0;
        $estimatedCost = 0.0;
        $directInvoices = 0;
        $salesOrderInvoices = 0;

        foreach ($moves as $move) {
            $direction = $move->move_type->value === 'out_refund' ? -1 : 1;
            $revenue += $direction * abs((float) $move->amount_untaxed);
            $tax += $direction * abs((float) $move->amount_tax);
            $total += $direction * abs((float) $move->amount_total);

            if ($direction > 0) {
                filled($move->invoice_origin) ? $salesOrderInvoices++ : $directInvoices++;
            }

            foreach ($move->invoiceLines as $line) {
                $product = $line->product;

                if (! $product || $product->type === ProductType::SERVICE) {
                    continue;
                }

                $quantity = abs((float) $line->quantity);
                if ($line->uom && $product->uom) {
                    $quantity = (float) $line->uom->computeQuantity($quantity, $product->uom, false);
                }

                $estimatedCost += $direction * $quantity * $costCalculator->unitCost($product, $move->company_id);
            }
        }

        $grossProfit = $revenue - $estimatedCost;

        return [
            'period' => ['from' => $start, 'to' => $end],
            'posted_document_count' => $moves->count(),
            'invoice_and_receipt_count' => $moves->filter(fn (Move $move) => in_array($move->move_type->value, ['out_invoice', 'out_receipt'], true))->count(),
            'credit_note_count' => $moves->filter(fn (Move $move) => $move->move_type->value === 'out_refund')->count(),
            'sales_order_invoice_count' => $salesOrderInvoices,
            'direct_invoice_count' => $directInvoices,
            'net_sales_before_tax' => round($revenue, 2),
            'net_tax' => round($tax, 2),
            'net_sales_including_tax' => round($total, 2),
            'estimated_cost_of_goods_sold' => round($estimatedCost, 2),
            'estimated_gross_profit' => round($grossProfit, 2),
            'estimated_gross_margin_percent' => $revenue !== 0.0 ? round(($grossProfit / $revenue) * 100, 2) : 0.0,
            'cost_basis' => 'Current product cost; BOM/component cost is used for manufactured products and service products have zero cost.',
            'profit_is_estimate' => true,
        ];
    }

    public function invoiceList(): array
    {
        $model = Invoice::class;

        /** @var \Closure(\Illuminate\Database\Eloquent\Builder): \Illuminate\Database\Eloquent\Builder $outboundScope */
        $outboundScope = fn (Builder $query) => $query->whereIn('move_type', ['out_invoice', 'out_receipt']);
        $today = Carbon::today();
        $startOfMonth = $today->copy()->startOfMonth()->toDateString();
        $last30Days = $today->copy()->subDays(30)->toDateString();

        $total = $this->count($model, $outboundScope);

        return [
            'total'                         => $total,
            'states'                        => $this->groupCount($model, 'state', $outboundScope),
            'payment_states'                => $this->groupCount($model, 'payment_state', $outboundScope),
            'top_partners_by_invoice_count' => $this->groupCountLimit($model, 'partner_id', $outboundScope),
            'top_salespersons_by_invoices'  => $this->groupCountLimit($model, 'invoice_user_id', $outboundScope),
            'avg_total_amount'              => $this->average($model, 'amount_total', $outboundScope),
            'total_amount'                  => $this->sum($model, 'amount_total', $outboundScope),
            'total_residual'                => $this->sum($model, 'amount_residual', $outboundScope),
            'paid_ratio'                    => $this->ratio(
                $this->count($model, fn (Builder $query) => $query->whereIn('move_type', ['out_invoice', 'out_receipt'])->where('payment_state', 'paid')),
                $total
            ),
            'this_month' => [
                'count'  => $this->count($model, fn (Builder $query) => $query->whereIn('move_type', ['out_invoice', 'out_receipt'])->whereDate('invoice_date', '>=', $startOfMonth)),
                'amount' => $this->sum($model, 'amount_total', fn (Builder $query) => $query->whereIn('move_type', ['out_invoice', 'out_receipt'])->whereDate('invoice_date', '>=', $startOfMonth)),
            ],
            'last_30_days' => [
                'count'  => $this->count($model, fn (Builder $query) => $query->whereIn('move_type', ['out_invoice', 'out_receipt'])->whereDate('invoice_date', '>=', $last30Days)),
                'amount' => $this->sum($model, 'amount_total', fn (Builder $query) => $query->whereIn('move_type', ['out_invoice', 'out_receipt'])->whereDate('invoice_date', '>=', $last30Days)),
            ],
        ];
    }

    public function invoiceOverdueSummary(): array
    {
        $model = Invoice::class;
        $today = Carbon::today()->toDateString();
        $overdueCount = $this->count($model, fn (Builder $query) => $query
            ->whereIn('move_type', ['out_invoice', 'out_receipt'])
            ->whereDate('invoice_date_due', '<', $today)
            ->whereIn('payment_state', ['not_paid', 'partial', 'in_payment']));
        $overdueAmount = $this->sum($model, 'amount_residual', fn (Builder $query) => $query
            ->whereIn('move_type', ['out_invoice', 'out_receipt'])
            ->whereDate('invoice_date_due', '<', $today)
            ->whereIn('payment_state', ['not_paid', 'partial', 'in_payment']));
        $openCount = $this->count($model, fn (Builder $query) => $query
            ->whereIn('move_type', ['out_invoice', 'out_receipt'])
            ->whereIn('payment_state', ['not_paid', 'partial', 'in_payment']));
        $openAmount = $this->sum($model, 'amount_residual', fn (Builder $query) => $query
            ->whereIn('move_type', ['out_invoice', 'out_receipt'])
            ->whereIn('payment_state', ['not_paid', 'partial', 'in_payment']));

        return [
            'overdue_count'           => $overdueCount,
            'overdue_amount_residual' => $overdueAmount,
            'overdue_count_ratio'     => $this->ratio($overdueCount, $openCount),
            'overdue_amount_ratio'    => $this->ratio($overdueAmount, $openAmount),
            'top_overdue_partners'    => $this->groupSumLimit($model, 'partner_id', 'amount_residual', fn (Builder $query) => $query
                ->whereIn('move_type', ['out_invoice', 'out_receipt'])
                ->whereDate('invoice_date_due', '<', $today)
                ->whereIn('payment_state', ['not_paid', 'partial', 'in_payment'])),
            'overdue_by_salesperson' => $this->groupSumLimit($model, 'invoice_user_id', 'amount_residual', fn (Builder $query) => $query
                ->whereIn('move_type', ['out_invoice', 'out_receipt'])
                ->whereDate('invoice_date_due', '<', $today)
                ->whereIn('payment_state', ['not_paid', 'partial', 'in_payment'])),
        ];
    }

    public function invoiceAgingBuckets(): array
    {
        $model = Invoice::class;
        $today = Carbon::today();

        $bucket0To7Scope = fn (Builder $query) => $query
            ->whereIn('move_type', ['out_invoice', 'out_receipt'])
            ->whereBetween('invoice_date_due', [$today->copy()->subDays(7)->toDateString(), $today->toDateString()])
            ->whereIn('payment_state', ['not_paid', 'partial', 'in_payment']);
        $bucket8To30Scope = fn (Builder $query) => $query
            ->whereIn('move_type', ['out_invoice', 'out_receipt'])
            ->whereBetween('invoice_date_due', [$today->copy()->subDays(30)->toDateString(), $today->copy()->subDays(8)->toDateString()])
            ->whereIn('payment_state', ['not_paid', 'partial', 'in_payment']);
        $bucket31PlusScope = fn (Builder $query) => $query
            ->whereIn('move_type', ['out_invoice', 'out_receipt'])
            ->whereDate('invoice_date_due', '<', $today->copy()->subDays(30)->toDateString())
            ->whereIn('payment_state', ['not_paid', 'partial', 'in_payment']);

        $bucket0To7Count = $this->count($model, $bucket0To7Scope);
        $bucket8To30Count = $this->count($model, $bucket8To30Scope);
        $bucket31PlusCount = $this->count($model, $bucket31PlusScope);
        $bucket0To7Amount = $this->sum($model, 'amount_residual', $bucket0To7Scope);
        $bucket8To30Amount = $this->sum($model, 'amount_residual', $bucket8To30Scope);
        $bucket31PlusAmount = $this->sum($model, 'amount_residual', $bucket31PlusScope);
        $totalCount = $bucket0To7Count + $bucket8To30Count + $bucket31PlusCount;

        return [
            '0_7_days'              => $bucket0To7Count,
            '0_7_days_amount'       => $bucket0To7Amount,
            '8_30_days'             => $bucket8To30Count,
            '8_30_days_amount'      => $bucket8To30Amount,
            '31_plus_days'          => $bucket31PlusCount,
            '31_plus_days_amount'   => $bucket31PlusAmount,
            'total_overdue_buckets' => $totalCount,
            'total_overdue_amount'  => $bucket0To7Amount + $bucket8To30Amount + $bucket31PlusAmount,
            '31_plus_share'         => $this->ratio($bucket31PlusCount, $totalCount),
        ];
    }

    public function invoicePaymentFollowups(): array
    {
        $model = Invoice::class;
        $followupCandidates = $this->count($model, fn (Builder $query) => $query
            ->whereIn('move_type', ['out_invoice', 'out_receipt'])
            ->whereIn('payment_state', ['not_paid', 'partial']));
        $highValueFollowups = $this->count($model, fn (Builder $query) => $query
            ->whereIn('move_type', ['out_invoice', 'out_receipt'])
            ->whereIn('payment_state', ['not_paid', 'partial'])
            ->where('amount_residual', '>', 1000));

        return [
            'followup_candidates'       => $followupCandidates,
            'high_value_followups'      => $highValueFollowups,
            'high_value_ratio'          => $this->ratio($highValueFollowups, $followupCandidates),
            'top_followup_partners'     => $this->groupSumLimit($model, 'partner_id', 'amount_residual', fn (Builder $query) => $query
                ->whereIn('move_type', ['out_invoice', 'out_receipt'])
                ->whereIn('payment_state', ['not_paid', 'partial'])),
            'top_followup_salespersons' => $this->groupSumLimit($model, 'invoice_user_id', 'amount_residual', fn (Builder $query) => $query
                ->whereIn('move_type', ['out_invoice', 'out_receipt'])
                ->whereIn('payment_state', ['not_paid', 'partial'])),
        ];
    }

    public function creditNotesOverview(): array
    {
        $model = Invoice::class;
        $today = Carbon::today();

        // Customer credit notes (out_refund)
        $customerScope = fn (Builder $query) => $query->where('move_type', 'out_refund');
        $customerTotal = $this->count($model, $customerScope);
        $customerOpenScope = fn (Builder $query) => $query
            ->where('move_type', 'out_refund')
            ->whereIn('payment_state', ['not_paid', 'partial', 'in_payment']);

        // Vendor refunds / credit notes (in_refund)
        $vendorScope = fn (Builder $query) => $query->where('move_type', 'in_refund');
        $vendorTotal = $this->count($model, $vendorScope);
        $vendorOpenScope = fn (Builder $query) => $query
            ->where('move_type', 'in_refund')
            ->whereIn('payment_state', ['not_paid', 'partial', 'in_payment']);

        return [
            'customer_credit_notes' => [
                'total'             => $customerTotal,
                'total_amount'      => $this->sum($model, 'amount_total', $customerScope),
                'open_count'        => $this->count($model, $customerOpenScope),
                'open_residual'     => $this->sum($model, 'amount_residual', $customerOpenScope),
                'states'            => $this->groupCount($model, 'state', $customerScope),
                'payment_states'    => $this->groupCount($model, 'payment_state', $customerScope),
                'top_partners'      => $this->groupSumLimit($model, 'partner_id', 'amount_total', $customerScope),
                'top_salespersons'  => $this->groupCountLimit($model, 'invoice_user_id', $customerScope),
                'issued_this_month' => $this->count($model, fn (Builder $query) => $query
                    ->where('move_type', 'out_refund')
                    ->whereDate('invoice_date', '>=', $today->copy()->startOfMonth()->toDateString())),
            ],
            'vendor_refunds' => [
                'total'             => $vendorTotal,
                'total_amount'      => $this->sum($model, 'amount_total', $vendorScope),
                'open_count'        => $this->count($model, $vendorOpenScope),
                'open_residual'     => $this->sum($model, 'amount_residual', $vendorOpenScope),
                'states'            => $this->groupCount($model, 'state', $vendorScope),
                'payment_states'    => $this->groupCount($model, 'payment_state', $vendorScope),
                'top_vendors'       => $this->groupSumLimit($model, 'partner_id', 'amount_total', $vendorScope),
                'issued_this_month' => $this->count($model, fn (Builder $query) => $query
                    ->where('move_type', 'in_refund')
                    ->whereDate('invoice_date', '>=', $today->copy()->startOfMonth()->toDateString())),
            ],
        ];
    }

    public function invoiceStatusBreakdown(): array
    {
        $model = Invoice::class;

        /** @var \Closure(\Illuminate\Database\Eloquent\Builder): \Illuminate\Database\Eloquent\Builder $outboundScope */
        $outboundScope = fn (Builder $query) => $query->whereIn('move_type', ['out_invoice', 'out_receipt']);
        $total = $this->count($model, $outboundScope);
        $paidCount = $this->count($model, fn (Builder $query) => $query
            ->whereIn('move_type', ['out_invoice', 'out_receipt'])
            ->where('payment_state', 'paid'));

        return [
            'state'          => $this->groupCount($model, 'state', $outboundScope),
            'payment_state'  => $this->groupCount($model, 'payment_state', $outboundScope),
            'move_type'      => $this->groupCount($model, 'move_type', $outboundScope),
            'total_amount'   => $this->sum($model, 'amount_total', $outboundScope),
            'total_residual' => $this->sum($model, 'amount_residual', $outboundScope),
            'paid_ratio'     => $this->ratio($paidCount, $total),
        ];
    }
}
