<?php

namespace Tests\ApiBundle\Functional\Controller\UserAccountPlanSubscription;

use SimplyTestable\ApiBundle\Entity\UserAccountPlan;
use SimplyTestable\ApiBundle\Services\ApplicationStateService;
use Tests\ApiBundle\Factory\StripeApiFixtureFactory;
use Tests\ApiBundle\Factory\UserFactory;
use SimplyTestable\ApiBundle\Exception\Services\UserAccountPlan\Exception as UserAccountPlanServiceException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

class UserAccountPlanSubscriptionControllerSubscribeActionTest extends AbstractUserAccountPlanSubscriptionControllerTest
{
    public function testPostRequest()
    {
        StripeApiFixtureFactory::set([
            StripeApiFixtureFactory::load('customer-nocard-hassub'),
        ]);

        $userFactory = new UserFactory($this->container);
        $user = $userFactory->createAndActivateUser([
            UserFactory::KEY_PLAN_NAME => null,
        ]);

        $planName = 'personal';

        $router = $this->container->get('router');
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

    public function testSubscribeActionInMaintenanceReadOnlyMode()
    {
        $applicationStateService = $this->container->get(ApplicationStateService::class);
        $applicationStateService->setState(ApplicationStateService::STATE_MAINTENANCE_READ_ONLY);

        try {
            $this->userAccountPlanSubscriptionController->subscribeAction('foo', 'bar');
            $this->fail('ServiceUnavailableHttpException not thrown');
        } catch (ServiceUnavailableHttpException $serviceUnavailableHttpException) {
            $applicationStateService->setState(ApplicationStateService::STATE_ACTIVE);
        }
    }

    /**
     * @dataProvider subscribeActionClientFailureDataProvider
     *
     * @param string $userName
     * @param string $emailCanonical
     * @param string $planName
     */
    public function testSubscribeActionClientFailure($userName, $emailCanonical, $planName)
    {
        $userFactory = new UserFactory($this->container);
        $users = $userFactory->createPublicAndPrivateUserSet();

        $user = $users[$userName];
        $this->setUser($user);

        $response = $this->userAccountPlanSubscriptionController->subscribeAction($emailCanonical, $planName);

        $this->assertTrue($response->isClientError());
    }

    /**
     * @return array
     */
    public function subscribeActionClientFailureDataProvider()
    {
        return [
            'public user' => [
                'userName' => 'public',
                'emailCanonical' => 'foo@example.com',
                'planName' => 'personal',
            ],
            'request email does not match user' => [
                'userName' => 'private',
                'emailCanonical' => 'foo@example.com',
                'planName' => 'personal',
            ],
            'invalid plan' => [
                'userName' => 'private',
                'emailCanonical' => 'private@example.com',
                'planName' => 'foo',
            ],
        ];
    }

    public function testSubscribeActionInvalidStripeApiKey()
    {
        StripeApiFixtureFactory::set([
            StripeApiFixtureFactory::load('invalid-api-key'),
        ], [
            401,
        ]);

        $userFactory = new UserFactory($this->container);

        $user = $userFactory->create([
            UserFactory::KEY_PLAN_NAME => null,
        ]);
        $this->setUser($user);

        $response = $this->userAccountPlanSubscriptionController->subscribeAction($user->getEmail(), 'personal');

        $this->assertTrue($response->isForbidden());
    }

    public function testSubscribeActionDecliningCard()
    {
        StripeApiFixtureFactory::set([
            StripeApiFixtureFactory::load('card-declined'),
        ], [
            402,
        ]);

        $userFactory = new UserFactory($this->container);

        $user = $userFactory->create([
            UserFactory::KEY_PLAN_NAME => null,
        ]);
        $this->setUser($user);

        $response = $this->userAccountPlanSubscriptionController->subscribeAction($user->getEmail(), 'personal');

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
        $userFactory = new UserFactory($this->container);
        $users = $userFactory->createPublicPrivateAndTeamUserSet();

        $user = $users['member1'];
        $this->setUser($user);

        $response = $this->userAccountPlanSubscriptionController->subscribeAction($user->getEmail(), 'personal');

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
        $userAccountPlanService = $this->container->get('simplytestable.services.useraccountplanservice');

        StripeApiFixtureFactory::set([
            StripeApiFixtureFactory::load('customer-nocard-hassub'),
        ]);

        $userFactory = new UserFactory($this->container);
        $user = $userFactory->create([
            UserFactory::KEY_PLAN_NAME => null,
        ]);

        $this->setUser($user);

        $planName = 'personal';

        $response = $this->userAccountPlanSubscriptionController->subscribeAction($user->getEmail(), $planName);

        $this->assertTrue($response->isSuccessful());

        $userAccountPlan = $userAccountPlanService->getForUser($user);

        $this->assertInstanceOf(UserAccountPlan::class, $userAccountPlan);
        $this->assertEquals($planName, $userAccountPlan->getPlan()->getName());
    }
}
