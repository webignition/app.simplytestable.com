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
      
        $this->getEntityManager()->persist($taskTypeClass);        
        $this->getEntityManager()->flush();
      
        $taskTypeClassId = $taskTypeClass->getId();
   
        $this->getEntityManager()->clear();
  
        $this->assertEquals($name, $this->getEntityManager()->getRepository('SimplyTestable\ApiBundle\Entity\Task\Type\TaskTypeClass')->find($taskTypeClassId)->getName());
    }    
    
    
    public function testUtf8Description() {
        $description = 'test-ɸ';
        
        $taskTypeClass = new TaskTypeClass();
        $taskTypeClass->setName('test-foo');
        $taskTypeClass->setDescription($description);
      
        $this->getEntityManager()->persist($taskTypeClass);        
        $this->getEntityManager()->flush();
      
        $taskTypeClassId = $taskTypeClass->getId();
   
        $this->getEntityManager()->clear();
  
        $this->assertEquals($description, $this->getEntityManager()->getRepository('SimplyTestable\ApiBundle\Entity\Task\Type\TaskTypeClass')->find($taskTypeClassId)->getDescription());
    }     
}
