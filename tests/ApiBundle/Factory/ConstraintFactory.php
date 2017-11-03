<?php

namespace Tests\ApiBundle\Factory;

use SimplyTestable\ApiBundle\Entity\Account\Plan\Constraint;
use SimplyTestable\ApiBundle\Entity\Account\Plan\Plan;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ConstraintFactory
{
    const KEY_NAME = 'name';
    const KEY_LIMIT = 'limit';

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @param Plan $plan
     * @param array $constraintValues
     *
     * @return Constraint
     */
    public function create(Plan $plan, $constraintValues)
    {
        $constraint = new Constraint();
        $constraint->setName($constraintValues[self::KEY_NAME]);
        $constraint->setPlan($plan);
        $constraint->setLimit($constraintValues[self::KEY_LIMIT]);

        $entityManager = $this->container->get('doctrine.orm.entity_manager');
        $entityManager->persist($constraint);
        $entityManager->flush();

        return $constraint;
    }
}
