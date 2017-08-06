<?php

namespace SimplyTestable\ApiBundle\Tests\Unit\Controller;

trait MaintenanceStatesDataProviderTrait
{
    /**
     * @return array
     */
    public function maintenanceStatesDataProvider()
    {
        return [
            'read-only' => [
                'maintenanceStates' => [
                    'read-only' => true,
                    'backup-read-only' => false,
                ],
            ],
            'backup-read-only' => [
                'maintenanceStates' => [
                    'read-only' => false,
                    'backup-read-only' => true,
                ],
            ],
        ];
    }
}
