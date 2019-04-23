<?php

namespace App\Services;

use App\Entity\Account\Plan\Constraint;
use App\Entity\Account\Plan\Plan;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;

class AccountPlanMigrator
{
    private $resourceLoader;
    private $entityManager;

    /**
     * @var EntityRepository|ObjectRepository
     */
    private $repository;

    public function __construct(YamlResourceLoader $resourceLoader, EntityManagerInterface $entityManager)
    {
        $this->resourceLoader = $resourceLoader;
        $this->entityManager = $entityManager;
        $this->repository = $entityManager->getRepository(Plan::class);
    }

    public function migrate()
    {
        $data = $this->resourceLoader->getData();

        foreach ($data as $accountPlanData) {
            $this->migrateAccountPlan($accountPlanData);
        }
    }

    private function migrateAccountPlan(array $accountPlanData)
    {
        $names = $accountPlanData['names'];

        $accountPlan = $this->findPlanByNameHistory($names);

        if (null === $accountPlan) {
            $accountPlan = new Plan();
        }

        $accountPlan->setName($names[count($names) - 1]);

        $isVisible = $accountPlanData['visible'] ?? false;
        $accountPlan->setIsVisible($isVisible);

        $isPremium = $accountPlanData['premium'] ?? false;
        $accountPlan->setIsPremium($isPremium);

        $stripeId = $accountPlanData['stripeId'] ?? null;
        $accountPlan->setStripeId($stripeId);

        $constraints = $accountPlanData['constraints'] ?? [];

        foreach ($constraints as $name => $limit) {
            $constraint = $this->getConstraintFromPlanByName($accountPlan, $name);
            $isNewConstraint = false;

            if (is_null($constraint)) {
                $constraint = new Constraint();
                $isNewConstraint = true;
            }

            $constraint->setName($name);
            if (is_int($limit)) {
                $constraint->setLimit($limit);
            }

            if ($isNewConstraint) {
                $accountPlan->addConstraint($constraint);
            }
        }

        $this->entityManager->persist($accountPlan);
        $this->entityManager->flush();
    }

    /**
     * @param string[] $names
     *
     * @return Plan|null
     */
    private function findPlanByNameHistory(array $names): ?Plan
    {
        foreach ($names as $name) {
            /* @var Plan $plan */
            $plan = $this->repository->findOneBy([
                'name' => $name,
            ]);

            if (!empty($plan)) {
                return $plan;
            }
        }

        return null;
    }

    private function getConstraintFromPlanByName(Plan $plan, string $constraintName): ?Constraint
    {
        foreach ($plan->getConstraints() as $constraint) {
            /* @var $constraint Constraint */
            if ($constraint->getName() == $constraintName) {
                return $constraint;
            }
        }

        return null;
    }
}
