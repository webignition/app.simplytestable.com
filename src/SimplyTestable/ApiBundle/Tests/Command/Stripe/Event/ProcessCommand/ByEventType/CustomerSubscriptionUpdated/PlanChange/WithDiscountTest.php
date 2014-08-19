<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Stripe\Event\ProcessCommand\ByEventType\CustomerSubscriptionUpdated\PlanChange;

abstract class WithDiscountTest extends PlanChangeTest {

    protected function getExpectedNotificationBodyFields() {
        $fields = array(
            'is_plan_change' => '1',
            'old_plan' => 'Personal',
            'new_plan' => 'Agency',
            'new_amount' => '1520',
            'subscription_status' => $this->getSubscriptionStatus()          
        );
        
        if ($this->getSubscriptionStatus() == 'trialing') {
            $fields['trial_end'] = '1405427088';
        }       
        
        return array_merge(parent::getExpectedNotificationBodyFields(), $fields);
    }

    protected function getHasDiscount() {
        return true;
    }
    
    protected function getStripeEventFixturePaths() {
        return [
            $this->getFixturesDataPath() . '/../../StripeEvents/WithDiscount/customer.created.json',
            $this->getFixturesDataPath() . '/../../StripeEvents/WithDiscount/customer.updated.json',
            $this->getFixturesDataPath() . '/../../StripeEvents/WithDiscount/customer.subscription.updated.planchange.json'
        ];
    }
    
}
