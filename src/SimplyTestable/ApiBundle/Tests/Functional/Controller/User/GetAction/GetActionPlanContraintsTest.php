<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\User\GetAction;

use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;
use SimplyTestable\ApiBundle\Tests\Functional\Controller\BaseControllerJsonTestCase;

class GetActionPlanContraintsTest extends BaseControllerJsonTestCase
{
    const DEFAULT_TRIAL_PERIOD = 30;

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

    public function testForUseWithBasicPlan()
    {
        $user = $this->userFactory->create();
        $this->getUserService()->setUser($user);

        $responseObject = json_decode($this->getUserController('getAction')->getAction()->getContent());

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
        $this->getUserService()->setUser($user);

        $this->getUserAccountPlanService()->subscribe($user, $this->getAccountPlanService()->find('personal'));

        $responseObject = json_decode($this->getUserController('getAction')->getAction()->getContent());

        $this->assertInstanceOf('\stdClass', $responseObject->plan_constraints);
        $this->assertTrue(isset($responseObject->plan_constraints->credits));
        $this->assertEquals(5000, $responseObject->plan_constraints->credits->limit);
        $this->assertEquals(0, $responseObject->plan_constraints->credits->used);

        $this->assertTrue(isset($responseObject->plan_constraints->urls_per_job));
        $this->assertEquals(50, $responseObject->plan_constraints->urls_per_job);
    }
}
