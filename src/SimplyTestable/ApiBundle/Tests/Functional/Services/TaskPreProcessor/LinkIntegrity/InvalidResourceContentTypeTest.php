<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\TaskPreProcessor\LinkIntegrity;

use SimplyTestable\ApiBundle\Tests\Factory\WorkerFactory;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class InvalidResourceContentTypeTest extends PreProcessorTest
{
    protected function setUp()
    {
        parent::setUp();

        $workerFactory = new WorkerFactory($this->container);
        $workerFactory->create();

        $taskAssignCollectionCommand = $this->container->get('simplytestable.command.task.assigncollection');
        $taskAssignCollectionCommand->run(new ArrayInput([
            'ids' => $this->tasks->get(1)->getId()
        ]), new BufferedOutput());
    }

    protected function getCompletedTaskOutput()
    {
        return array();
    }

    public function test1thTaskIsInProgress()
    {
        $this->assertEquals($this->getTaskService()->getInProgressState(), $this->tasks->get(1)->getState());
    }
}
