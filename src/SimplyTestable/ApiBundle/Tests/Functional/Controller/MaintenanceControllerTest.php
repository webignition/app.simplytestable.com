<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller;

use SimplyTestable\ApiBundle\Command\Maintenance\AbstractApplicationStateChangeCommand;
use SimplyTestable\ApiBundle\Controller\MaintenanceController;
use SimplyTestable\ApiBundle\Services\ApplicationStateService;
use SimplyTestable\ApiBundle\Tests\Functional\AbstractBaseTestCase;

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

        $this->controller = new MaintenanceController();
        $this->controller->setContainer($this->container);

        $this->applicationStateService = $this->container->get('simplytestable.services.applicationstateservice');
    }

    public function testEnableBackupReadOnlyAction()
    {
        $this->controller->enableBackupReadOnlyAction();

        $this->assertEquals(
            AbstractApplicationStateChangeCommand::STATE_MAINTENANCE_BACKUP_READ_ONLY,
            $this->applicationStateService->getState()
        );
    }

    public function testEnableReadOnlyAction()
    {
        $this->controller->enableReadOnlyAction();

        $this->assertEquals(
            AbstractApplicationStateChangeCommand::STATE_MAINTENANCE_READ_ONLY,
            $this->applicationStateService->getState()
        );
    }

    public function testDisableReadOnlyAction()
    {
        $this->controller->disableReadOnlyAction();

        $this->assertEquals(
            AbstractApplicationStateChangeCommand::STATE_ACTIVE,
            $this->applicationStateService->getState()
        );
    }

    public function testLeaveReadOnlyAction()
    {
        $this->controller->leaveReadOnlyAction();

        $this->assertEquals(
            AbstractApplicationStateChangeCommand::STATE_ACTIVE,
            $this->applicationStateService->getState()
        );
    }
}
