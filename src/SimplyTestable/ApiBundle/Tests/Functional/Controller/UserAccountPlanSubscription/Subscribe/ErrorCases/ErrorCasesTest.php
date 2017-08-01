<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\UserAccountPlanSubsciption\Subscribe\ErrorCases;

use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;
use SimplyTestable\ApiBundle\Tests\Functional\Controller\BaseControllerJsonTestCase;

class ErrorCasesTest extends BaseControllerJsonTestCase
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
        $this->getUserService()->setUser($this->getUserService()->getPublicUser());

        $response = $this->getUserAccountPlanSubscriptionController('subscribeAction')->subscribeAction('', '');
        $this->assertEquals(400, $response->getStatusCode());
    }

    public function testWithWrongUser()
    {
        $user = $this->userFactory->create();
        $this->getUserService()->setUser($user);

        $response = $this->getUserAccountPlanSubscriptionController('subscribeAction')->subscribeAction('', '');
        $this->assertEquals(400, $response->getStatusCode());
    }

    public function testWithCorrectUserAndInvalidPlan()
    {
        $user = $this->userFactory->create();
        $this->getUserService()->setUser($user);

        $response = $this->getUserAccountPlanSubscriptionController('subscribeAction')->subscribeAction(
            $user->getEmail(),
            'invalid-plan'
        );
        $this->assertEquals(400, $response->getStatusCode());
    }

    public function testWithInvalidStripeApiKey()
    {
        $newPlan = 'personal';

        $user = $this->userFactory->create();
        $this->getUserService()->setUser($user);

        $this->getStripeService()->setHasInvalidApiKey(true);

        $response = $this->getUserAccountPlanSubscriptionController('subscribeAction')->subscribeAction(
            $user->getEmail(),
            $newPlan
        );
        $this->assertEquals(403, $response->getStatusCode());
    }

    public function testSubscribeWithUserThatHasDecliningCard()
    {
        $currentPlan = 'basic';
        $newPlan = 'personal';

        $stripeErrorMessage = 'Your card was declined.';
        $stripeErrorParam = null;
        $stripeErrorCode = 'card_declined';

        $user = $this->userFactory->create();
        $this->getUserService()->setUser($user);

        $this->getStripeService()->setIssueStripeCardError(true);
        $this->getStripeService()->setNextStripeCardErrorMessage($stripeErrorMessage);
        $this->getStripeService()->setNextStripeCardErrorParam($stripeErrorParam);
        $this->getStripeService()->setNextStripeCardErrorCode($stripeErrorCode);

        $response = $this->getUserAccountPlanSubscriptionController('subscribeAction')->subscribeAction(
            $user->getEmail(),
            $newPlan
        );
        $this->assertEquals(400, $response->getStatusCode());

        $this->assertEquals($stripeErrorMessage, $response->headers->get('X-Stripe-Error-Message'));
        $this->assertEquals($stripeErrorCode, $response->headers->get('X-Stripe-Error-Code'));

        $userAccountPlan = $this->getUserAccountPlanService()->getForUser($user);
        $this->assertEquals($currentPlan, $userAccountPlan->getPlan()->getName());
    }
}
