<?php

namespace Tests\ApiBundle\Functional\Controller\UserAccountPlanSubscription;

use SimplyTestable\ApiBundle\Services\ApplicationStateService;
use Tests\ApiBundle\Factory\StripeApiFixtureFactory;
use Tests\ApiBundle\Factory\UserFactory;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

class UserAccountPlanSubscriptionControllerAssociateCardActionTest extends
 AbstractUserAccountPlanSubscriptionControllerTest
{
    public function testPostRequest()
    {
        StripeApiFixtureFactory::set([
            StripeApiFixtureFactory::load('customer-nocard-nosub'),
            StripeApiFixtureFactory::load('customer-hascard-nosub'),
        ]);

        $userFactory = new UserFactory($this->container);

        $user = $userFactory->createAndActivateUser([
            UserFactory::KEY_PLAN_NAME => 'personal',
        ]);

        $router = $this->container->get('router');
        $requestUrl = $router->generate('user_card_associate', [
            'email_canonical' => $user->getEmail(),
            'stripe_card_token' => 'tok_Bb4A2szGLfgwJe',
        ]);

        $this->getCrawler([
            'url' => $requestUrl,
            'method' => 'POST',
            'user' => $user,
        ]);

        $response = $this->getClientResponse();

        $this->assertTrue($response->isSuccessful());
    }

    public function testAssociateCardActionInMaintenanceReadOnlyMode()
    {
        $applicationStateService = $this->container->get(ApplicationStateService::class);
        $applicationStateService->setState(ApplicationStateService::STATE_MAINTENANCE_READ_ONLY);

        try {
            $this->userAccountPlanSubscriptionController->associateCardAction('foo', 'bar');
            $this->fail('ServiceUnavailableHttpException not thrown');
        } catch (ServiceUnavailableHttpException $serviceUnavailableHttpException) {
            $applicationStateService->setState(ApplicationStateService::STATE_ACTIVE);
        }
    }

    /**
     * @dataProvider associateCardActionClientFailureDataProvider
     *
     * @param string $userName
     * @param string $emailCanonical
     * @param string $stripeCardToken
     */
    public function testAssociateCardActionClientFailure($userName, $emailCanonical, $stripeCardToken)
    {
        $userFactory = new UserFactory($this->container);
        $users = $userFactory->createPublicAndPrivateUserSet();

        $user = $users[$userName];
        $this->setUser($user);

        $response = $this->userAccountPlanSubscriptionController->associateCardAction(
            $emailCanonical,
            $stripeCardToken
        );

        $this->assertTrue($response->isClientError());
    }

    /**
     * @return array
     */
    public function associateCardActionClientFailureDataProvider()
    {
        return [
            'public user' => [
                'userName' => 'public',
                'emailCanonical' => 'foo@example.com',
                'stripeCardToken' => 'personal',
            ],
            'request email does not match user' => [
                'userName' => 'private',
                'emailCanonical' => 'foo@example.com',
                'stripeCardToken' => 'foo',
            ],
            'invalid stripe card token' => [
                'userName' => 'private',
                'emailCanonical' => 'private@example.com',
                'stripeCardToken' => 'foo',
            ],
            'user has no stripe customer' => [
                'userName' => 'private',
                'emailCanonical' => 'private@example.com',
                'stripeCardToken' => 'tok_Bb4A2szGLfgwJe',
            ],
        ];
    }

    public function testAssociateCardActionWithDecliningCard()
    {
        StripeApiFixtureFactory::set([
            StripeApiFixtureFactory::load('customer-hascard-nosub'),
            StripeApiFixtureFactory::load('zip-validation-failed'),
        ], [
            200,
            402
        ]);

        $userFactory = new UserFactory($this->container);

        $user = $userFactory->create([
            UserFactory::KEY_PLAN_NAME => 'personal',
        ]);
        $this->setUser($user);

        $response = $this->userAccountPlanSubscriptionController->associateCardAction(
            $user->getEmail(),
            'tok_Bb4A2szGLfgwJe'
        );

        $this->assertTrue($response->isClientError());

        $this->assertEquals([
            'code' => 'incorrect_zip',
            'message' => 'The zip code you supplied failed validation.',
            'param' => 'address_zip',
        ], [
            'code' => $response->headers->get('x-stripe-error-code'),
            'message' => $response->headers->get('x-stripe-error-message'),
            'param' => $response->headers->get('x-stripe-error-param'),
        ]);
    }

    public function testAssociateCardActionSuccess()
    {
        StripeApiFixtureFactory::set([
            StripeApiFixtureFactory::load('customer-nocard-nosub'),
            StripeApiFixtureFactory::load('customer-hascard-nosub'),
        ]);

        $userFactory = new UserFactory($this->container);

        $user = $userFactory->create([
            UserFactory::KEY_PLAN_NAME => 'personal',
        ]);
        $this->setUser($user);

        $response = $this->userAccountPlanSubscriptionController->associateCardAction(
            $user->getEmail(),
            'tok_Bb4A2szGLfgwJe'
        );

        $this->assertTrue($response->isSuccessful());
    }
}
