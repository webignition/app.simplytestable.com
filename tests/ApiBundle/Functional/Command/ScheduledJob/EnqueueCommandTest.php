<?php

namespace Tests\ApiBundle\Functional\Command\ScheduledJob;

use SimplyTestable\ApiBundle\Services\Resque\QueueService;
use Tests\ApiBundle\Functional\AbstractBaseTestCase;
use SimplyTestable\ApiBundle\Command\ScheduledJob\EnqueueCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class EnqueueCommandTest extends AbstractBaseTestCase
{
    /**
     * @var EnqueueCommand
     */
    private $command;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->command = $this->container->get(EnqueueCommand::class);
    }

    public function testRunEnqueuesScheduledJobExecute()
    {
        $resqueQueueService = $this->container->get(QueueService::class);

        $returnCode = $this->command->run(new ArrayInput([
            'id' => 1,
        ]), new BufferedOutput());

        $this->assertEquals(0, $returnCode);

        $this->assertTrue($resqueQueueService->contains('scheduledjob-execute', [
            'id' => 1
        ]));
    }

    public function testRunIsIdempotent()
    {
        $resqueQueueService = $this->container->get(QueueService::class);
        $resqueQueueService->getResque()->getQueue('scheduledjob-execute')->clear();

        $this->command->run(new ArrayInput([
            'id' => 1,
        ]), new BufferedOutput());

        $returnCode = $this->command->run(new ArrayInput([
            'id' => 1,
        ]), new BufferedOutput());

        $this->assertEquals(0, $returnCode);

        $this->assertTrue($resqueQueueService->contains('scheduledjob-execute', [
            'id' => 1
        ]));

        $this->assertEquals(
            1,
            $resqueQueueService->getResque()->getQueue('scheduledjob-execute')->getSize()
        );
    }
}
