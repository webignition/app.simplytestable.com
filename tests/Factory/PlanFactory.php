<?php

namespace App\Tests\Factory;

use App\Entity\Account\Plan\Plan;
use Symfony\Component\DependencyInjection\ContainerInterface;

class PlanFactory
{
    const KEY_NAME = 'name';
    const KEY_CONSTRAINTS = 'constraints';

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var ConstraintFactory
     */
    private $constraintFactory;

    /**
     * @var array
     */
    private $defaultPlanValues = [
        self::KEY_NAME => 'Plan',
        self::KEY_CONSTRAINTS => [],
    ];

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->constraintFactory = new ConstraintFactory($container);
    }

    /**
     * @param array $planValues
     *
     * @return Plan
     */
    public function create($planValues = [])
    {
        foreach ($this->defaultPlanValues as $key => $value) {
            if (!isset($planValues[$key])) {
                $planValues[$key] = $value;
            }
        }

        $plan = new Plan();
        $plan->setName($planValues[self::KEY_NAME]);

        $entityManager = $this->container->get('doctrine.orm.entity_manager');
        $entityManager->persist($plan);
        $entityManager->flush($plan);

        foreach ($planValues[self::KEY_CONSTRAINTS] as $constraintValues) {
            $constraint = $this->constraintFactory->create($plan, $constraintValues);
            $plan->addConstraint($constraint);
        }

        $entityManager->persist($plan);
        $entityManager->flush($plan);

        return $plan;
    }
}
