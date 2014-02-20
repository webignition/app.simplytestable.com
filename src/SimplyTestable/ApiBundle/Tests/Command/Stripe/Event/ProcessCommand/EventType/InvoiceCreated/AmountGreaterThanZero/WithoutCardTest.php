<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Stripe\Event\ProcessCommand\EventType\InvoiceCreated\AmountGreaterThanZero;

use \SimplyTestable\ApiBundle\Tests\Command\Stripe\Event\ProcessCommand\EventType\InvoiceCreated\InvoiceCreatedTest;

class WithoutCardTest extends InvoiceCreatedTest {
    
    public function testWebClientEventBody() {        
        $this->assertEquals(
                'event=invoice.created&next_payment_attempt=1377442521',
                (string)$this->getHttpClientService()->getHistoryPlugin()->getLastRequest()->getPostFields()
        );
    } 
    
    public function testWebClientSubscriberResponseStatusCode() {        
        $this->assertEquals(
                200,
                $this->getHttpClientService()->getHistoryPlugin()->getLastResponse()->getStatusCode()
        );
    }

    protected function getHasCard() {
        return false;
    }
    
    protected function getTotal() {
        return 2000;
    }

}
