<?php

namespace SimplyTestable\ApiBundle\Tests\Services\UserAccountPlanService\Subscribe;

use SimplyTestable\ApiBundle\Tests\Services\UserAccountPlanService\ServiceTest;

use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Entity\UserAccountPlan;
use SimplyTestable\ApiBundle\Entity\Account\Plan\Plan as AccountPlan;

abstract class SubscribeTest extends ServiceTest {

    abstract protected function getNewPlanName();

    /**
     * @var User
     */
    private $user;

    /**
     * @var AccountPlan
     */
    private $accountPlan;

    /**
     * @var UserAccountPlan
     */
    private $userAccountPlan;


    public function setUp() {
        parent::setUp();

        $this->user = $this->createAndActivateUser('user@example.com', 'password');
        $this->accountPlan = $this->getAccountPlanService()->find($this->getNewPlanName());

        $this->userAccountPlan = $this->getUserAccountPlanService()->subscribe($this->user, $this->accountPlan);
    }

    public function testCreateReturnsUserAccountPlan() {
        $this->assertInstanceof('SimplyTestable\ApiBundle\Entity\UserAccountPlan', $this->userAccountPlan);
    }


    public function testCreatedUserAccountPlanIsForUser() {
        $this->assertEquals($this->user->getId(), $this->userAccountPlan->getUser()->getId());
    }


    public function testCreatedUserAccountPlanIsForAccountPlan() {
        $this->assertEquals($this->accountPlan->getId(), $this->userAccountPlan->getPlan()->getId());
    }

}
