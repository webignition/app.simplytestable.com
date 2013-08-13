<?php

namespace SimplyTestable\ApiBundle\Tests\Services\CrawlJobContainer\ProcessTaskResults;

use SimplyTestable\ApiBundle\Tests\BaseSimplyTestableTestCase;
use SimplyTestable\ApiBundle\Entity\Task\Task;

class ProcessTaskResultsTest extends BaseSimplyTestableTestCase {
    
    public function testWithNoTaskType() {        
        $this->assertFalse($this->getCrawlJobContainerService()->processTaskResults(new Task));
    }
    
    public function testWithInvalidTaskType() {    
        $task = new Task();
        $task->setType($this->getTaskTypeService()->getByName('HTML Validation'));
        
        $this->assertFalse($this->getCrawlJobContainerService()->processTaskResults($task));
    }  
    
    public function testWithNoState() {    
        $task = new Task();
        $task->setType($this->getTaskTypeService()->getByName('URL discovery'));
        
        $this->assertFalse($this->getCrawlJobContainerService()->processTaskResults($task));
    } 
    
    public function testWithInvalidState() {    
        $task = new Task();
        $task->setType($this->getTaskTypeService()->getByName('URL discovery'));
        $task->setState($this->getTaskService()->getInProgressState());
        
        $this->assertFalse($this->getCrawlJobContainerService()->processTaskResults($task));
    }
    
    public function testWithNoOutput() {    
        $task = new Task();
        $task->setType($this->getTaskTypeService()->getByName('URL discovery'));
        $task->setState($this->getTaskService()->getCompletedState());
        
        $this->assertFalse($this->getCrawlJobContainerService()->processTaskResults($task));
    }    
    


}
