<?php

namespace App\Tests\Unit\Command\Job;

use App\Command\Job\PrepareCommand;
use App\Resque\Job\Job\PrepareJob;
use App\Tests\Factory\MockFactory;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class PrepareCommandTest extends \PHPUnit\Framework\TestCase
{
    public function testRunInMaintenanceReadOnlyMode()
    {
        $jobId = 1;

        $resqueQueueService = MockFactory::createResqueQueueService();
        $resqueQueueService
            ->shouldReceive('enqueue')
            ->withArgs(function (PrepareJob $prepareJob) use ($jobId) {
                $this->assertEquals(['id' => $jobId], $prepareJob->args);

                return true;
            });

        $command = new PrepareCommand(
            MockFactory::createApplicationStateService(true),
            $resqueQueueService,
            MockFactory::createJobPreparationService(),
            MockFactory::createCrawlJobContainerService(),
            MockFactory::createLogger(),
            MockFactory::createEntityManager(),
            []
        );

        $returnCode = $command->run(new ArrayInput([
            'id' => $jobId,
        ]), new BufferedOutput());

        $this->assertEquals(
            PrepareCommand::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE,
            $returnCode
        );
    }
}
