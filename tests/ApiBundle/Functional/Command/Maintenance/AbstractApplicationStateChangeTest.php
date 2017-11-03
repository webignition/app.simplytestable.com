<?php

namespace Tests\ApiBundle\Functional\Command\Maintenance;

use Mockery\MockInterface;
use SimplyTestable\ApiBundle\Command\Maintenance\AbstractApplicationStateChangeCommand;
use SimplyTestable\ApiBundle\Services\ApplicationStateService;
use Tests\ApiBundle\Functional\AbstractBaseTestCase;

abstract class AbstractApplicationStateChangeTest extends AbstractBaseTestCase
{
    /**
     * @param string $state
     * @param bool $setStateReturnValue
     *
     * @return MockInterface|ApplicationStateService
     */
    protected function createApplicationStateService($state, $setStateReturnValue)
    {
        $applicationStateService = \Mockery::mock(ApplicationStateService::class);

        $applicationStateService
            ->shouldReceive('setState')
            ->with($state)
            ->andReturn($setStateReturnValue);

        return $applicationStateService;
    }

    /**
     * @return array
     */
    public function changeApplicationStateDataProvider()
    {
        return [
            'success' => [
                'setStateReturnValue' => true,
                'expectedReturnCode' => AbstractApplicationStateChangeCommand::RETURN_CODE_OK,
            ],
            'failure' => [
                'setStateReturnValue' => false,
                'expectedReturnCode' => AbstractApplicationStateChangeCommand::RETURN_CODE_FAILURE,
            ],
        ];
    }
}
