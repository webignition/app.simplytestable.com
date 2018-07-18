<?php

namespace App\Tests\Unit\Command\Job;

use App\Command\Job\ResolveWebsiteCommand;
use App\Tests\Factory\MockFactory;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class ResolveWebsiteCommandTest extends \PHPUnit\Framework\TestCase
{
    public function testRunInMaintenanceReadOnlyMode()
    {
        $command = new ResolveWebsiteCommand(
            MockFactory::createApplicationStateService(true),
            MockFactory::createResqueQueueService(),
            MockFactory::createWebsiteResolutionService(),
            MockFactory::createJobPreparationService(),
            MockFactory::createEntityManager(),
            []
        );

        $returnCode = $command->run(new ArrayInput([
            'id' => 1,
        ]), new BufferedOutput());

        $this->assertEquals(
            ResolveWebsiteCommand::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE,
            $returnCode
        );
    }
}
