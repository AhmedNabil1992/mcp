<?php

namespace Webkul\Mcp\Support;

use Webkul\Mcp\Support\Concerns\HasQueryHelpers;
use Webkul\SoftwareOnline\Models\OnlineInstance;
use Webkul\SoftwareOnline\Models\OnlineSystem;

class SoftwareOnlineToolService
{
    use HasQueryHelpers;

    public function onlineInstancesOverview(): array
    {
        $instanceModel = OnlineInstance::class;
        $systemModel = OnlineSystem::class;

        return [
            'total_instances'       => $this->count($instanceModel),
            'total_systems'         => $this->count($systemModel),
            'instances_by_status'   => $this->groupCount($instanceModel, 'status'),
            'instances_by_cycle'    => $this->groupCount($instanceModel, 'billing_cycle'),
            'instances_by_system'   => $this->groupCountLimit($instanceModel, 'system_id', null, 5),
            'instances_by_partner'  => $this->groupCountLimit($instanceModel, 'partner_id', null, 5),
        ];
    }
}
