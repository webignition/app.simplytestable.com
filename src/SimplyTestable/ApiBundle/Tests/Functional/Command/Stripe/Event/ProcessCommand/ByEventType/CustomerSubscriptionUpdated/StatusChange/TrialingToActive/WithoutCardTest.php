<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Command\Stripe\Event\ProcessCommand\ByEventType\CustomerSubscriptionUpdated\StatusChange\TrialingToActive;

class WithoutCardTest extends TrialingToActiveTest {

    public function testUserIsDowngradedToBasicPlan() {
        $userAccountPlan = $this->getUserAccountPlanService()->getForUser($this->getUserService()->getUser());
        $this->assertEquals($this->getAccountPlanService()->find('basic'), $userAccountPlan->getPlan());
    }


    protected function getHasCard() {
        return false;
    }

}
