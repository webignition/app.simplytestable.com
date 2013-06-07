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

}
