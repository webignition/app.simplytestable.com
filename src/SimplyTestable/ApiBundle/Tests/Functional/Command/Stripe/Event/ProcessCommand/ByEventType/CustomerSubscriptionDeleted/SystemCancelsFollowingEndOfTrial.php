<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Command\Stripe\Event\ProcessCommand\ByEventType\CustomerSubscriptionDeleted;

class SystemCancelsFollowingEndOfTrial extends CustomerSubscriptionDeletedTest {

    protected function getExpectedNotificationBodyFields() {
        return array();
    }

    public function testNoWebClientRequestIsMade() {
        $this->assertEquals(0, $this->getHttpClientService()->getHistoryPlugin()->count());
    }

    protected function getStripeEventFixturePaths() {
        return array(
            $this->getFixturesDataPath() . '/StripeEvents/customer.subscription.updated.json',
            $this->getFixturesDataPath() . '/StripeEvents/customer.subscription.deleted.json'
        );
    }
}
