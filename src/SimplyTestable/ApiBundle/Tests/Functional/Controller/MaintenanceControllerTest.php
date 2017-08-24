<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller;

use SimplyTestable\ApiBundle\Command\Maintenance\EnableBackupReadOnlyCommand;
use SimplyTestable\ApiBundle\Command\Maintenance\EnableReadOnlyCommand;
use SimplyTestable\ApiBundle\Controller\MaintenanceController;
use SimplyTestable\ApiBundle\Tests\Functional\BaseSimplyTestableTestCase;

class MaintenanceControllerTest extends BaseSimplyTestableTestCase
{
    /**
     * @var MaintenanceController
     */
    private $controller;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->controller = new MaintenanceController();
        $this->controller->setContainer($this->container);
    }

    public function testEnableBackupReadOnlyAction()
    {
        $this->markTestSkipped('Re-implement in 1191');

        $this->controller->enableBackupReadOnlyAction();

        $this->assertEquals(
            EnableBackupReadOnlyCommand::STATE_MAINTENANCE_BACKUP_READ_ONLY,
            file_get_contents($this->getStateResourcePath())
        );
    }

    public function testEnableReadOnlyAction()
    {
        $this->markTestSkipped('Re-implement in 1191');

        $this->controller->enableReadOnlyAction();

        $this->assertEquals(
            EnableReadOnlyCommand::STATE_MAINTENANCE_READ_ONLY,
            file_get_contents($this->getStateResourcePath())
        );
    }

    public function testDisableReadOnlyAction()
    {
        $this->markTestSkipped('Re-implement in 1191');

        $this->controller->disableReadOnlyAction();

        $this->assertEquals(
            EnableReadOnlyCommand::STATE_ACTIVE,
            file_get_contents($this->getStateResourcePath())
        );
    }

    public function testLeaveReadOnlyAction()
    {
        $this->markTestSkipped('Re-implement in 1191');

        $this->controller->leaveReadOnlyAction();

        $this->assertEquals(
            EnableReadOnlyCommand::STATE_ACTIVE,
            file_get_contents($this->getStateResourcePath())
        );
    }

    /**
     * @return string
     */
    private function getStateResourcePath()
    {
        $kernel = $this->container->get('kernel');

        return sprintf(
            '%s%s',
            $kernel->locateResource('@SimplyTestableApiBundle/Resources/config/state/'),
            $kernel->getEnvironment()
        );
    }
}
