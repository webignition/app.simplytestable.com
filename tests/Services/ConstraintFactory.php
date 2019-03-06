<?php

namespace App\Tests\Services;

use App\Entity\Account\Plan\Constraint;
use App\Entity\Account\Plan\Plan;
use Doctrine\ORM\EntityManagerInterface;

class ConstraintFactory
{
    const KEY_NAME = 'name';
    const KEY_LIMIT = 'limit';

    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function create(Plan $plan, array $constraintValues): Constraint
    {
        $constraint = new Constraint();
        $constraint->setName($constraintValues[self::KEY_NAME]);
        $constraint->setPlan($plan);
        $constraint->setLimit($constraintValues[self::KEY_LIMIT]);

        $this->entityManager->persist($constraint);
        $this->entityManager->flush();

        return $constraint;
    }
}
