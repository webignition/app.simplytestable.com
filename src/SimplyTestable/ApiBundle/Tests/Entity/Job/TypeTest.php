<?php

namespace SimplyTestable\ApiBundle\Tests\Entity\Job;

use SimplyTestable\ApiBundle\Tests\BaseSimplyTestableTestCase;
use SimplyTestable\ApiBundle\Entity\Job\Type;

class TypeTest extends BaseSimplyTestableTestCase {
    
    public function testUtf8Name() {
        $name = 'test-ɸ';
        
        $type = new Type();
        $type->setDescription('foo');
        $type->setName($name);
      
        $this->getEntityManager()->persist($type);        
        $this->getEntityManager()->flush();
      
        $typeId = $type->getId();
   
        $this->getEntityManager()->clear();
  
        $this->assertEquals($name, $this->getEntityManager()->getRepository('SimplyTestable\ApiBundle\Entity\Job\Type')->find($typeId)->getName());         
    }   
    
    public function testUtf8Description() {
        $description = 'ɸ';
        
        $type = new Type();
        $type->setDescription($description);
        $type->setName('test-foo');
      
        $this->getEntityManager()->persist($type);        
        $this->getEntityManager()->flush();
      
        $typeId = $type->getId();
   
        $this->getEntityManager()->clear();
  
        $this->assertEquals($description, $this->getEntityManager()->getRepository('SimplyTestable\ApiBundle\Entity\Job\Type')->find($typeId)->getDescription());         
    }     
}
