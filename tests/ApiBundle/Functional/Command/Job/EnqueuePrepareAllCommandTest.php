<?php

namespace Tests\ApiBundle\Functional\Command\Job;

use SimplyTestable\ApiBundle\Command\Job\EnqueuePrepareAllCommand;
use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Services\Resque\QueueService;
use Tests\ApiBundle\Functional\AbstractBaseTestCase;
use Tests\ApiBundle\Factory\JobFactory;
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

        $this->command = $this->container->get(EnqueuePrepareAllCommand::class);
    }

    public function testRun()
    {
        $resqueQueueService = $this->container->get(QueueService::class);

        $jobValuesCollection = [
            [
                JobFactory::KEY_DOMAIN => 'http://foo.example.com/',
            ],
            [
                JobFactory::KEY_DOMAIN => 'http://bar.example.com/',
            ],
        ];

        $jobFactory = new JobFactory($this->container);

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
