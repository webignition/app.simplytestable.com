<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Command\Stripe\Event\ProcessCommand\ByEventType\InvoicePaymentSucceeded;

class AmountZeroTest extends InvoicePaymentSucceededTest {

    protected function getExpectedNotificationBodyFields() {
        return array();
    }

    public function testNoWebClientRequestIsMade() {
        $this->assertEquals(0, $this->getHttpClientService()->getHistoryPlugin()->count());
    }

    protected function getTotal() {
        return 0;
    }
}
