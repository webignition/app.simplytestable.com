<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\User\GetAction;

use SimplyTestable\ApiBundle\Controller\UserController;
use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;
use SimplyTestable\ApiBundle\Tests\Functional\BaseSimplyTestableTestCase;

class GetActionPlanContraintsTest extends BaseSimplyTestableTestCase
{
    const DEFAULT_TRIAL_PERIOD = 30;

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

    public function testForUseWithBasicPlan()
    {
        $user = $this->userFactory->create();
        $this->setUser($user);

        $responseObject = json_decode($this->userController->getAction()->getContent());

        $this->assertInstanceOf('\stdClass', $responseObject->plan_constraints);
        $this->assertTrue(isset($responseObject->plan_constraints->credits));
        $this->assertEquals(500, $responseObject->plan_constraints->credits->limit);
        $this->assertEquals(0, $responseObject->plan_constraints->credits->used);

        $this->assertTrue(isset($responseObject->plan_constraints->urls_per_job));
        $this->assertEquals(10, $responseObject->plan_constraints->urls_per_job);
    }

    public function testForUserWithPremiumPlan()
    {
        $user = $this->userFactory->create();
        $this->setUser($user);

        $this->getUserAccountPlanService()->subscribe($user, $this->getAccountPlanService()->find('personal'));

        $responseObject = json_decode($this->userController->getAction()->getContent());

        $this->assertInstanceOf('\stdClass', $responseObject->plan_constraints);
        $this->assertTrue(isset($responseObject->plan_constraints->credits));
        $this->assertEquals(5000, $responseObject->plan_constraints->credits->limit);
        $this->assertEquals(0, $responseObject->plan_constraints->credits->used);

        $this->assertTrue(isset($responseObject->plan_constraints->urls_per_job));
        $this->assertEquals(50, $responseObject->plan_constraints->urls_per_job);
    }
}
