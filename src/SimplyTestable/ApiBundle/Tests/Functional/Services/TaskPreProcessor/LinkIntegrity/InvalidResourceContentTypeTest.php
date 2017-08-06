<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\TaskPreProcessor\LinkIntegrity;

use SimplyTestable\ApiBundle\Tests\Factory\WorkerFactory;

class InvalidResourceContentTypeTest extends PreProcessorTest
{
    protected function setUp()
    {
        parent::setUp();

        $workerFactory = new WorkerFactory($this->container);
        $workerFactory->create();

        $this->executeCommand('simplytestable:task:assigncollection', array(
            'ids' => $this->tasks->get(1)->getId()
        ));
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
