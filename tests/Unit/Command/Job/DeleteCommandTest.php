<?php

namespace App\Tests\Unit\Command\Job;

use App\Command\Job\DeleteCommand;
use App\Repository\CrawlJobContainerRepository;
use App\Repository\JobRepository;
use App\Resque\Job\Job\PrepareJob;
use App\Tests\Factory\MockFactory;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class DeleteCommandTest extends \PHPUnit\Framework\TestCase
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

        $command = new DeleteCommand(
            \Mockery::mock(JobRepository::class),
            MockFactory::createApplicationStateService(true),
            MockFactory::createEntityManager(),
            \Mockery::mock(CrawlJobContainerRepository::class)
        );

        $returnCode = $command->run(new ArrayInput([
            'id' => $jobId,
        ]), new BufferedOutput());

        $this->assertEquals(
            DeleteCommand::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE,
            $returnCode
        );
    }
}
