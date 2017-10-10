<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\User\GetAction;

use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;
use SimplyTestable\ApiBundle\Tests\Functional\Controller\BaseControllerJsonTestCase;

class GetActionTest extends BaseControllerJsonTestCase
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

    public function testGetForUserWithBasicPlan()
    {
        $user = $this->userFactory->create();
        $this->setUser($user);

        $responseObject = json_decode($this->getUserController('getAction')->getAction()->getContent());

        $this->assertEquals($user->getEmail(), $responseObject->email);
    }

    public function testGetForUserWithPremiumPlan()
    {
        $user = $this->userFactory->create();
        $this->setUser($user);

        $this->getUserAccountPlanService()->subscribe($user, $this->getAccountPlanService()->find('personal'));

        $responseObject = json_decode($this->getUserController('getAction')->getAction()->getContent());
        $this->assertEquals($user->getEmail(), $responseObject->email);
    }
}
