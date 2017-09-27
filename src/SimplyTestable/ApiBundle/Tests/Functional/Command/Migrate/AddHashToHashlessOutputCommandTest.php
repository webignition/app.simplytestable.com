<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Command\Migrate;

use SimplyTestable\ApiBundle\Command\Migrate\AddHashToHashlessOutputCommand;
use SimplyTestable\ApiBundle\Controller\MaintenanceController;
use SimplyTestable\ApiBundle\Tests\Functional\BaseSimplyTestableTestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class AddHashToHashlessOutputCommandTest extends BaseSimplyTestableTestCase
{
    /**
     * @var AddHashToHashlessOutputCommand
     */
    private $command;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->command = new AddHashToHashlessOutputCommand();
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
