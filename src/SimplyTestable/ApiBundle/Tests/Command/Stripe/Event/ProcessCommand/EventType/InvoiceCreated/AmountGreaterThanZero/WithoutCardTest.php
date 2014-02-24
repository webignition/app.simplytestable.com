<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Stripe\Event\ProcessCommand\EventType\InvoiceCreated\AmountGreaterThanZero;

use \SimplyTestable\ApiBundle\Tests\Command\Stripe\Event\ProcessCommand\EventType\InvoiceCreated\InvoiceCreatedTest;

class WithoutCardTest extends InvoiceCreatedTest {
    
    public function testWebClientEventBody() {        
        $this->assertEquals(
                'event=invoice.created&user=user%40example.com&lines%5B0%5D%5Bproration%5D=&lines%5B0%5D%5Bplan_name%5D=Agency&next_payment_attempt=1377442521&invoice_id=in_2c6Kz0tw4CBlOL&total=2000&amount_due=2000',
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
    
    protected function getAmountDue() {
        return 2000;
    }

}
