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
use Tests\ApiBundle\Functional\AbstractBaseTestCase;

/**
 * @group Controller/Maintenance
 */
class MaintenanceControllerTest extends AbstractBaseTestCase
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

        $this->controller = $this->container->get(MaintenanceController::class);

        $this->applicationStateService = $this->container->get(ApplicationStateService::class);
    }

    public function testEnableBackupReadOnlyAction()
    {
        $this->controller->enableBackupReadOnlyAction(
            $this->container->get(EnableBackupReadOnlyCommand::class)
        );

        $this->assertEquals(
            AbstractApplicationStateChangeCommand::STATE_MAINTENANCE_BACKUP_READ_ONLY,
            $this->applicationStateService->getState()
        );
    }

    public function testEnableReadOnlyAction()
    {
        $this->controller->enableReadOnlyAction(
            $this->container->get(EnableReadOnlyCommand::class)
        );

        $this->assertEquals(
            AbstractApplicationStateChangeCommand::STATE_MAINTENANCE_READ_ONLY,
            $this->applicationStateService->getState()
        );
    }

    public function testDisableReadOnlyAction()
    {
        $this->controller->disableReadOnlyAction(
            $this->container->get(DisableReadOnlyCommand::class)
        );

        $this->assertEquals(
            AbstractApplicationStateChangeCommand::STATE_ACTIVE,
            $this->applicationStateService->getState()
        );
    }

    public function testLeaveReadOnlyAction()
    {
        $this->controller->leaveReadOnlyAction(
            $this->container->get(DisableReadOnlyCommand::class),
            $this->container->get(EnqueuePrepareAllCommand::class),
            $this->container->get(RequeueQueuedForAssignmentCommand::class),
            $this->container->get(TaskNotificationCommand::class),
            $this->container->get(EnqueueCancellationForAwaitingCancellationCommand::class)
        );

        $this->assertEquals(
            AbstractApplicationStateChangeCommand::STATE_ACTIVE,
            $this->applicationStateService->getState()
        );
    }
}
