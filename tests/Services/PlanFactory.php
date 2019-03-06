<?php

namespace App\Tests\Services;

use App\Entity\Account\Plan\Plan;
use Doctrine\ORM\EntityManagerInterface;

class PlanFactory
{
    const KEY_NAME = 'name';
    const KEY_CONSTRAINTS = 'constraints';

    /**
     * @var array
     */
    private $defaultPlanValues = [
        self::KEY_NAME => 'Plan',
        self::KEY_CONSTRAINTS => [],
    ];

    private $constraintFactory;
    private $entityManager;

    public function __construct(
        ConstraintFactory $constraintFactory,
        EntityManagerInterface $entityManager
    ) {
        $this->constraintFactory = $constraintFactory;
        $this->entityManager = $entityManager;
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

        $this->entityManager->persist($plan);
        $this->entityManager->flush();

        foreach ($planValues[self::KEY_CONSTRAINTS] as $constraintValues) {
            $constraint = $this->constraintFactory->create($plan, $constraintValues);
            $plan->addConstraint($constraint);
        }

        $this->entityManager->persist($plan);
        $this->entityManager->flush();

        return $plan;
    }
}
