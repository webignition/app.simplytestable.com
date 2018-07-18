<?php

namespace Tests\AppBundle\Functional\Entity\Account\Plan;

use Tests\AppBundle\Factory\PlanFactory;
use Tests\AppBundle\Functional\AbstractBaseTestCase;
use AppBundle\Entity\Account\Plan\Constraint;

class ConstraintTest extends AbstractBaseTestCase
{
    /**
     * @dataProvider persistDataProvider
     *
     * @param string $name
     */
    public function testPersist($name)
    {
        $entityManager = self::$container->get('doctrine.orm.entity_manager');
        $constraintRepository = $entityManager->getRepository(Constraint::class);

        $planFactory = new PlanFactory(self::$container);
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
