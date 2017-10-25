<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\Task\TaskService\IsFinished;

use SimplyTestable\ApiBundle\Entity\Task\Task;
use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;
use SimplyTestable\ApiBundle\Tests\Functional\Services\Task\TaskService\ServiceTest;

abstract class IsFinishedTest extends ServiceTest
{
    private $task;

    protected function setUp()
    {
        parent::setUp();

        $jobFactory = new JobFactory($this->container);
        $job = $jobFactory->create();

        $this->task = new Task();
        $this->task->setJob($job);
        $this->task->setUrl('http://example.com');
        $this->task->setState($this->getState());
        $this->task->setType($this->getTaskTypeService()->getByName('html validation'));

        $this->getTaskService()->persistAndFlush($this->task);
    }

    abstract protected function getExpectedIsFinished();

    public function testIsFinished()
    {
        $this->assertEquals($this->getExpectedIsFinished(), $this->getTaskService()->isFinished($this->task));
    }

    private function getState()
    {
        $classNameParts = explode('\\', get_class($this));

        $inflector = \ICanBoogie\Inflector::get();
        $stateName = 'task-' . $inflector->hyphenate(
            str_replace('Test', '', $classNameParts[count($classNameParts) - 1])
        );

        if (!$this->getStateService()->has($stateName)) {
            $this->fail('Task state "' . $stateName . '" does not exist');
        }

        return $this->getStateService()->fetch($stateName);
    }
}
