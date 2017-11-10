<?php

namespace Tests\ApiBundle\Functional\Entity\Account\Plan;

use Doctrine\DBAL\DBALException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Tests\ApiBundle\Functional\AbstractBaseTestCase;
use SimplyTestable\ApiBundle\Entity\Account\Plan\Constraint;
use SimplyTestable\ApiBundle\Entity\Account\Plan\Plan;

class PlanTest extends AbstractBaseTestCase
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var EntityRepository
     */
    private $planRepository;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->entityManager = $this->container->get('doctrine.orm.entity_manager');
        $this->planRepository = $this->entityManager->getRepository(Plan::class);
    }

    public function testUtf8Name()
    {
        $name = 'test-ɸ';

        $plan = new Plan();
        $plan->setName($name);

        $this->entityManager->persist($plan);
        $this->entityManager->flush();

        $planId = $plan->getId();

        $this->entityManager->clear();
        $this->assertEquals($name, $this->planRepository->find($planId)->getName());
    }

    public function testUtf8StripeId()
    {
        $name = 'test-foo-plan';
        $stripeId = 'ɸ';

        $plan = new Plan();
        $plan->setName($name);
        $plan->setStripeId($stripeId);

        $this->entityManager->persist($plan);
        $this->entityManager->flush();

        $planId = $plan->getId();

        $this->entityManager->clear();
        $this->assertEquals($stripeId, $this->planRepository->find($planId)->getStripeId());
    }

    public function testCreateAndPersistWithNoConstraints()
    {
        $plan = new Plan();
        $plan->setName('test-foo-plan');

        $this->entityManager->persist($plan);
        $this->entityManager->flush();

        $this->assertNotNull($plan->getId());
    }

    public function testDefaultVisibilityIsFalse()
    {
        $plan = new Plan();
        $plan->setName('test-foo-plan');

        $this->entityManager->persist($plan);
        $this->entityManager->flush();

        $this->assertFalse($plan->getIsVisible());
    }

    public function testMakeVisible()
    {
        $plan = new Plan();
        $plan->setName('test-foo-plan');
        $plan->setIsVisible(true);

        $this->entityManager->persist($plan);
        $this->entityManager->flush();

        $this->assertTrue($plan->getIsVisible());
    }

    public function testNameUniqueness()
    {
        $plan1 = new Plan();
        $plan1->setName('test-bar-plan');

        $this->entityManager->persist($plan1);
        $this->entityManager->flush();

        $plan2 = new Plan();
        $plan2->setName('test-bar-plan');

        $this->entityManager->persist($plan2);

        try {
            $this->entityManager->flush();
            $this->fail('\Doctrine\DBAL\DBALException not raised for non-unique name');
        } catch (DBALException $doctrineDbalException) {
            $this->assertEquals(23000, $doctrineDbalException->getPrevious()->getCode());
        }
    }

    public function testAddConstraintToPlan()
    {
        $plan = new Plan();
        $plan->setName('test-foo-plan');

        $constraint = new Constraint();
        $constraint->setName('foo');

        $plan->addConstraint($constraint);

        $this->entityManager->persist($plan);
        $this->entityManager->flush();

        foreach ($plan->getConstraints() as $associatedConstraint) {
            $this->assertNotNull($associatedConstraint->getId());
        }
    }

    public function testAddConstraintsToPlan()
    {
        $plan = new Plan();
        $plan->setName('test-foo-plan');

        $constraint1 = new Constraint();
        $constraint1->setName('foo');
        $plan->addConstraint($constraint1);

        $constraint2 = new Constraint();
        $constraint2->setName('bar');
        $plan->addConstraint($constraint2);

        $this->entityManager->persist($plan);
        $this->entityManager->flush();

        foreach ($plan->getConstraints() as $associatedConstraint) {
            $this->assertNotNull($associatedConstraint->getId());
        }
    }

    public function testRemoveConstraintFromPlan()
    {
        $plan = new Plan();
        $plan->setName('test-foo-plan');

        $constraint1 = new Constraint();
        $constraint1->setName('foo');
        $plan->addConstraint($constraint1);

        $constraint2 = new Constraint();
        $constraint2->setName('bar');
        $plan->addConstraint($constraint2);

        $this->entityManager->persist($plan);
        $this->entityManager->flush();

        $this->assertEquals(2, $plan->getConstraints()->count());

        $plan->removeConstraint($constraint1);
        $this->entityManager->persist($plan);
        $this->entityManager->flush();

        $this->assertEquals(1, $plan->getConstraints()->count());
        $this->assertFalse($plan->getConstraints()->contains($constraint1));
        $this->assertTrue($plan->getConstraints()->contains($constraint2));
    }

    public function testPersistAndRetrievePlanWithConstraints()
    {
        $plan = new Plan();
        $plan->setName('test-foo-plan');

        $constraint1 = new Constraint();
        $constraint1->setName('foo');
        $plan->addConstraint($constraint1);

        $constraint2 = new Constraint();
        $constraint2->setName('bar');
        $plan->addConstraint($constraint2);

        $this->entityManager->persist($plan);
        $this->entityManager->flush();

        $this->entityManager->clear();

        $retrievedPlan = $this->planRepository->find($plan->getId());

        $this->assertEquals(2, $retrievedPlan->getConstraints()->count());
    }

    public function testGetConstraintNamed()
    {
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
