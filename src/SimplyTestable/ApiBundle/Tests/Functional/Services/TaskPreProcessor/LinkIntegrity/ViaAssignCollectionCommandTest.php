<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\TaskPreProcessor\LinkIntegrity;

use SimplyTestable\ApiBundle\Tests\Factory\WorkerFactory;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class ViaAssignCollectionCommandTest extends PreProcessorTest
{
    protected function setUp()
    {
        parent::setUp();

        /* @var $task \SimplyTestable\ApiBundle\Entity\Task\Task */
        $task = $this->tasks->get(1);

        $workerFactory = new WorkerFactory($this->container);
        $workerFactory->create();

        $taskAssignCollectionCommand = $this->container->get('simplytestable.command.task.assigncollection');
        $taskAssignCollectionCommand->run(new ArrayInput([
            'ids' => $task->getId()
        ]), new BufferedOutput());
    }

    protected function getCompletedTaskOutput()
    {
        return $this->getDefaultCompletedTaskOutput();
    }

    public function testDetermineCorrectErrorCount()
    {
        $this->assertEquals(0, $this->tasks->get(1)->getOutput()->getErrorCount());
    }
}
