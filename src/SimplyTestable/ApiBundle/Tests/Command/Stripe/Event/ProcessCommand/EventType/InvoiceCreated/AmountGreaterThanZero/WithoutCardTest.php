<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Stripe\Event\ProcessCommand\EventType\InvoiceCreated\AmountGreaterThanZero;

use \SimplyTestable\ApiBundle\Tests\Command\Stripe\Event\ProcessCommand\EventType\InvoiceCreated\InvoiceCreatedTest;

class WithoutCardTest extends InvoiceCreatedTest {
    
    public function testNotificationBodyEvent() {        
        $this->assertNotificationBodyField('event', 'invoice.created');
    }
    
    public function testNotificationBodyUser() {
        $this->assertNotificationBodyField('user', 'user@example.com');
    }
    
    public function testNotificationBodyLines() {        
        $this->assertNotificationBodyField('lines', array(
            array(
                'proration' => 0,
                'plan_name' => 'Agency',
                'period_start' => 1379776581,
                'period_end' => 1382368580                
            )
        ));
    }
    
    public function testNotificationBodyNextPaymentAttempt() {        
        $this->assertNotificationBodyField('next_payment_attempt', '1377442521');
    }    
    
    public function testNotificationBodyInvoiceId() {        
        $this->assertNotificationBodyField('invoice_id', 'in_2c6Kz0tw4CBlOL');
    } 
    
    public function testNotificationBodyTotal() {        
        $this->assertNotificationBodyField('total', '2000');
    } 
    
    public function testNotificationBodyAmountDue() {        
        $this->assertNotificationBodyField('amount_due', '2000');
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
