<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Stripe\Event\ProcessCommand\SingleEvent\InvoicePaymentSucceeded;

class AmountZeroTest extends InvoicePaymentSucceededTest {   
    
    public function testNoWebClientRequestIsMade() {
        $this->assertEquals(0, $this->getHttpClientService()->getHistoryPlugin()->count());
    }

    protected function getTotal() {
        return 0;
    }    
    
    protected function getAmountDue() {
        return 0;
    }
}
