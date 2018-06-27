<?php

namespace Tests\ApiBundle\Unit\Command\Job;

use SimplyTestable\ApiBundle\Command\Job\PrepareCommand;
use SimplyTestable\ApiBundle\Resque\Job\Job\PrepareJob;
use Tests\ApiBundle\Factory\MockFactory;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class PrepareCommandTest extends \PHPUnit_Framework_TestCase
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
