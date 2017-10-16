<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\UserAccountPlanSubsciption\Subscribe\ErrorCases;

use SimplyTestable\ApiBundle\Controller\UserAccountPlanSubscriptionController;
use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;
use SimplyTestable\ApiBundle\Tests\Functional\BaseSimplyTestableTestCase;

class ErrorCasesTest extends BaseSimplyTestableTestCase
{
    /**
     * @var UserFactory
     */
    private $userFactory;

    /**
     * @var UserAccountPlanSubscriptionController
     */
    private $userAccountPlanSubscriptionController;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->userFactory = new UserFactory($this->container);
        $this->userAccountPlanSubscriptionController = new UserAccountPlanSubscriptionController();
        $this->userAccountPlanSubscriptionController->setContainer($this->container);
    }

    public function testWithPublicUser()
    {
        $userService = $this->container->get('simplytestable.services.userservice');
        $this->setUser($userService->getPublicUser());

        $response = $this->userAccountPlanSubscriptionController->subscribeAction('', '');
        $this->assertEquals(400, $response->getStatusCode());
    }

    public function testWithWrongUser()
    {
        $user = $this->userFactory->create();
        $this->setUser($user);

        $response = $this->userAccountPlanSubscriptionController->subscribeAction('', '');
        $this->assertEquals(400, $response->getStatusCode());
    }

    public function testWithCorrectUserAndInvalidPlan()
    {
        $user = $this->userFactory->create();
        $this->setUser($user);

        $response = $this->userAccountPlanSubscriptionController->subscribeAction(
            $user->getEmail(),
            'invalid-plan'
        );
        $this->assertEquals(400, $response->getStatusCode());
    }

    public function testWithInvalidStripeApiKey()
    {
        $newPlan = 'personal';

        $user = $this->userFactory->create();
        $this->setUser($user);

        $this->getStripeService()->setHasInvalidApiKey(true);

        $response = $this->userAccountPlanSubscriptionController->subscribeAction(
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
        $this->setUser($user);

        $this->getStripeService()->setIssueStripeCardError(true);
        $this->getStripeService()->setNextStripeCardErrorMessage($stripeErrorMessage);
        $this->getStripeService()->setNextStripeCardErrorParam($stripeErrorParam);
        $this->getStripeService()->setNextStripeCardErrorCode($stripeErrorCode);

        $response = $this->userAccountPlanSubscriptionController->subscribeAction(
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
