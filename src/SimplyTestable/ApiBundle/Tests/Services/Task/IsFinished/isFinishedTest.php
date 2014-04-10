<?php

namespace SimplyTestable\ApiBundle\Tests\Services\Task\IsFinished;

use SimplyTestable\ApiBundle\Tests\Services\Task\ServiceTest;

abstract class isFinishedTest extends ServiceTest {
    
    private $task;
    
    public function setUp() {
        parent::setUp();
        
        $job = $this->getJobService()->getById($this->createJobAndGetId(self::DEFAULT_CANONICAL_URL));                
        
        $this->task = new \SimplyTestable\ApiBundle\Entity\Task\Task();
        $this->task->setJob($job);
        $this->task->setUrl(self::DEFAULT_CANONICAL_URL);
        $this->task->setState($this->getState());
        $this->task->setType($this->getTaskTypeService()->getByName('html validation'));
        
        $this->getTaskService()->persistAndFlush($this->task);
    }
    
    abstract protected function getExpectedIsFinished();
    
    public function testIsFinished() {
        $this->assertEquals($this->getExpectedIsFinished(), $this->getTaskService()->isFinished($this->task));
    }
    
    private function getState() {
        $classNameParts = explode('\\', get_class($this));
        
        $inflector = \ICanBoogie\Inflector::get();
        $stateName = 'task-' . $inflector->hyphenate(str_replace('Test', '', $classNameParts[count($classNameParts) - 1]));
        
        if (!$this->getStateService()->has($stateName)) {
            $this->fail('Task state "' . $stateName . '" does not exist');
        }
        
        return $this->getStateService()->fetch($stateName);
    }
    
}
