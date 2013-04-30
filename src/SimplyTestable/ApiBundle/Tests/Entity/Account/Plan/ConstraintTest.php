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
        
        $this->getEntityManager()->persist($constraint);
        $this->getEntityManager()->flush();
        
        $this->assertNotNull($constraint->getId());
    }
    
    
    public function testNameUniqueness() {
        $plan = $this->createAccountPlan();
        
        $constraint1 = new Constraint();
        $constraint1->setName('bar');
        $constraint1->setPlan($plan);
        
        $this->getEntityManager()->persist($constraint1);
        $this->getEntityManager()->flush();        
        
        $constraint2 = new Constraint();
        $constraint2->setName('bar');
        $constraint2->setPlan($plan);
        
        $this->getEntityManager()->persist($constraint2);
        
        try {
            $this->getEntityManager()->flush();
            $this->fail('\Doctrine\DBAL\DBALException not raised for non-unique name');
        } catch (\Doctrine\DBAL\DBALException $doctrineDbalException) {
            $this->assertEquals(23000, $doctrineDbalException->getPrevious()->getCode());
        }        
    }

}
