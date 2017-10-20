<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\User\GetAction;

use SimplyTestable\ApiBundle\Controller\UserController;
use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;
use SimplyTestable\ApiBundle\Tests\Functional\BaseSimplyTestableTestCase;

class GetActionTest extends BaseSimplyTestableTestCase
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

    public function testGetForUserWithBasicPlan()
    {
        $user = $this->userFactory->create();
        $this->setUser($user);

        $responseObject = json_decode($this->userController->getAction()->getContent());

        $this->assertEquals($user->getEmail(), $responseObject->email);
    }

    public function testGetForUserWithPremiumPlan()
    {
        $this->markTestSkipped('Re-implement in 1299');

        $user = $this->userFactory->create();
        $this->setUser($user);

        $this->getUserAccountPlanService()->subscribe($user, $this->getAccountPlanService()->find('personal'));

        $responseObject = json_decode($this->userController->getAction()->getContent());
        $this->assertEquals($user->getEmail(), $responseObject->email);
    }
}
