<?php

namespace App\Tests\Functional\Controller;

use App\Command\Job\EnqueuePrepareAllCommand;
use App\Command\Maintenance\AbstractApplicationStateChangeCommand;
use App\Command\Maintenance\DisableReadOnlyCommand;
use App\Command\Maintenance\EnableBackupReadOnlyCommand;
use App\Command\Maintenance\EnableReadOnlyCommand;
use App\Command\Task\EnqueueCancellationForAwaitingCancellationCommand;
use App\Command\Tasks\RequeueQueuedForAssignmentCommand;
use App\Command\Worker\TaskNotificationCommand;
use App\Controller\MaintenanceController;
use App\Services\ApplicationStateService;

/**
 * @group Controller/Maintenance
 */
class MaintenanceControllerTest extends AbstractControllerTest
{
    /**
     * @var MaintenanceController
     */
    private $controller;

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

        $this->controller = self::$container->get(MaintenanceController::class);

        $this->applicationStateService = self::$container->get(ApplicationStateService::class);
    }

    public function testEnableBackupReadOnlyAction()
    {
        $this->controller->enableBackupReadOnlyAction(
            self::$container->get(EnableBackupReadOnlyCommand::class)
        );

        $this->assertEquals(
            AbstractApplicationStateChangeCommand::STATE_MAINTENANCE_BACKUP_READ_ONLY,
            $this->applicationStateService->getState()
        );
    }

    public function testEnableReadOnlyAction()
    {
        $this->controller->enableReadOnlyAction(
            self::$container->get(EnableReadOnlyCommand::class)
        );

        $this->assertEquals(
            AbstractApplicationStateChangeCommand::STATE_MAINTENANCE_READ_ONLY,
            $this->applicationStateService->getState()
        );
    }

    public function testDisableReadOnlyAction()
    {
        $this->controller->disableReadOnlyAction(
            self::$container->get(DisableReadOnlyCommand::class)
        );

        $this->assertEquals(
            AbstractApplicationStateChangeCommand::STATE_ACTIVE,
            $this->applicationStateService->getState()
        );
    }

    public function testLeaveReadOnlyAction()
    {
        $this->controller->leaveReadOnlyAction(
            self::$container->get(DisableReadOnlyCommand::class),
            self::$container->get(EnqueuePrepareAllCommand::class),
            self::$container->get(RequeueQueuedForAssignmentCommand::class),
            self::$container->get(TaskNotificationCommand::class),
            self::$container->get(EnqueueCancellationForAwaitingCancellationCommand::class)
        );

        $this->assertEquals(
            AbstractApplicationStateChangeCommand::STATE_ACTIVE,
            $this->applicationStateService->getState()
        );
    }
}
