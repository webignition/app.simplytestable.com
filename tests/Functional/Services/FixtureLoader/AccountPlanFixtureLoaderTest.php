<?php

namespace App\Tests\Functional\Services\FixtureLoader;

use App\Entity\Account\Plan\Constraint;
use App\Entity\Account\Plan\Plan;
use App\Entity\UserAccountPlan;
use App\Services\FixtureLoader\AccountPlanFixtureLoader;
use App\Tests\Functional\AbstractBaseTestCase;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;

class AccountPlanFixtureLoaderTest extends AbstractBaseTestCase
{
    /**
     * @var AccountPlanFixtureLoader
     */
    private $accountPlanFixtureLoader;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var EntityRepository|ObjectRepository
     */
    private $repository;

    /**
     * @var array
     */
    private $accountPlansData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->accountPlanFixtureLoader = self::$container->get(AccountPlanFixtureLoader::class);
        $this->entityManager = self::$container->get(EntityManagerInterface::class);

        $this->repository = $this->entityManager->getRepository(Plan::class);

        $this->removeAllForEntity(Constraint::class);
        $this->removeAllForEntity(UserAccountPlan::class);
        $this->removeAllForEntity(Plan::class);

        $this->entityManager->flush();

        $accountPlansDataProvider = self::$container->get('app.services.data-provider.account-plans');
        $this->accountPlansData = $accountPlansDataProvider->getData();
    }

    public function testLoadFromEmpty()
    {
        $this->assertEmpty($this->repository->findAll());

        $this->accountPlanFixtureLoader->load();

        $this->assertRepositoryAccountPlans($this->accountPlansData);
    }

    public function testLoadFromNonEmpty()
    {
        $this->assertEmpty($this->repository->findAll());

        $accountPlanData = $this->accountPlansData[0];

        $reflectionMethod = new \ReflectionMethod(AccountPlanFixtureLoader::class, 'loadAccountPlan');
        $reflectionMethod->setAccessible(true);
        $reflectionMethod->invoke($this->accountPlanFixtureLoader, $accountPlanData);

        $this->assertRepositoryAccountPlans([$accountPlanData]);

        $this->accountPlanFixtureLoader->load();
        $this->assertRepositoryAccountPlans($this->accountPlansData);
    }

    private function assertRepositoryAccountPlans(array $accountPlansData)
    {
        $repositoryAccountPlans = $this->repository->findAll();

        $this->assertCount(count($accountPlansData), $repositoryAccountPlans);

        /* @var Plan[] $accountPlans */
        $accountPlans = [];

        foreach ($repositoryAccountPlans as $accountPlan) {
            $accountPlans[$accountPlan->getName()] = $accountPlan;
        }

        foreach ($accountPlansData as $accountPlanData) {
            $names = $accountPlanData['names'];
            $expectedName = $names[count($names) - 1];

            $accountPlan = $accountPlans[$expectedName];

            $this->assertEquals($expectedName, $accountPlan->getName());
            $this->assertEquals($accountPlanData['visible'] ?? false, $accountPlan->getIsVisible());
            $this->assertEquals($accountPlanData['premium'] ?? false, $accountPlan->getIsPremium());
            $this->assertEquals($accountPlanData['stripeId'] ?? null, $accountPlan->getStripeId());

            $expectedConstraintData = $accountPlanData['constraints'];
            $constraints = $accountPlan->getConstraints();

            $this->assertCount(count($expectedConstraintData), $constraints);

            foreach ($expectedConstraintData as $name => $limit) {
                $this->assertEquals(
                    $accountPlan->getConstraintNamed($name)->getLimit(),
                    $limit
                );
            }
        }
    }

    private function removeAllForEntity(string $entityClass)
    {
        $repository = $this->entityManager->getRepository($entityClass);
        $entities = $repository->findAll();
        foreach ($entities as $entity) {
            $this->entityManager->remove($entity);
        }
    }
}
