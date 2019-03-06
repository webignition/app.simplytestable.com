<?php

namespace App\Tests\Functional\Entity\Account\Plan;

use App\Tests\Services\PlanFactory;
use App\Tests\Functional\AbstractBaseTestCase;
use App\Entity\Account\Plan\Constraint;

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

        $planFactory = self::$container->get(PlanFactory::class);
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
