<?php

namespace Tests\ApiBundle\Unit\Command\Migrate;

use SimplyTestable\ApiBundle\Command\Migrate\CanonicaliseTaskOutputCommand;
use Tests\ApiBundle\Factory\MockFactory;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class CanonicaliseTaskOutputCommandTest extends \PHPUnit_Framework_TestCase
{
    public function testRunCommandInMaintenanceReadOnlyMode()
    {
        $command = new CanonicaliseTaskOutputCommand(
            MockFactory::createApplicationStateService(true),
            MockFactory::createEntityManager()
        );

        $returnCode = $command->run(new ArrayInput([]), new BufferedOutput());

        $this->assertEquals(
            CanonicaliseTaskOutputCommand::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE,
            $returnCode
        );
    }
}
