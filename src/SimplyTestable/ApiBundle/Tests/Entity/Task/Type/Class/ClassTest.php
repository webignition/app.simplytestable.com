<?php

namespace SimplyTestable\ApiBundle\Tests\Entity\Task\Type;

use SimplyTestable\ApiBundle\Tests\BaseSimplyTestableTestCase;
use SimplyTestable\ApiBundle\Entity\Task\Type\TaskTypeClass;

class ClassTest extends BaseSimplyTestableTestCase {
    
    public function testUtf8Name() {
        $name = 'test-ɸ';
        
        $taskTypeClass = new TaskTypeClass();
        $taskTypeClass->setName($name);
        $taskTypeClass->setDescription('foo');
      
        $this->getManager()->persist($taskTypeClass);
        $this->getManager()->flush();
      
        $taskTypeClassId = $taskTypeClass->getId();
   
        $this->getManager()->clear();
  
        $this->assertEquals($name, $this->getManager()->getRepository('SimplyTestable\ApiBundle\Entity\Task\Type\TaskTypeClass')->find($taskTypeClassId)->getName());
    }    
    
    
    public function testUtf8Description() {
        $description = 'test-ɸ';
        
        $taskTypeClass = new TaskTypeClass();
        $taskTypeClass->setName('test-foo');
        $taskTypeClass->setDescription($description);
      
        $this->getManager()->persist($taskTypeClass);
        $this->getManager()->flush();
      
        $taskTypeClassId = $taskTypeClass->getId();
   
        $this->getManager()->clear();
  
        $this->assertEquals($description, $this->getManager()->getRepository('SimplyTestable\ApiBundle\Entity\Task\Type\TaskTypeClass')->find($taskTypeClassId)->getDescription());
    }     
}
