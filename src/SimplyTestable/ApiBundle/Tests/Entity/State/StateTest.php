<?php

namespace SimplyTestable\ApiBundle\Tests\Entity\State;

use SimplyTestable\ApiBundle\Tests\BaseSimplyTestableTestCase;
use SimplyTestable\ApiBundle\Entity\State;

class StateTest extends BaseSimplyTestableTestCase {
    
    public function testUtf8Name() {
        $name = 'test-ɸ';
        
        $state = new State();
        $state->setName($name); 
      
        $this->getEntityManager()->persist($state);        
        $this->getEntityManager()->flush();
      
        $stateId = $state->getId();
   
        $this->getEntityManager()->clear();
  
        $this->assertEquals($name, $this->getEntityManager()->getRepository('SimplyTestable\ApiBundle\Entity\State')->find($stateId)->getName());         
    }     
}
