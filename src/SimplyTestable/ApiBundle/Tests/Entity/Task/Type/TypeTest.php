<?php

namespace SimplyTestable\ApiBundle\Tests\Entity\Task\Type;

use SimplyTestable\ApiBundle\Tests\BaseSimplyTestableTestCase;
use SimplyTestable\ApiBundle\Entity\Task\Type\Type;

class TypeTest extends BaseSimplyTestableTestCase {
    
    public function testUtf8Name() {
        $name = 'test-ɸ';
        
        $type = new Type();
        $type->setName($name);
        $type->setDescription('foo');
        $type->setClass($this->getEntityManager()->getRepository('SimplyTestable\ApiBundle\Entity\Task\Type\TaskTypeClass')->find(1));
      
        $this->getEntityManager()->persist($type);        
        $this->getEntityManager()->flush();
      
        $typeId = $type->getId();
   
        $this->getEntityManager()->clear();
  
        $this->assertEquals($name, $this->getEntityManager()->getRepository('SimplyTestable\ApiBundle\Entity\Task\Type\Type')->find($typeId)->getName());                 
    }    
    
    
    public function testUtf8Description() {
        $description = 'ɸ';
        
        $type = new Type();
        $type->setName('test-foo');
        $type->setDescription($description);
        $type->setClass($this->getEntityManager()->getRepository('SimplyTestable\ApiBundle\Entity\Task\Type\TaskTypeClass')->find(1));
      
        $this->getEntityManager()->persist($type);        
        $this->getEntityManager()->flush();
      
        $typeId = $type->getId();
   
        $this->getEntityManager()->clear();
  
        $this->assertEquals($description, $this->getEntityManager()->getRepository('SimplyTestable\ApiBundle\Entity\Task\Type\Type')->find($typeId)->getDescription());                 
    }     
}
