<?php

namespace Webkul\Mcp\Support;

use Webkul\Mcp\Support\Concerns\HasQueryHelpers;
use Webkul\TechnicalSupport\Models\Ticket;

class SupportToolService
{
    use HasQueryHelpers;

    public function supportTicketOverview(): array
    {
        $ticketModel = Ticket::class;

        return [
            'total_tickets'       => $this->count($ticketModel),
            'unread_by_admin'     => $this->count($ticketModel, fn ($q) => $q->where('is_unread_admin', true)),
            'reopened_tickets'    => $this->count($ticketModel, fn ($q) => $q->where('reopened', true)),
            'tickets_by_status'   => $this->groupCount($ticketModel, 'status'),
            'tickets_by_priority' => $this->groupCount($ticketModel, 'priority'),
            'top_assignees'       => $this->groupCountLimit($ticketModel, 'assigned_to', null, 5),
            'tickets_by_partner'  => $this->groupCountLimit($ticketModel, 'partner_id', null, 5),
        ];
    }
}
