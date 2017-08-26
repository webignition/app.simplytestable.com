<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Command\ScheduledJob;

use SimplyTestable\ApiBundle\Tests\Functional\BaseSimplyTestableTestCase;
use SimplyTestable\ApiBundle\Command\ScheduledJob\EnqueueCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class EnqueueCommandTest extends BaseSimplyTestableTestCase
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

        $this->command = $this->container->get('simplytestable.command.scheduledjob.enqueue');
    }

    public function testRunEnqueuesScheduledJobExecute()
    {
        $resqueQueueService = $this->container->get('simplytestable.services.resque.queueservice');

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
        $resqueQueueService = $this->container->get('simplytestable.services.resque.queueservice');

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
