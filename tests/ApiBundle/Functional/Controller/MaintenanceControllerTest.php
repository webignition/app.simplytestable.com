<?php

namespace Tests\ApiBundle\Functional\Controller;

use SimplyTestable\ApiBundle\Command\Job\EnqueuePrepareAllCommand;
use SimplyTestable\ApiBundle\Command\Maintenance\AbstractApplicationStateChangeCommand;
use SimplyTestable\ApiBundle\Command\Maintenance\DisableReadOnlyCommand;
use SimplyTestable\ApiBundle\Command\Maintenance\EnableBackupReadOnlyCommand;
use SimplyTestable\ApiBundle\Command\Maintenance\EnableReadOnlyCommand;
use SimplyTestable\ApiBundle\Command\Task\EnqueueCancellationForAwaitingCancellationCommand;
use SimplyTestable\ApiBundle\Command\Tasks\RequeueQueuedForAssignmentCommand;
use SimplyTestable\ApiBundle\Command\Worker\TaskNotificationCommand;
use SimplyTestable\ApiBundle\Controller\MaintenanceController;
use SimplyTestable\ApiBundle\Services\ApplicationStateService;

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
