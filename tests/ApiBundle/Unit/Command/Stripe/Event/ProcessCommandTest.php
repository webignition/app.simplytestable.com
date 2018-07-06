<?php

namespace Tests\ApiBundle\Unit\Command\Stripe\Event;

use SimplyTestable\ApiBundle\Command\Stripe\Event\ProcessCommand;
use Tests\ApiBundle\Factory\MockFactory;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class ProcessCommandTest extends \PHPUnit\Framework\TestCase
{
    public function testRunInMaintenanceReadOnlyMode()
    {
        $command = new ProcessCommand(
            MockFactory::createApplicationStateService(true),
            MockFactory::createEntityManager(),
            MockFactory::createLogger(),
            MockFactory::createEventDispatcher()
        );

        $returnCode = $command->run(new ArrayInput([
            'stripeId' => 1,
        ]), new BufferedOutput());

        $this->assertEquals(
            ProcessCommand::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE,
            $returnCode
        );
    }
}
