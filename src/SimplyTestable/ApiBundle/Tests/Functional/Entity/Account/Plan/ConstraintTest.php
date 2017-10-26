<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Entity\Account\Plan;

use SimplyTestable\ApiBundle\Tests\Factory\PlanFactory;
use SimplyTestable\ApiBundle\Tests\Functional\BaseSimplyTestableTestCase;
use SimplyTestable\ApiBundle\Entity\Account\Plan\Constraint;

class ConstraintTest extends BaseSimplyTestableTestCase
{
    /**
     * @dataProvider persistDataProvider
     *
     * @param string $name
     */
    public function testPersist($name)
    {
        $entityManager = $this->container->get('doctrine.orm.entity_manager');

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

        $constraintRepository = $entityManager->getRepository(Constraint::class);

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
