<?php

namespace App\Services;

use App\Entity\Account\Plan\Constraint;
use App\Entity\Account\Plan\Plan;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Console\Output\OutputInterface;

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

    public function migrate(?OutputInterface $output = null)
    {
        if ($output) {
            $output->writeln('Migrating account plans ...');
        }

        $data = $this->resourceLoader->getData();

        foreach ($data as $accountPlanData) {
            $this->migrateAccountPlan($accountPlanData, $output);
        }

        if ($output) {
            $output->writeln('');
        }
    }

    private function migrateAccountPlan(array $accountPlanData, ?OutputInterface $output = null)
    {
        if ($output) {
            $output->writeln('');
        }

        $names = $accountPlanData['names'];
        $name = $names[count($names) - 1];

        $accountPlan = $this->findPlanByNameHistory($names);

        if ($output) {
            $output->writeln("  " . '<comment>' . $name . '</comment>');
        }

        if (null === $accountPlan) {
            if ($output) {
                $output->write(' <fg=cyan>creating</>');
            }

            $accountPlan = new Plan();
        }

        $accountPlan->setName($name);

        $isVisible = $accountPlanData['visible'] ?? false;
        if ($output) {
            $output->writeln('    <comment>visible:</comment> ' . ($isVisible ? 'true' : 'false'));
        }

        $accountPlan->setIsVisible($isVisible);

        $isPremium = $accountPlanData['premium'] ?? false;
        if ($output) {
            $output->writeln('    <comment>premium:</comment> ' . ($isPremium ? 'true' : 'false'));
        }

        $accountPlan->setIsPremium($isPremium);

        $stripeId = $accountPlanData['stripeId'] ?? null;
        if ($output) {
            $output->writeln('    <comment>stripeId:</comment> ' . ($stripeId ?? '---'));
        }

        $accountPlan->setStripeId($stripeId);

        $constraints = $accountPlanData['constraints'] ?? [];

        foreach ($constraints as $constraintName => $limit) {
            $constraint = $this->getConstraintFromPlanByName($accountPlan, $constraintName);
            $isNewConstraint = false;

            if (is_null($constraint)) {
                $constraint = new Constraint();
                $isNewConstraint = true;
            }

            $constraint->setName($constraintName);
            if (is_int($limit)) {
                $constraint->setLimit($limit);
            }

            if ($isNewConstraint) {
                $accountPlan->addConstraint($constraint);
            }

            if ($output) {
                $output->writeln('      <comment>' . $constraintName . '</comment> ' . $limit);
            }
        }

        $this->entityManager->persist($accountPlan);
        $this->entityManager->flush();

//        if ($output) {
//            $output->writeln(' <info>✓</info>');
//        }
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
