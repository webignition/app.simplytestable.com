<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services;

use SimplyTestable\ApiBundle\Entity\Account\Plan\Plan;
use SimplyTestable\ApiBundle\Entity\UserAccountPlan;
use SimplyTestable\ApiBundle\Services\UserAccountPlanService;
use SimplyTestable\ApiBundle\Tests\Factory\StripeApiFixtureFactory;
use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;
use SimplyTestable\ApiBundle\Tests\Functional\BaseSimplyTestableTestCase;
use SimplyTestable\ApiBundle\Exception\Services\UserAccountPlan\Exception as UserAccountPlanServiceException;

class UserAccountPlanServiceTest extends BaseSimplyTestableTestCase
{
    /**
     * @var UserAccountPlanService
     */
    private $userAccountPlanService;

    /**
     * @var UserFactory
     */
    private $userFactory;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->userAccountPlanService = $this->container->get('simplytestable.services.useraccountplanservice');
        $this->userFactory = new UserFactory($this->container);
    }

    public function testSubscribeUserBelongsToTeam()
    {
        $this->setExpectedException(
            UserAccountPlanServiceException::class,
            '',
            UserAccountPlanServiceException::CODE_USER_IS_TEAM_MEMBER
        );

        $users = $this->userFactory->createPublicPrivateAndTeamUserSet();
        $user = $users['member1'];

        $accountPlan = new Plan();

        $this->userAccountPlanService->subscribe($user, $accountPlan);
    }

    /**
     * @dataProvider subscribeActionNoExistingUserAccountPlanDataProvider
     *
     * @param string $planName
     * @param string $expectedStripeCustomer
     * @param int $expectedStartTrialPeriod
     */
    public function testSubscribeActionNoExistingUserAccountPlan(
        $httpFixtures,
        $planName,
        $expectedStripeCustomer,
        $expectedStartTrialPeriod
    ) {
        $accountPlanService = $this->container->get('simplytestable.services.accountplanservice');

        StripeApiFixtureFactory::set($httpFixtures);

        $user = $this->userFactory->create([
            UserFactory::KEY_PLAN_NAME => null,
        ]);

        $accountPlan = $accountPlanService->find($planName);

        $userAccountPlan = $this->userAccountPlanService->subscribe($user, $accountPlan);

        $this->assertInstanceOf(UserAccountPlan::class, $userAccountPlan);

        $this->assertEquals($user, $userAccountPlan->getUser());
        $this->assertEquals($accountPlan, $userAccountPlan->getPlan());
        $this->assertTrue($userAccountPlan->getIsActive());

        $this->assertEquals($expectedStripeCustomer, $userAccountPlan->getStripeCustomer());
        $this->assertEquals($expectedStartTrialPeriod, $userAccountPlan->getStartTrialPeriod());
    }

    /**
     * @return array
     */
    public function subscribeActionNoExistingUserAccountPlanDataProvider()
    {
        return [
            'basic plan' => [
                'httpFixtures' => [],
                'planName' => 'basic',
                'expectedStripeCustomer' => null,
                'expectedStartTrialPeriod' => 30,
            ],
            'personal plan' => [
                'httpFixtures' => [
                    StripeApiFixtureFactory::load('customer-nocard-nosub'),
                    StripeApiFixtureFactory::load('customer-nocard-nosub'),
                    StripeApiFixtureFactory::load('customer-hascard-hassub'),
                ],
                'planName' => 'personal',
                'expectedStripeCustomer' => 'b58996c504c5638798eb6b511e6f49af',
                'expectedStartTrialPeriod' => 30,
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        parent::tearDown();

        \Mockery::close();
    }
}
