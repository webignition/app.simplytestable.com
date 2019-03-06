<?php

namespace App\Tests\Functional\Command\Job;

use App\Command\Job\EnqueuePrepareAllCommand;
use App\Entity\Job\Job;
use App\Services\Resque\QueueService;
use App\Tests\Functional\AbstractBaseTestCase;
use App\Tests\Services\JobFactory;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class EnqueuePrepareAllCommandTest extends AbstractBaseTestCase
{
    /**
     * @var EnqueuePrepareAllCommand
     */
    private $command;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->command = self::$container->get(EnqueuePrepareAllCommand::class);
    }

    public function testRun()
    {
        $resqueQueueService = self::$container->get(QueueService::class);
        $resqueQueueService->getResque()->getQueue('job-prepare')->clear();

        $jobValuesCollection = [
            [
                JobFactory::KEY_DOMAIN => 'http://foo.example.com/',
            ],
            [
                JobFactory::KEY_DOMAIN => 'http://bar.example.com/',
            ],
        ];

        $jobFactory = self::$container->get(JobFactory::class);

        /* @var Job[] $jobs */
        $jobs = [];

        foreach ($jobValuesCollection as $jobValues) {
            $jobs[] = $jobFactory->create($jobValues);
        }

        $this->assertTrue($resqueQueueService->isEmpty('job-prepare'));

        $returnCode = $this->command->run(new ArrayInput([]), new BufferedOutput());

        $this->assertEquals(EnqueuePrepareAllCommand::RETURN_CODE_OK, $returnCode);

        foreach ($jobs as $job) {
            $this->assertTrue($resqueQueueService->contains(
                'job-prepare',
                ['id' => $job->getId()]
            ));
        }
    }
}
