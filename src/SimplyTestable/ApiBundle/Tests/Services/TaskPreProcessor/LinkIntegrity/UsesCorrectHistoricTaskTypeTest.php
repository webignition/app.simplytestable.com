<?php

namespace SimplyTestable\ApiBundle\Tests\Services\TaskPreProcessor\LinkIntegrity;

class UsesCorrectHistoricTaskTypeTest extends PreProcessorTest {

    public function setUp() {
        parent::setUp();
        
        $this->createWorker();
        
        $this->executeCommand('simplytestable:task:assign', array(
            'id' => $this->tasks->get(1)->getId()
        ));
    }
    
    
    protected function getCompletedTaskOutput() {
        return array();
    }    
    
    
    public function test1thTaskIsInProgress() {        
        $this->assertEquals($this->getTaskService()->getInProgressState(), $this->tasks->get(1)->getState());
    }
    
}
