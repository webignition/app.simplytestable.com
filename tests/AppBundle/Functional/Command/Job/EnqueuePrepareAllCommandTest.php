<?php

namespace Tests\AppBundle\Functional\Command\Job;

use AppBundle\Command\Job\EnqueuePrepareAllCommand;
use AppBundle\Entity\Job\Job;
use AppBundle\Services\Resque\QueueService;
use Tests\AppBundle\Functional\AbstractBaseTestCase;
use Tests\AppBundle\Factory\JobFactory;
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

        $jobFactory = new JobFactory(self::$container);

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
