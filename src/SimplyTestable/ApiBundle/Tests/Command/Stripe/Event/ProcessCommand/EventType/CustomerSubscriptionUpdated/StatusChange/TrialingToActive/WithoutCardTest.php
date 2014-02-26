<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Stripe\Event\ProcessCommand\EventType\CustomerSubscriptionUpdated\StatusChange\TrialingToActive;

class WithoutCardTest extends TrialingToActiveTest {   

    public function testUserIsDowngradedToBasicPlan() {
        $userAccountPlan = $this->getUserAccountPlanService()->getForUser($this->getUserService()->getUser());
        $this->assertEquals($this->getAccountPlanService()->find('basic'), $userAccountPlan->getPlan());
    }
    

    protected function getHasCard() {
        return false;
    }    

}
