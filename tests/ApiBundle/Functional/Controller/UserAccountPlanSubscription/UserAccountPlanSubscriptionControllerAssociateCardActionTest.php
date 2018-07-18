<?php

namespace Tests\ApiBundle\Functional\Controller\UserAccountPlanSubscription;

use Tests\ApiBundle\Factory\StripeApiFixtureFactory;
use Tests\ApiBundle\Factory\UserFactory;

/**
 * @group Controller/UserAccountPlanSubscriptionController
 */
class UserAccountPlanSubscriptionControllerAssociateCardActionTest extends
 AbstractUserAccountPlanSubscriptionControllerTest
{
    public function testPostRequest()
    {
        StripeApiFixtureFactory::set([
            StripeApiFixtureFactory::load('customer-nocard-nosub'),
            StripeApiFixtureFactory::load('customer-hascard-nosub'),
        ]);

        $userFactory = new UserFactory(self::$container);

        $user = $userFactory->createAndActivateUser([
            UserFactory::KEY_PLAN_NAME => 'personal',
        ]);

        $router = self::$container->get('router');
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

    public function testAssociateCardActionWithDecliningCard()
    {
        StripeApiFixtureFactory::set([
            StripeApiFixtureFactory::load('customer-hascard-nosub'),
            StripeApiFixtureFactory::load('zip-validation-failed'),
        ], [
            200,
            402
        ]);

        $userFactory = new UserFactory(self::$container);

        $user = $userFactory->create([
            UserFactory::KEY_PLAN_NAME => 'personal',
        ]);
        $this->setUser($user);

        $response = $this->userAccountPlanSubscriptionController->associateCardAction(
            $user,
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

        $userFactory = new UserFactory(self::$container);

        $user = $userFactory->create([
            UserFactory::KEY_PLAN_NAME => 'personal',
        ]);
        $this->setUser($user);

        $response = $this->userAccountPlanSubscriptionController->associateCardAction(
            $user,
            $user->getEmail(),
            'tok_Bb4A2szGLfgwJe'
        );

        $this->assertTrue($response->isSuccessful());
    }
}
