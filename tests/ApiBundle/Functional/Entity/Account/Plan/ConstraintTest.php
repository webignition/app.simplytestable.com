<?php

namespace Tests\ApiBundle\Functional\Entity\Account\Plan;

use Tests\ApiBundle\Factory\PlanFactory;
use Tests\ApiBundle\Functional\AbstractBaseTestCase;
use SimplyTestable\ApiBundle\Entity\Account\Plan\Constraint;

class ConstraintTest extends AbstractBaseTestCase
{
    /**
     * @dataProvider persistDataProvider
     *
     * @param string $name
     */
    public function testPersist($name)
    {
        $entityManager = $this->container->get('doctrine.orm.entity_manager');
        $constraintRepository = $entityManager->getRepository(Constraint::class);

        $planFactory = new PlanFactory($this->container);
        $plan = $planFactory->create();

        $constraint = new Constraint();
        $constraint->setName($name);
        $constraint->setPlan($plan);

        $entityManager->persist($constraint);
        $entityManager->flush($constraint);

        $this->assertNotNull($constraint->getId());

        $constraintId = $constraint->getId();

        $entityManager->clear();

        $this->assertEquals($name, $constraintRepository->find($constraintId)->getName());
    }

    /**
     * @return array
     */
    public function persistDataProvider()
    {
        return [
            'foo' => [
                'name' => 'foo',
            ],
            'utf8 content' => [
                'name' => 'foo-ɸ',
            ],
        ];
    }
}
