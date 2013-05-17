<?php

namespace SimplyTestable\ApiBundle\Tests\Entity\Account\Plan;

use SimplyTestable\ApiBundle\Tests\BaseSimplyTestableTestCase;
use SimplyTestable\ApiBundle\Entity\Account\Plan\Constraint;
use SimplyTestable\ApiBundle\Entity\Account\Plan\Plan;

class PlanTest extends BaseSimplyTestableTestCase {

    public function testCreateAndPersistWithNoConstraints() {
        $plan = new Plan();
        $plan->setName('foo-plan');
      
        $this->getEntityManager()->persist($plan);
        $this->getEntityManager()->flush();
        
        $this->assertNotNull($plan->getId());
    }
    
    
    public function testDefaultVisibilityIsFalse() {
        $plan = new Plan();
        $plan->setName('foo-plan');
      
        $this->getEntityManager()->persist($plan);
        $this->getEntityManager()->flush();
        
        $this->assertFalse($plan->getIsVisible());
    }
    
    
    public function testMakeVisible() {
        $plan = new Plan();
        $plan->setName('foo-plan');
        $plan->setIsVisible(true);
      
        $this->getEntityManager()->persist($plan);
        $this->getEntityManager()->flush();
        
        $this->assertTrue($plan->getIsVisible());
    }    
    
    
    public function testNameUniqueness() {
        $plan1 = new Plan();
        $plan1->setName('bar-plan');
      
        $this->getEntityManager()->persist($plan1);
        $this->getEntityManager()->flush();     
        
        $plan2 = new Plan();
        $plan2->setName('bar-plan');           
    
        $this->getEntityManager()->persist($plan2);
        
        try {
            $this->getEntityManager()->flush();
            $this->fail('\Doctrine\DBAL\DBALException not raised for non-unique name');
        } catch (\Doctrine\DBAL\DBALException $doctrineDbalException) {
            $this->assertEquals(23000, $doctrineDbalException->getPrevious()->getCode());
        }        
    }
    
    
    public function testAddConstraintToPlan() {
        $plan = new Plan();
        $plan->setName('foo-plan');
        
        $constraint = new Constraint();
        $constraint->setName('foo');
        
        $plan->addConstraint($constraint);
      
        $this->getEntityManager()->persist($plan);
        $this->getEntityManager()->flush();    
        
        foreach ($plan->getConstraints() as $associatedConstraint) {
            $this->assertNotNull($associatedConstraint->getId()); 
        }
    }
    
    
    public function testAddConstraintsToPlan() {
        $plan = new Plan();
        $plan->setName('foo-plan');
        
        $constraint1 = new Constraint();
        $constraint1->setName('foo');        
        $plan->addConstraint($constraint1);
        
        $constraint2 = new Constraint();
        $constraint2->setName('bar');        
        $plan->addConstraint($constraint2);        
      
        $this->getEntityManager()->persist($plan);
        $this->getEntityManager()->flush();      
        
        foreach ($plan->getConstraints() as $associatedConstraint) {
            $this->assertNotNull($associatedConstraint->getId()); 
        }
    } 
    
    
    public function testRemoveConstraintFromPlan() {
        $plan = new Plan();
        $plan->setName('foo-plan');
        
        $constraint1 = new Constraint();
        $constraint1->setName('foo');        
        $plan->addConstraint($constraint1);
        
        $constraint2 = new Constraint();
        $constraint2->setName('bar');        
        $plan->addConstraint($constraint2);        
      
        $this->getEntityManager()->persist($plan);
        $this->getEntityManager()->flush();       
        
        $this->assertEquals(2, $plan->getConstraints()->count());
        
        $plan->removeConstraint($constraint1);
        $this->getEntityManager()->persist($plan);
        $this->getEntityManager()->flush();          
        
        $this->assertEquals(1, $plan->getConstraints()->count());
        $this->assertFalse($plan->getConstraints()->contains($constraint1));
        $this->assertTrue($plan->getConstraints()->contains($constraint2));
        
    } 
    
    
    public function testPersistAndRetrievePlanWithConstraints() {
        $plan = new Plan();
        $plan->setName('foo-plan');
        
        $constraint1 = new Constraint();
        $constraint1->setName('foo');        
        $plan->addConstraint($constraint1);
        
        $constraint2 = new Constraint();
        $constraint2->setName('bar');        
        $plan->addConstraint($constraint2);              

        $this->getEntityManager()->persist($plan);
        $this->getEntityManager()->flush(); 
        
        $this->getEntityManager()->clear();
        
        $planEntityRepository = $this->getEntityManager()->getRepository('SimplyTestable\ApiBundle\Entity\Account\Plan\Plan');
        $retrievedPlan = $planEntityRepository->find($plan->getId());
        
        $this->assertEquals(2, $retrievedPlan->getConstraints()->count());
    }
    
    
    public function testHasConstraintNamed() {
        $plan = new Plan();
        $plan->setName('foo-plan');
        
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
        $plan->setName('foo-plan');
        
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
