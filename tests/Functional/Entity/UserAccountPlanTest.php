<?php

namespace App\Tests\Functional\Entity;

use App\Repository\UserAccountPlanRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Account\Plan\Plan;
use App\Services\UserAccountPlanService;
use App\Tests\Factory\PlanFactory;
use App\Tests\Factory\UserFactory;
use App\Tests\Functional\AbstractBaseTestCase;
use App\Entity\UserAccountPlan;

class UserAccountPlanTest extends AbstractBaseTestCase
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var UserFactory
     */
    private $userFactory;

    /**
     * @var Plan
     */
    private $plan;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->entityManager = self::$container->get('doctrine.orm.entity_manager');

        $this->userFactory = new UserFactory(self::$container);

        $planFactory = new PlanFactory(self::$container);
        $this->plan = $planFactory->create();
    }

    public function testUtf8StripeCustomer()
    {
        $userAccountPlanRepository = self::$container->get(UserAccountPlanRepository::class);

        $stripeCustomer = 'test-ɸ';

        $user = $this->userFactory->create();

        $userAccountPlan = new UserAccountPlan();
        $userAccountPlan->setUser($user);
        $userAccountPlan->setPlan($this->plan);
        $userAccountPlan->setStripeCustomer($stripeCustomer);

        $this->entityManager->persist($userAccountPlan);
        $this->entityManager->flush();

        $userAccountPlanId = $userAccountPlan->getId();

        $this->entityManager->clear();

        $this->assertEquals(
            $stripeCustomer,
            $userAccountPlanRepository->find($userAccountPlanId)->getStripeCustomer()
        );
    }

    public function testPersist()
    {
        $user = $this->userFactory->create();

        $userAccountPlan = new UserAccountPlan();
        $userAccountPlan->setUser($user);
        $userAccountPlan->setPlan($this->plan);

        $this->entityManager->persist($userAccountPlan);
        $this->entityManager->flush();

        $this->assertNotNull($userAccountPlan->getId());
    }

    public function testApplyOnePlanToMultipleUsers()
    {
        $user1 = $this->userFactory->create([
            UserFactory::KEY_EMAIL => 'user1@example.com',
        ]);

        $user2 = $this->userFactory->create([
            UserFactory::KEY_EMAIL => 'user2@example.com',
        ]);

        $userAccountPlan1 = new UserAccountPlan();
        $userAccountPlan1->setUser($user1);
        $userAccountPlan1->setPlan($this->plan);

        $userAccountPlan2 = new UserAccountPlan();
        $userAccountPlan2->setUser($user2);
        $userAccountPlan2->setPlan($this->plan);

        $this->entityManager->persist($userAccountPlan1);
        $this->entityManager->persist($userAccountPlan2);
        $this->entityManager->flush();

        $this->assertNotNull($userAccountPlan1->getId());
        $this->assertNotNull($userAccountPlan2->getId());
    }

    public function testDefaultStartTrialPeriod()
    {
        $userAccountPlanService = self::$container->get(UserAccountPlanService::class);
        $defaultTrialPeriod = getenv('DEFAULT_TRIAL_PERIOD');
        $user = $this->userFactory->create();

        $userAccountPlan = new UserAccountPlan();
        $userAccountPlan->setUser($user);
        $userAccountPlan->setPlan($this->plan);

        $this->assertEquals($defaultTrialPeriod, $userAccountPlan->getStartTrialPeriod());

        $this->entityManager->persist($userAccountPlan);
        $this->entityManager->flush();
        $this->entityManager->clear();

        $this->assertEquals($defaultTrialPeriod, $userAccountPlanService->getForUser($user)->getStartTrialPeriod());
    }
}
