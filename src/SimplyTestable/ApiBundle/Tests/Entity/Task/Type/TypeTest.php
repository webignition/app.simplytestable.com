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
        $type->setClass($this->getManager()->getRepository('SimplyTestable\ApiBundle\Entity\Task\Type\TaskTypeClass')->find(1));
      
        $this->getManager()->persist($type);
        $this->getManager()->flush();
      
        $typeId = $type->getId();
   
        $this->getManager()->clear();
  
        $this->assertEquals($name, $this->getManager()->getRepository('SimplyTestable\ApiBundle\Entity\Task\Type\Type')->find($typeId)->getName());
    }    
    
    
    public function testUtf8Description() {
        $description = 'ɸ';
        
        $type = new Type();
        $type->setName('test-foo');
        $type->setDescription($description);
        $type->setClass($this->getManager()->getRepository('SimplyTestable\ApiBundle\Entity\Task\Type\TaskTypeClass')->find(1));
      
        $this->getManager()->persist($type);
        $this->getManager()->flush();
      
        $typeId = $type->getId();
   
        $this->getManager()->clear();
  
        $this->assertEquals($description, $this->getManager()->getRepository('SimplyTestable\ApiBundle\Entity\Task\Type\Type')->find($typeId)->getDescription());
    }     
}
