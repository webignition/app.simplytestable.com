<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\UserCreation\CreateAction\Success;

use SimplyTestable\ApiBundle\Entity\User;

class HasPlanTest extends SuccessTest {

    const DEFAULT_EMAIL = 'user@example.com';
    const DEFAULT_PASSWORD = 'password';
    const PLAN_NAME = 'personal';

    /**
     * @var \Symfony\Component\HttpFoundation\Response
     */
    private $response;


    /**
     * @var User
     */
    private $user;


    protected function setUp() {
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

    public function testUserHasNoPlan() {
        $this->assertNull($this->getUserAccountPlanService()->getForUser($this->user));
    }


    public function testHasPostUserActivationProperties() {
        $this->assertTrue($this->getUserPostActivationPropertiesService()->hasForUser($this->user));
    }


    public function testPostUserActivationPlan() {
        $this->assertEquals(self::PLAN_NAME, $this->getUserPostActivationPropertiesService()->getForUser($this->user)->getAccountPlan()->getName());
    }

    public function testPostUserActivationCoupon() {
        $this->assertNull($this->getUserPostActivationPropertiesService()->getForUser($this->user)->getCoupon());
    }


    protected function getRequestPostData() {
        return [
            'email' => self::DEFAULT_EMAIL,
            'password' => self::DEFAULT_PASSWORD,
            'plan' => self::PLAN_NAME
        ];
    }


}

