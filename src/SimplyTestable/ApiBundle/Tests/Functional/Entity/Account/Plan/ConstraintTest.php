<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Entity\Account\Plan;

use Doctrine\ORM\EntityManagerInterface;
use SimplyTestable\ApiBundle\Entity\Account\Plan\Plan;
use SimplyTestable\ApiBundle\Tests\Factory\PlanFactory;
use SimplyTestable\ApiBundle\Tests\Functional\BaseSimplyTestableTestCase;
use SimplyTestable\ApiBundle\Entity\Account\Plan\Constraint;

class ConstraintTest extends BaseSimplyTestableTestCase
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var Plan
     */
    private $plan;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->entityManager = $this->container->get('doctrine.orm.entity_manager');

        $planFactory = new PlanFactory($this->container);
        $this->plan = $planFactory->create();
    }

    public function testPersist()
    {
        $constraint = new Constraint();
        $constraint->setName('foo');
        $constraint->setPlan($this->plan);

        $this->entityManager->persist($constraint);
        $this->entityManager->flush();

        $this->assertNotNull($constraint->getId());
    }

    public function testUtf8Name()
    {
        $name = 'foo-ɸ';

        $constraint = new Constraint();
        $constraint->setName($name);
        $constraint->setPlan($this->plan);

        $this->entityManager->persist($constraint);
        $this->entityManager->flush();

        $constraintId = $constraint->getId();

        $this->entityManager->clear();

        $constraintRepository = $this->entityManager->getRepository(Constraint::class);

        $this->assertEquals($name, $constraintRepository->find($constraintId)->getName());
    }
}
