<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\UserCreation\CreateAction\Success;

use SimplyTestable\ApiBundle\Entity\User;

class NoPlanNoCouponTest extends SuccessTest {

    const DEFAULT_EMAIL = 'user@example.com';
    const DEFAULT_PASSWORD = 'password';

    /**
     * @var \Symfony\Component\HttpFoundation\Response
     */
    private $response;


    /**
     * @var User
     */
    private $user;


    public function setUp() {
        parent::setUp();

        $this->assertFalse($this->getUserService()->exists(self::DEFAULT_EMAIL));

        $methodName = $this->getActionNameFromRouter();
        $this->response = $this->getCurrentController($this->getRequestPostData())->$methodName();
        $this->user = $this->getUserService()->findUserByEmail(self::DEFAULT_EMAIL);
    }


    public function testResponseStatusCode() {
        $this->assertEquals(200, $this->response->getStatusCode());
    }


    public function testUserIsCreated() {
        $this->assertTrue($this->getUserService()->exists(self::DEFAULT_EMAIL));
    }


    public function testDoesNotHavePostUserActivationProperties() {
        $this->assertFalse($this->getUserPostActivationPropertiesService()->hasForUser($this->user));
    }


    public function testUserHasBasicPlan() {
        $this->assertEquals('basic', $this->getUserAccountPlanService()->getForUser($this->user)->getPlan()->getName());
    }


    public function testDefaultTrialPeriod() {
        $this->assertEquals($this->container->getParameter('default_trial_period'), $this->getUserAccountPlanService()->getForUser($this->user)->getStartTrialPeriod());
    }


    protected function getRequestPostData() {
        return [
            'email' => self::DEFAULT_EMAIL,
            'password' => self::DEFAULT_PASSWORD
        ];
    }


}

