<?php

namespace SimplyTestable\ApiBundle\Tests\Entity\Task;

use SimplyTestable\ApiBundle\Tests\BaseSimplyTestableTestCase;
use SimplyTestable\ApiBundle\Entity\Task\Task;

class TaskTest extends BaseSimplyTestableTestCase {
    
    public function testUtf8Url() {
        $taskUrl = 'http://example.com/ɸ';
        
        $task = new Task();
        $task->setJob($this->getJobService()->getById($this->createJobAndGetId('http://example.com/')));
        $task->setUrl($taskUrl);
        $task->setState($this->getTaskService()->getQueuedState());
        $task->setType($this->getTaskTypeService()->getByName('HTML Validation'));

        $this->getEntityManager()->persist($task);        
        $this->getEntityManager()->flush();
      
        $taskId = $task->getId();
   
        $this->getEntityManager()->clear();
  
        $this->assertEquals($taskUrl, $this->getEntityManager()->getRepository('SimplyTestable\ApiBundle\Entity\Task\Task')->find($taskId)->getUrl());         
    }     
    
    public function testUtf8Parameters() {                
        $key = 'key-ɸ';
        $value = 'value-ɸ';        
        
        $task = new Task();
        $task->setJob($this->getJobService()->getById($this->createJobAndGetId('http://example.com/')));
        $task->setUrl('http://example.com/');
        $task->setState($this->getTaskService()->getQueuedState());
        $task->setType($this->getTaskTypeService()->getByName('HTML Validation'));
        $task->setParameters(json_encode(array(
            $key => $value
        )));        

        $this->getEntityManager()->persist($task);        
        $this->getEntityManager()->flush();
      
        $taskId = $task->getId();
   
        $this->getEntityManager()->clear();
        
        $this->assertEquals('{"key-\u0278":"value-\u0278"}', $this->getTaskService()->getById($taskId)->getParameters());
    }     
}
