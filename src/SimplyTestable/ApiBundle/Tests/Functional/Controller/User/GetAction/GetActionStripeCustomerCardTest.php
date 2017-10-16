<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\User\GetAction;

use SimplyTestable\ApiBundle\Controller\UserController;
use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;
use SimplyTestable\ApiBundle\Tests\Functional\BaseSimplyTestableTestCase;

class GetActionStripeCustomerCardTest extends BaseSimplyTestableTestCase
{
    /**
     * @var UserFactory
     */
    private $userFactory;

    /**
     * @var UserController
     */
    private $userController;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->userFactory = new UserFactory($this->container);
        $this->userController = new UserController();
        $this->userController->setContainer($this->container);
    }

    public function testForUserWithBasicPlanAndNeverHadCard()
    {
        $user = $this->userFactory->create();
        $this->setUser($user);

        $responseObject = json_decode($this->userController->getAction()->getContent());
        $this->assertFalse(isset($responseObject->stripe_customer));
    }

    public function testForUserWithBasicPlanAndHasCard()
    {
        $card = $this->getRandomCard();

        $user = $this->userFactory->create();
        $this->setUser($user);

        $this->getUserAccountPlanService()->subscribe($user, $this->getAccountPlanService()->find('personal'));
        $this->getUserAccountPlanService()->subscribe($user, $this->getAccountPlanService()->find('basic'));

        // Mock the StripeService getCustomer response to include
        // card details
        $this->getStripeService()->addResponseData('getCustomer', array(
            'active_card' => $card
        ));

        $responseObject = json_decode($this->userController->getAction()->getContent());
        $this->assertTrue(isset($responseObject->stripe_customer));
        $this->assertTrue(isset($responseObject->stripe_customer->id));
        $this->assertTrue(isset($responseObject->stripe_customer->active_card));
        $this->assertNotNull($responseObject->stripe_customer->active_card);

        $this->assertNotNull($responseObject->stripe_customer->active_card->exp_month);
        $this->assertNotNull($responseObject->stripe_customer->active_card->exp_year);
        $this->assertNotNull($responseObject->stripe_customer->active_card->last4);
        $this->assertNotNull($responseObject->stripe_customer->active_card->type);
    }


    public function testForUserWithPremiumPlan()
    {
        $card = $this->getRandomCard();

        $user = $this->userFactory->create();
        $this->setUser($user);

        $this->getUserAccountPlanService()->subscribe($user, $this->getAccountPlanService()->find('personal'));

        // Mock the StripeService getCustomer response to include
        // card details
        $this->getStripeService()->addResponseData('getCustomer', array(
            'active_card' => $card
        ));

        $responseObject = json_decode($this->userController->getAction()->getContent());
        $this->assertTrue(isset($responseObject->stripe_customer));
        $this->assertTrue(isset($responseObject->stripe_customer->active_card));
        $this->assertNotNull($responseObject->stripe_customer->active_card);

        foreach ($card as $key => $value) {
            $this->assertEquals($value, $responseObject->stripe_customer->active_card->$key);
        }
    }

    private function getRandomCard()
    {
        return array(
            'exp_month' => $this->getRandomCardExpiryMonth(),
            'exp_year' => $this->getRandomCardExpiryYear(),
            'last4' => $this->getRandomCardLastFour(),
            'type' => $this->getRandomCardType(),
        );
    }

    private function getRandomCardExpiryMonth()
    {
        $month = (string)rand(1, 12);

        if (strlen($month) === 1) {
            $month = '0' . $month;
        }

        return $month;
    }

    private function getRandomCardExpiryYear()
    {
        $year = (string)rand(0, 99);

        if (strlen($year) === 1) {
            $year = str_pad($year, 2, '0', STR_PAD_LEFT);
        }

        if (rand(0, 1) === 0) {
            $year = '20' . $year;
        }

        return $year;
    }

    private function getRandomCardLastFour()
    {
        $lastFour = '';

        while (strlen($lastFour) < 4) {
            $lastFour .= rand(0, 9);
        }

        return $lastFour;
    }

    private function getRandomCardType()
    {
        $cards = array('Visa', 'American Express', 'MasterCard', 'Discover', 'JCB', 'Diners Club', 'Unknown');
        $cardKey = array_rand($cards);

        return $cards[$cardKey];
    }
}
