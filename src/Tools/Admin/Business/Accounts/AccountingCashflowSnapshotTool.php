<?php

namespace Webkul\Mcp\Tools\Admin\Business\Accounts;

use Webkul\Mcp\Tools\Admin\Business\BusinessMetricTool;

class AccountingCashflowSnapshotTool extends BusinessMetricTool
{
    protected string $description = <<<'MARKDOWN'
        Show the current financial position: posted cash and bank account balances with account breakdown, customer receivables, customer prepaid/on-account money, supplier payables, and net exposure. Use this for questions such as "what are the account balances now?", "how much is in cash/bank?", or "how much customer money is held on account?" Do not describe zero open receivables as zero company balance.
    MARKDOWN;

    protected function metric(): string
    {
        return 'accounting_cashflow_snapshot';
    }

    protected function pluginName(): string
    {
        return 'accounts';
    }
}
