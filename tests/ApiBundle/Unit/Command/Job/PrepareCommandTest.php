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

        $resqueJobPrepareJob = \Mockery::mock(PrepareJob::class);

        $resqueJobFactory = MockFactory::createResqueJobFactory([
            'create' => [
                'withArgs' => [
                    'job-prepare',
                    ['id' => $jobId]
                ],
                'return' => $resqueJobPrepareJob,
            ],
        ]);

        $resqueQueueService = MockFactory::createResqueQueueService([
            'enqueue' => [
                'with' => $resqueJobPrepareJob,
            ],
        ]);

        $command = new PrepareCommand(
            MockFactory::createApplicationStateService(true),
            $resqueQueueService,
            $resqueJobFactory,
            MockFactory::createJobPreparationService(),
            MockFactory::createCrawlJobContainerService(),
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
