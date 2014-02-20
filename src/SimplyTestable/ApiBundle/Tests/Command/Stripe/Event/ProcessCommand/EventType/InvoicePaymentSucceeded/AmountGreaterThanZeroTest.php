<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Stripe\Event\ProcessCommand\EventType\InvoicePaymentSucceeded;

class AmountGreaterThanZeroTest extends InvoicePaymentSucceededTest {   
    
    public function testWebClientEventBody() {        
        $this->assertEquals(
                'event=invoice.payment_succeeded&plan_name=Agency&plan_amount=1900&invoice_total=2000&period_start=1379776581&period_end=1382368580',
                (string)$this->getHttpClientService()->getHistoryPlugin()->getLastRequest()->getPostFields()
        );
    } 
    
    public function testWebClientSubscriberResponseStatusCode() {        
        $this->assertEquals(
                200,
                $this->getHttpClientService()->getHistoryPlugin()->getLastResponse()->getStatusCode()
        );
    }
    
    protected function getTotal() {
        return 2000;
    }
}
