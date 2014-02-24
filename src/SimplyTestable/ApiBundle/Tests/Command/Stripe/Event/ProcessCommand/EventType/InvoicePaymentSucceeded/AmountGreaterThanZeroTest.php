<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Stripe\Event\ProcessCommand\EventType\InvoicePaymentSucceeded;

class AmountGreaterThanZeroTest extends InvoicePaymentSucceededTest {       
    
    public function testNotificationBodyEvent() {        
        $this->assertNotificationBodyField('event', 'invoice.payment_succeeded');
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
                'period_end' => 1382368580,
                'amount' => 2000
            )
        ));
    }   
    
    public function testNotificationBodyInvoiceId() {        
        $this->assertNotificationBodyField('invoice_id', 'in_2c6Kz0tw4CBlOL');
    } 
    
    public function testNotificationBodyTotal() {        
        $this->assertNotificationBodyField('total', '2000');
    } 
    
    public function testNotificationBodyAmountDue() {        
        $this->assertNotificationBodyField('amount_due', '0');
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
    
    protected function getAmountDue() {
        return 2000;
    }    
}
