<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Stripe\Event\ProcessCommand\SingleEvent\InvoicePaymentSucceeded;

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
