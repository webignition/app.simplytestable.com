<?php

namespace App\Tests\Functional\Services;

use App\Model\ApplicationStateInterface;
use App\Services\ApplicationStateService;
use App\Tests\Functional\AbstractBaseTestCase;

class ApplicationStateServiceTest extends AbstractBaseTestCase
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

        $this->applicationStateService = self::$container->get(ApplicationStateService::class);
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

        $this->applicationStateService->setState(ApplicationStateInterface::DEFAULT_STATE);
    }

    /**
     * @return array
     */
    public function setStateGetStateDataProvider()
    {
        return [
            ApplicationStateInterface::STATE_ACTIVE => [
                'state' => ApplicationStateInterface::STATE_ACTIVE,
                'expectedState' => ApplicationStateInterface::STATE_ACTIVE,
            ],
            ApplicationStateInterface::STATE_MAINTENANCE_READ_ONLY => [
                'state' => ApplicationStateInterface::STATE_MAINTENANCE_READ_ONLY,
                'expectedState' => ApplicationStateInterface::STATE_MAINTENANCE_READ_ONLY,
            ],
            ApplicationStateInterface::STATE_MAINTENANCE_BACKUP_READ_ONLY => [
                'state' => ApplicationStateInterface::STATE_MAINTENANCE_BACKUP_READ_ONLY,
                'expectedState' => ApplicationStateInterface::STATE_MAINTENANCE_BACKUP_READ_ONLY,
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

        $this->applicationStateService->setState(ApplicationStateInterface::DEFAULT_STATE);
    }

    /**
     * @return array
     */
    public function isInStateDataProvider()
    {
        return [
            ApplicationStateInterface::STATE_ACTIVE => [
                'state' => ApplicationStateInterface::STATE_ACTIVE,
                'expectedIsInActiveState' => true,
                'expectedIsInMaintenanceReadOnlyState' => false,
                'expectedIsInMaintenanceBackupReadOnlyState' => false,
            ],
            ApplicationStateInterface::STATE_MAINTENANCE_READ_ONLY => [
                'state' => ApplicationStateInterface::STATE_MAINTENANCE_READ_ONLY,
                'expectedIsInActiveState' => false,
                'expectedIsInMaintenanceReadOnlyState' => true,
                'expectedIsInMaintenanceBackupReadOnlyState' => false,
            ],
            ApplicationStateInterface::STATE_MAINTENANCE_BACKUP_READ_ONLY => [
                'state' => ApplicationStateInterface::STATE_MAINTENANCE_BACKUP_READ_ONLY,
                'expectedIsInActiveState' => false,
                'expectedIsInMaintenanceReadOnlyState' => false,
                'expectedIsInMaintenanceBackupReadOnlyState' => true,
            ],
        ];
    }
}
