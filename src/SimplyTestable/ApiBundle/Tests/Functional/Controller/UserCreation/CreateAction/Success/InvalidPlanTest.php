<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\UserCreation\CreateAction\Success;

use SimplyTestable\ApiBundle\Entity\User;

class InvalidPlanTest extends SuccessTest {

    const DEFAULT_EMAIL = 'user@example.com';
    const DEFAULT_PASSWORD = 'password';
    const REQUESTED_PLAN = 'foo';
    const EXPECTED_PLAN = 'basic';

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


    public function testUserHasBasicPlan() {
        $this->assertEquals(self::EXPECTED_PLAN, $this->getUserAccountPlanService()->getForUser($this->user)->getPlan()->getName());
    }


    protected function getRequestPostData() {
        return [
            'email' => self::DEFAULT_EMAIL,
            'password' => self::DEFAULT_PASSWORD,
            'plan' => self::REQUESTED_PLAN
        ];
    }


}

