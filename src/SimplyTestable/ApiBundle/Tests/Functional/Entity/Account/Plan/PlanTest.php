<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Entity\Account\Plan;

use SimplyTestable\ApiBundle\Tests\Functional\BaseSimplyTestableTestCase;
use SimplyTestable\ApiBundle\Entity\Account\Plan\Constraint;
use SimplyTestable\ApiBundle\Entity\Account\Plan\Plan;

class PlanTest extends BaseSimplyTestableTestCase {

    public function testUtf8Name() {
        $name = 'test-ɸ';

        $plan = new Plan();
        $plan->setName($name);

        $this->getManager()->persist($plan);
        $this->getManager()->flush();

        $planId = $plan->getId();

        $this->getManager()->clear();
        $this->assertEquals($name, $this->getManager()->getRepository('SimplyTestable\ApiBundle\Entity\Account\Plan\Plan')->find($planId)->getName());
    }

    public function testUtf8StripeId() {
        $name = 'test-foo-plan';
        $stripeId = 'ɸ';

        $plan = new Plan();
        $plan->setName($name);
        $plan->setStripeId($stripeId);

        $this->getManager()->persist($plan);
        $this->getManager()->flush();

        $planId = $plan->getId();

        $this->getManager()->clear();
        $this->assertEquals($stripeId, $this->getManager()->getRepository('SimplyTestable\ApiBundle\Entity\Account\Plan\Plan')->find($planId)->getStripeId());
    }

    public function testCreateAndPersistWithNoConstraints() {
        $plan = new Plan();
        $plan->setName('test-foo-plan');

        $this->getManager()->persist($plan);
        $this->getManager()->flush();

        $this->assertNotNull($plan->getId());
    }


    public function testDefaultVisibilityIsFalse() {
        $plan = new Plan();
        $plan->setName('test-foo-plan');

        $this->getManager()->persist($plan);
        $this->getManager()->flush();

        $this->assertFalse($plan->getIsVisible());
    }


    public function testMakeVisible() {
        $plan = new Plan();
        $plan->setName('test-foo-plan');
        $plan->setIsVisible(true);

        $this->getManager()->persist($plan);
        $this->getManager()->flush();

        $this->assertTrue($plan->getIsVisible());
    }


    public function testNameUniqueness() {
        $plan1 = new Plan();
        $plan1->setName('test-bar-plan');

        $this->getManager()->persist($plan1);
        $this->getManager()->flush();

        $plan2 = new Plan();
        $plan2->setName('test-bar-plan');

        $this->getManager()->persist($plan2);

        try {
            $this->getManager()->flush();
            $this->fail('\Doctrine\DBAL\DBALException not raised for non-unique name');
        } catch (\Doctrine\DBAL\DBALException $doctrineDbalException) {
            $this->assertEquals(23000, $doctrineDbalException->getPrevious()->getCode());
        }
    }


    public function testAddConstraintToPlan() {
        $plan = new Plan();
        $plan->setName('test-foo-plan');

        $constraint = new Constraint();
        $constraint->setName('foo');

        $plan->addConstraint($constraint);

        $this->getManager()->persist($plan);
        $this->getManager()->flush();

        foreach ($plan->getConstraints() as $associatedConstraint) {
            $this->assertNotNull($associatedConstraint->getId());
        }
    }


    public function testAddConstraintsToPlan() {
        $plan = new Plan();
        $plan->setName('test-foo-plan');

        $constraint1 = new Constraint();
        $constraint1->setName('foo');
        $plan->addConstraint($constraint1);

        $constraint2 = new Constraint();
        $constraint2->setName('bar');
        $plan->addConstraint($constraint2);

        $this->getManager()->persist($plan);
        $this->getManager()->flush();

        foreach ($plan->getConstraints() as $associatedConstraint) {
            $this->assertNotNull($associatedConstraint->getId());
        }
    }


    public function testRemoveConstraintFromPlan() {
        $plan = new Plan();
        $plan->setName('test-foo-plan');

        $constraint1 = new Constraint();
        $constraint1->setName('foo');
        $plan->addConstraint($constraint1);

        $constraint2 = new Constraint();
        $constraint2->setName('bar');
        $plan->addConstraint($constraint2);

        $this->getManager()->persist($plan);
        $this->getManager()->flush();

        $this->assertEquals(2, $plan->getConstraints()->count());

        $plan->removeConstraint($constraint1);
        $this->getManager()->persist($plan);
        $this->getManager()->flush();

        $this->assertEquals(1, $plan->getConstraints()->count());
        $this->assertFalse($plan->getConstraints()->contains($constraint1));
        $this->assertTrue($plan->getConstraints()->contains($constraint2));

    }


    public function testPersistAndRetrievePlanWithConstraints() {
        $plan = new Plan();
        $plan->setName('test-foo-plan');

        $constraint1 = new Constraint();
        $constraint1->setName('foo');
        $plan->addConstraint($constraint1);

        $constraint2 = new Constraint();
        $constraint2->setName('bar');
        $plan->addConstraint($constraint2);

        $this->getManager()->persist($plan);
        $this->getManager()->flush();

        $this->getManager()->clear();

        $planEntityRepository = $this->getManager()->getRepository('SimplyTestable\ApiBundle\Entity\Account\Plan\Plan');
        $retrievedPlan = $planEntityRepository->find($plan->getId());

        $this->assertEquals(2, $retrievedPlan->getConstraints()->count());
    }


    public function testHasConstraintNamed() {
        $plan = new Plan();
        $plan->setName('test-foo-plan');

        $constraint1 = new Constraint();
        $constraint1->setName('foo');
        $plan->addConstraint($constraint1);

        $constraint2 = new Constraint();
        $constraint2->setName('bar');
        $plan->addConstraint($constraint2);

        $this->assertTrue($plan->hasConstraintNamed('foo'));
        $this->assertTrue($plan->hasConstraintNamed('bar'));
        $this->assertFalse($plan->hasConstraintNamed('foobar'));
    }


    public function testGetConstraintNamed() {
        $plan = new Plan();
        $plan->setName('test-foo-plan');

        $constraint1 = new Constraint();
        $constraint1->setName('foo');
        $plan->addConstraint($constraint1);

        $constraint2 = new Constraint();
        $constraint2->setName('bar');
        $plan->addConstraint($constraint2);

        $this->assertEquals('foo', $plan->getConstraintNamed('foo')->getName());
        $this->assertEquals('bar', $plan->getConstraintNamed('bar')->getName());
        $this->assertNull($plan->getConstraintNamed('foobar'));
    }

}
