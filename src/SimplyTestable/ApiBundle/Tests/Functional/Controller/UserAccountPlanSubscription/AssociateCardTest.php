<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\UserAccountPlanSubsciption;

use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;
use SimplyTestable\ApiBundle\Tests\Functional\Controller\BaseControllerJsonTestCase;

class AssociateCardTest extends BaseControllerJsonTestCase
{
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

        $this->userFactory = new UserFactory($this->container);
    }

    public function testWithPublicUser()
    {
        $userService = $this->container->get('simplytestable.services.userservice');
        $this->setUser($userService->getPublicUser());

        $response = $this->getUserAccountPlanSubscriptionController('associateCardAction')->associateCardAction('', '');
        $this->assertEquals(400, $response->getStatusCode());
    }

    public function testWithWrongUser()
    {
        $user = $this->userFactory->create();
        $this->setUser($user);

        $response = $this->getUserAccountPlanSubscriptionController('associateCardAction')->associateCardAction('', '');
        $this->assertEquals(400, $response->getStatusCode());
    }

    public function testWithInvalidStripeCardToken()
    {
        $user = $this->userFactory->create();
        $this->setUser($user);

        $response = $this->getUserAccountPlanSubscriptionController('associateCardAction')->associateCardAction(
            $user->getEmail(),
            ''
        );
        $this->assertEquals(400, $response->getStatusCode());
    }

    public function testWithNoStripeCustomer()
    {
        $user = $this->userFactory->create();
        $this->setUser($user);

        $response = $this->getUserAccountPlanSubscriptionController('associateCardAction')->associateCardAction(
            $user->getEmail(),
            'tok_22SBwowh6VeVgR'
        );
        $this->assertEquals(400, $response->getStatusCode());
    }

    public function testWithValidStripeCustomerandValidStipeCardToken()
    {
        $user = $this->userFactory->create();
        $this->setUser($user);

        $this->getUserAccountPlanSubscriptionController('subscribeAction')->subscribeAction(
            $user->getEmail(),
            'personal'
        );

        $response = $this->getUserAccountPlanSubscriptionController('associateCardAction')->associateCardAction(
            $user->getEmail(),
            $this->generateStripeCardToken()
        );
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testWithTokenForCardFailingZipCheck()
    {
        $stripeErrorMessage = 'The zip code you supplied failed validation.';
        $stripeErrorParam = 'address_zip';
        $stripeErrorCode = 'incorrect_zip';

        $user = $this->userFactory->create();
        $this->setUser($user);

        $this->getUserAccountPlanSubscriptionController('subscribeAction')->subscribeAction(
            $user->getEmail(),
            'personal'
        );

        $this->getStripeService()->setIssueStripeCardError(true);
        $this->getStripeService()->setNextStripeCardErrorMessage($stripeErrorMessage);
        $this->getStripeService()->setNextStripeCardErrorParam($stripeErrorParam);
        $this->getStripeService()->setNextStripeCardErrorCode($stripeErrorCode);

        $response = $this->getUserAccountPlanSubscriptionController('associateCardAction')->associateCardAction(
            $user->getEmail(),
            $this->generateStripeCardToken()
        );
        $this->assertEquals(400, $response->getStatusCode());

        $this->assertEquals($stripeErrorMessage, $response->headers->get('X-Stripe-Error-Message'));
        $this->assertEquals($stripeErrorParam, $response->headers->get('X-Stripe-Error-Param'));
        $this->assertEquals($stripeErrorCode, $response->headers->get('X-Stripe-Error-Code'));
    }

    public function testWithTokenForCardFailingCvcCheck()
    {
        $stripeErrorMessage = 'Your card\'s security code is incorrect.';
        $stripeErrorParam = 'cvc';
        $stripeErrorCode = 'incorrect_cvc';

        $user = $this->userFactory->create();
        $this->setUser($user);

        $this->getUserAccountPlanSubscriptionController('subscribeAction')->subscribeAction(
            $user->getEmail(),
            'personal'
        );

        $this->getStripeService()->setIssueStripeCardError(true);
        $this->getStripeService()->setNextStripeCardErrorMessage($stripeErrorMessage);
        $this->getStripeService()->setNextStripeCardErrorParam($stripeErrorParam);
        $this->getStripeService()->setNextStripeCardErrorCode($stripeErrorCode);

        $response = $this->getUserAccountPlanSubscriptionController('associateCardAction')->associateCardAction(
            $user->getEmail(),
            $this->generateStripeCardToken()
        );
        $this->assertEquals(400, $response->getStatusCode());

        $this->assertEquals($stripeErrorMessage, $response->headers->get('X-Stripe-Error-Message'));
        $this->assertEquals($stripeErrorParam, $response->headers->get('X-Stripe-Error-Param'));
        $this->assertEquals($stripeErrorCode, $response->headers->get('X-Stripe-Error-Code'));
    }

    private function generateStripeCardToken()
    {
        return 'tok_' . $this->generateAlphaNumericToken(14);
    }

    private function generateAlphaNumericToken($length)
    {
        $token = '';

        while (strlen($token) < $length) {
            $token .= (rand(0, 1) === 0)
                ? $this->generateRandomNumericCharacter()
                : $this->generateRandomAlphaCharacter();
        }

        return $token;
    }

    private function generateRandomNumericCharacter()
    {
        return (string)rand(0, 9);
    }

    private function generateRandomAlphaCharacter()
    {
        $character = chr(rand(65, 90));

        return (rand(0, 1) === 1) ? $character : strtolower($character);
    }
}
