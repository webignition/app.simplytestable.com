<?php

namespace App\Tests\Functional\Controller\UserAccountPlanSubscription;

use App\Entity\UserAccountPlan;
use App\Services\UserAccountPlanService;
use App\Tests\Factory\StripeApiFixtureFactory;
use App\Tests\Services\UserFactory;
use App\Exception\Services\UserAccountPlan\Exception as UserAccountPlanServiceException;

/**
 * @group Controller/UserAccountPlanSubscriptionController
 */
class UserAccountPlanSubscriptionControllerSubscribeActionTest extends AbstractUserAccountPlanSubscriptionControllerTest
{
    public function testPostRequest()
    {
        StripeApiFixtureFactory::set([
            StripeApiFixtureFactory::load('customer-nocard-hassub'),
        ]);

        $userFactory = self::$container->get(UserFactory::class);
        $user = $userFactory->createAndActivateUser([
            UserFactory::KEY_PLAN_NAME => null,
        ]);

        $planName = 'personal';

        $router = self::$container->get('router');
        $requestUrl = $router->generate('user_plan_subscribe', [
            'email_canonical' => $user->getEmail(),
            'plan_name' => $planName,
        ]);

        $this->getCrawler([
            'url' => $requestUrl,
            'method' => 'POST',
            'user' => $user,
        ]);

        $response = $this->getClientResponse();

        $this->assertTrue($response->isSuccessful());
    }

    public function testSubscribeActionInvalidStripeApiKey()
    {
        StripeApiFixtureFactory::set([
            StripeApiFixtureFactory::load('invalid-api-key'),
        ], [
            401,
        ]);

        $userFactory = self::$container->get(UserFactory::class);

        $user = $userFactory->create([
            UserFactory::KEY_PLAN_NAME => null,
        ]);
        $this->setUser($user);

        $response = $this->userAccountPlanSubscriptionController->subscribeAction(
            $user,
            $user->getEmail(),
            'personal'
        );

        $this->assertTrue($response->isForbidden());
    }

    public function testSubscribeActionDecliningCard()
    {
        StripeApiFixtureFactory::set([
            StripeApiFixtureFactory::load('card-declined'),
        ], [
            402,
        ]);

        $userFactory = self::$container->get(UserFactory::class);

        $user = $userFactory->create([
            UserFactory::KEY_PLAN_NAME => null,
        ]);
        $this->setUser($user);

        $response = $this->userAccountPlanSubscriptionController->subscribeAction(
            $user,
            $user->getEmail(),
            'personal'
        );

        $this->assertTrue($response->isClientError());

        $this->assertEquals(
            [
                'card_declined',
                'Your card was declined.'
            ],
            [
                $response->headers->get('x-stripe-error-code'),
                $response->headers->get('x-stripe-error-message'),
            ]
        );
    }

    public function testSubscribeActionUserIsTeamMember()
    {
        $userFactory = self::$container->get(UserFactory::class);
        $users = $userFactory->createPublicPrivateAndTeamUserSet();

        $user = $users['member1'];
        $this->setUser($user);

        $response = $this->userAccountPlanSubscriptionController->subscribeAction(
            $user,
            $user->getEmail(),
            'personal'
        );

        $this->assertTrue($response->isClientError());

        $this->assertEquals(
            [
                UserAccountPlanServiceException::CODE_USER_IS_TEAM_MEMBER,
                'User is a team member'
            ],
            [
                $response->headers->get('x-error-code'),
                $response->headers->get('x-error-message'),
            ]
        );
    }

    public function testSubscribeActionSuccess()
    {
        $userAccountPlanService = self::$container->get(UserAccountPlanService::class);

        StripeApiFixtureFactory::set([
            StripeApiFixtureFactory::load('customer-nocard-hassub'),
        ]);

        $userFactory = self::$container->get(UserFactory::class);
        $user = $userFactory->create([
            UserFactory::KEY_PLAN_NAME => null,
        ]);

        $this->setUser($user);

        $planName = 'personal';

        $response = $this->userAccountPlanSubscriptionController->subscribeAction(
            $user,
            $user->getEmail(),
            'personal'
        );

        $this->assertTrue($response->isSuccessful());

        $userAccountPlan = $userAccountPlanService->getForUser($user);

        $this->assertInstanceOf(UserAccountPlan::class, $userAccountPlan);
        $this->assertEquals($planName, $userAccountPlan->getPlan()->getName());
    }
}
