<?php

namespace SimplyTestable\ApiBundle\Tests\Entity\Account\Plan;

use SimplyTestable\ApiBundle\Tests\BaseSimplyTestableTestCase;
use SimplyTestable\ApiBundle\Entity\Account\Plan\Constraint;

class ConstraintTest extends BaseSimplyTestableTestCase {

    public function testPersist() {
        $plan = $this->createAccountPlan();
        
        $constraint = new Constraint();
        $constraint->setName('foo');
        $constraint->setPlan($plan);
        
        $this->getManager()->persist($constraint);
        $this->getManager()->flush();
        
        $this->assertNotNull($constraint->getId());
    }
    
    public function testUtf8Name() {
        $name = 'foo-ɸ';
        
        $plan = $this->createAccountPlan();
        
        $constraint = new Constraint();
        $constraint->setName($name);
        $constraint->setPlan($plan);
        
        $this->getManager()->persist($constraint);
        $this->getManager()->flush();
        
        $constraintId = $constraint->getId();
        
        $this->getManager()->clear();
        $this->assertEquals($name, $this->getManager()->getRepository('SimplyTestable\ApiBundle\Entity\Account\Plan\Constraint')->find($constraintId)->getName());
    }    

}
