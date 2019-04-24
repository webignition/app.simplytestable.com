<?php

namespace App\Tests\Functional\Services\FixtureLoader;

use App\Entity\Account\Plan\Constraint;
use App\Entity\Account\Plan\Plan;
use App\Entity\UserAccountPlan;
use App\Services\FixtureLoader\AccountPlanFixtureLoader;

class AccountPlanFixtureLoaderTest extends AbstractFixtureLoaderTest
{
    /**
     * @var AccountPlanFixtureLoader
     */
    private $accountPlanFixtureLoader;

    /**
     * @var array
     */
    private $accountPlansData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->accountPlanFixtureLoader = self::$container->get(AccountPlanFixtureLoader::class);
        $accountPlansDataProvider = self::$container->get('app.services.data-provider.account-plans');
        $this->accountPlansData = $accountPlansDataProvider->getData();
    }

    protected function getEntityClass(): string
    {
        return Plan::class;
    }

    protected function getEntityClassesToRemove(): array
    {
        return [
            Constraint::class,
            UserAccountPlan::class,
            Plan::class,
        ];
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
}
