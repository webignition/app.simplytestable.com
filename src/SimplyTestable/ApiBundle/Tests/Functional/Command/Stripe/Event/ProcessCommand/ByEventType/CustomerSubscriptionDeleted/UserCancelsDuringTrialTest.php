<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Command\Stripe\Event\ProcessCommand\ByEventType\CustomerSubscriptionDeleted;

class UserCancelsDuringTrialTest extends CustomerSubscriptionDeletedTest {

    protected function preCall() {
        $this->getUserAccountPlanService()->subscribe($this->getUserService()->getUser(), $this->getAccountPlanService()->find('basic'));
    }

    protected function getExpectedNotificationBodyFields() {
        return array_merge(parent::getExpectedNotificationBodyFields(), array(
            'plan_name' => 'Personal',
            'actioned_by' => 'user',
            'is_during_trial' => '1',
            'trial_days_remaining' => '15'
        ));
    }

    protected function getStripeEventFixturePaths() {
        return array(
            $this->getFixturesDataPath() . '/StripeEvents/customer.subscription.deleted.json'
        );
    }

    protected function getStripeServiceResponseMethod() {
        return 'getCustomer';
    }

    protected function getStripeServiceResponseData() {
        return array(
            'subscription' => array(
                'trial_end' => time() + (15 * 86400) // 15 days from now
            )
        );
    }
}
