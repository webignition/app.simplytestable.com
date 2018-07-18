<?php

namespace Tests\ApiBundle\Functional\Entity;

use Doctrine\ORM\EntityManagerInterface;
use SimplyTestable\ApiBundle\Entity\Account\Plan\Plan;
use SimplyTestable\ApiBundle\Services\UserAccountPlanService;
use Tests\ApiBundle\Factory\PlanFactory;
use Tests\ApiBundle\Factory\UserFactory;
use Tests\ApiBundle\Functional\AbstractBaseTestCase;
use SimplyTestable\ApiBundle\Entity\UserAccountPlan;

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
        $entityManager = self::$container->get('doctrine.orm.entity_manager');
        $userAccountPlanRepository = $entityManager->getRepository(UserAccountPlan::class);

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

        $defaultStartTrialPeriod = self::$container->getParameter('default_trial_period');

        $user = $this->userFactory->create();

        $userAccountPlan = new UserAccountPlan();
        $userAccountPlan->setUser($user);
        $userAccountPlan->setPlan($this->plan);

        $this->assertEquals(
            $defaultStartTrialPeriod,
            $userAccountPlan->getStartTrialPeriod()
        );

        $this->entityManager->persist($userAccountPlan);
        $this->entityManager->flush();
        $this->entityManager->clear();

        $this->assertEquals(
            $defaultStartTrialPeriod,
            $userAccountPlanService->getForUser($user)->getStartTrialPeriod()
        );
    }
}
