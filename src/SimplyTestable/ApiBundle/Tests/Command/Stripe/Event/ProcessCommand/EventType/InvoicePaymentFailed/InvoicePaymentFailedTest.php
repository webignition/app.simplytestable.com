<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Stripe\Event\ProcessCommand\EventType\InvoicePaymentFailed;

use SimplyTestable\ApiBundle\Tests\Command\Stripe\Event\ProcessCommand\EventType\EventTypeTest;

class InvoicePaymentFailedTest extends EventTypeTest {   
    
    public function testNotificationBodyEvent() {        
        $this->assertNotificationBodyField('event', 'invoice.payment_failed');
    }
    
    public function testNotificationBodyUser() {
        $this->assertNotificationBodyField('user', 'user@example.com');
    }
    
    public function testNotificationBodyLines() {        
        $this->assertNotificationBodyField('lines', array(
            array(
                'proration' => 0,
                'plan_name' => 'Agency',
                'period_start' => 1382368580,
                'period_end' => 1385046980,
                'amount' => 1900
            )
        ));
    }
    
    public function testNotificationBodyInvoiceId() {        
        $this->assertNotificationBodyField('invoice_id', 'in_2nL671LyaO5mbg');
    } 
    
    public function testNotificationBodyTotal() {        
        $this->assertNotificationBodyField('total', '1900');
    } 
    
    public function testNotificationBodyAmountDue() {        
        $this->assertNotificationBodyField('amount_due', '1900');
    }   
    
    public function testWebClientSubscriberResponseStatusCode() {        
        $this->assertEquals(
                200,
                $this->getHttpClientService()->getHistoryPlugin()->getLastResponse()->getStatusCode()
        );
    }
    
    protected function getHttpFixtureItems() {
        return array(
            "HTTP/1.1 200 OK"
        );
    }
    
    protected function getStripeEventFixturePath() {
        return $this->getFixturesDataPath() . '/../StripeEvents/invoice.payment_failed.json';
    }
    
    
    protected function getStripeServiceResponseMethod() {
        return 'getCustomer';
    }
    
    protected function getStripeServiceResponseData() {
        return array(
            'active_card' => array(
                'exp_month' => '01',
                'exp_year' => '99',
                'last4' => '1234',
                'type' => 'Foo'
            )
        );
    }  
}
