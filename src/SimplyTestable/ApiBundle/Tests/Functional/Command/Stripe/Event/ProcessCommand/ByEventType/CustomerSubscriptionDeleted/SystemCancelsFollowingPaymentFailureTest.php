<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Command\Stripe\Event\ProcessCommand\ByEventType\CustomerSubscriptionDeleted;

class SystemCancelsFollowingPaymentFailureTest extends CustomerSubscriptionDeletedTest {

    public function testUserIsDowngradedToBasicPlan() {
        $this->assertEquals(
            $this->getAccountPlanService()->find('basic'),
            $this->getUserAccountPlanService()->getForUser($this->getUserService()->getUser())->getPlan()
        );
    }

    protected function getExpectedNotificationBodyFields() {
        return array_merge(parent::getExpectedNotificationBodyFields(), array(
            'plan_name' => 'Agency',
            'actioned_by' => 'system'
        ));
    }

    protected function getStripeEventFixturePaths() {
        return array(
            $this->getFixturesDataPath() . '/StripeEvents/invoice.payment_failed.json',
            $this->getFixturesDataPath() . '/StripeEvents/customer.subscription.deleted.json'
        );
    }
}