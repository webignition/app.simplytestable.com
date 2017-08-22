<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services;

use phpmock\functions\FixedValueFunction;
use phpmock\MockBuilder;
use phpmock\mockery\PHPMockery;
use SimplyTestable\ApiBundle\Services\ApplicationStateService;
use SimplyTestable\ApiBundle\Tests\Functional\BaseSimplyTestableTestCase;

class ApplicationStateServiceTest extends BaseSimplyTestableTestCase
{
    /**
     * @var ApplicationStateService
     */
    private $applicationStateService;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->applicationStateService = $this->container->get('simplytestable.services.applicationstateservice');
    }

    /**
     * @dataProvider setStateGetStateDataProvider
     *
     * @param string $state
     * @param string $expectedState
     */
    public function testSetStateGetState($state, $expectedState)
    {
        $returnValue = $this->applicationStateService->setState($state);

        $this->assertTrue($returnValue);
        $this->assertEquals($expectedState, $this->applicationStateService->getState());

        $this->applicationStateService->setState(ApplicationStateService::DEFAULT_STATE);
    }

    /**
     * @return array
     */
    public function setStateGetStateDataProvider()
    {
        return [
            ApplicationStateService::STATE_ACTIVE => [
                'state' => ApplicationStateService::STATE_ACTIVE,
                'expectedState' => ApplicationStateService::STATE_ACTIVE,
            ],
            ApplicationStateService::STATE_MAINTENANCE_READ_ONLY => [
                'state' => ApplicationStateService::STATE_MAINTENANCE_READ_ONLY,
                'expectedState' => ApplicationStateService::STATE_MAINTENANCE_READ_ONLY,
            ],
            ApplicationStateService::STATE_MAINTENANCE_BACKUP_READ_ONLY => [
                'state' => ApplicationStateService::STATE_MAINTENANCE_BACKUP_READ_ONLY,
                'expectedState' => ApplicationStateService::STATE_MAINTENANCE_BACKUP_READ_ONLY,
            ],
        ];
    }

    public function testSetStateInvalidState()
    {
        $previousState = $this->applicationStateService->getState();

        $returnValue = $this->applicationStateService->setState('foo');

        $this->assertFalse($returnValue);
        $this->assertEquals($previousState, $this->applicationStateService->getState());
    }

    /**
     * @dataProvider isInStateDataProvider
     *
     * @param string $state
     * @param bool $expectedIsInActiveState
     * @param bool $expectedIsInMaintenanceReadOnlyState
     * @param bool $expectedIsInMaintenanceBackupReadOnlyState
     */
    public function testIsInState(
        $state,
        $expectedIsInActiveState,
        $expectedIsInMaintenanceReadOnlyState,
        $expectedIsInMaintenanceBackupReadOnlyState
    ) {
        $this->applicationStateService->setState($state);

        $this->assertEquals(
            $expectedIsInActiveState,
            $this->applicationStateService->isInActiveState()
        );

        $this->assertEquals(
            $expectedIsInMaintenanceReadOnlyState,
            $this->applicationStateService->isInMaintenanceReadOnlyState()
        );

        $this->assertEquals(
            $expectedIsInMaintenanceBackupReadOnlyState,
            $this->applicationStateService->isInMaintenanceBackupReadOnlyState()
        );

        $this->applicationStateService->setState(ApplicationStateService::DEFAULT_STATE);
    }

    /**
     * @return array
     */
    public function isInStateDataProvider()
    {
        return [
            ApplicationStateService::STATE_ACTIVE => [
                'state' => ApplicationStateService::STATE_ACTIVE,
                'expectedIsInActiveState' => true,
                'expectedIsInMaintenanceReadOnlyState' => false,
                'expectedIsInMaintenanceBackupReadOnlyState' => false,
            ],
            ApplicationStateService::STATE_MAINTENANCE_READ_ONLY => [
                'state' => ApplicationStateService::STATE_MAINTENANCE_READ_ONLY,
                'expectedIsInActiveState' => false,
                'expectedIsInMaintenanceReadOnlyState' => true,
                'expectedIsInMaintenanceBackupReadOnlyState' => false,
            ],
            ApplicationStateService::STATE_MAINTENANCE_BACKUP_READ_ONLY => [
                'state' => ApplicationStateService::STATE_MAINTENANCE_BACKUP_READ_ONLY,
                'expectedIsInActiveState' => false,
                'expectedIsInMaintenanceReadOnlyState' => false,
                'expectedIsInMaintenanceBackupReadOnlyState' => true,
            ],
        ];
    }
}
