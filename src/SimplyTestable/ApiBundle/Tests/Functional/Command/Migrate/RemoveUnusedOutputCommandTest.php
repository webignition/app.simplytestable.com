<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Command\Migrate;

use SimplyTestable\ApiBundle\Command\Migrate\RemoveUnusedOutputCommand;
use SimplyTestable\ApiBundle\Controller\MaintenanceController;
use SimplyTestable\ApiBundle\Tests\Functional\BaseSimplyTestableTestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class RemoveUnusedOutputCommandTest extends BaseSimplyTestableTestCase
{
    /**
     * @var RemoveUnusedOutputCommand
     */
    private $command;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->command = new RemoveUnusedOutputCommand();
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
