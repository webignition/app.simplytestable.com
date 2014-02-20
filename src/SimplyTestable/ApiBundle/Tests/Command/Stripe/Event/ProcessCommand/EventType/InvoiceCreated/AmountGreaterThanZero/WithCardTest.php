<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Stripe\Event\ProcessCommand\EventType\InvoiceCreated\AmountGreaterThanZero;

use \SimplyTestable\ApiBundle\Tests\Command\Stripe\Event\ProcessCommand\EventType\InvoiceCreated\InvoiceCreatedTest;

class WithCardTest extends InvoiceCreatedTest {
    
    public function testNoWebClientRequestIsMade() {
        $this->assertEquals(0, $this->getHttpClientService()->getHistoryPlugin()->count());
    }     
    
    protected function getStripeServiceResponseMethod() {
        return 'getCustomer';
    }
    
    protected function getStripeServiceResponseData() {
        return array(
            'active_card' => '123'
        );
    }
    
    protected function getTotal() {
        return 2000;
    }

}
