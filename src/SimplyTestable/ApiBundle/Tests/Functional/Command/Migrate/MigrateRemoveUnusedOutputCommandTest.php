<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Command\Migrate;

use SimplyTestable\ApiBundle\Command\MigrateRemoveUnusedOutputCommand;
use SimplyTestable\ApiBundle\Controller\MaintenanceController;
use SimplyTestable\ApiBundle\Tests\Functional\BaseSimplyTestableTestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class MigrateRemoveUnusedOutputCommandTest extends BaseSimplyTestableTestCase
{
    /**
     * @var MigrateRemoveUnusedOutputCommand
     */
    private $command;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->command = new MigrateRemoveUnusedOutputCommand();
        $this->command->setContainer($this->container);
    }

    public function testRunCommandInMaintenanceReadOnlyModeReturnsStatusCode1()
    {
        $maintenanceController = new MaintenanceController();
        $maintenanceController->setContainer($this->container);
        $maintenanceController->enableReadOnlyAction();

        $returnCode = $this->command->run(new ArrayInput([]), new BufferedOutput());

        $maintenanceController->disableReadOnlyAction();

        $this->assertEquals(1, $returnCode);
    }
}
