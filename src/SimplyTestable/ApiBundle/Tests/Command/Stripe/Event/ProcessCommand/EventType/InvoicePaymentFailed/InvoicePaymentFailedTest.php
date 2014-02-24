<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Stripe\Event\ProcessCommand\EventType\InvoicePaymentFailed;

use SimplyTestable\ApiBundle\Tests\Command\Stripe\Event\ProcessCommand\EventType\EventTypeTest;

abstract class InvoicePaymentFailedTest extends EventTypeTest {   
    
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
    
    public function testNotificationBodyHasCard() {
        $this->assertNotificationBodyField('has_card', (int)$this->getHasCard());
    }    
    
    public function testNotificationBodyNextPaymentAttempt() {        
        if (!is_null($this->getNextPaymentAttempt())) {
            $this->assertNotificationBodyField('next_payment_attempt', $this->getNextPaymentAttempt());
        }
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
    
    public function testNotificationBodyAttemptCount() {        
        $this->assertNotificationBodyField('attempt_count', $this->getAttemptCount());
    }         
    
    public function testNotificationBodyAttemptLimit() {        
        $this->assertNotificationBodyField('attempt_limit', 4);
    }     
    
    public function testWebClientSubscriberResponseStatusCode() {        
        $this->assertEquals(
                200,
                $this->getHttpClientService()->getHistoryPlugin()->getLastResponse()->getStatusCode()
        );
    }     
    
    abstract protected function getAttemptCount();
    abstract protected function getIsClosed();
    abstract protected function getHasCard();
    abstract protected function getNextPaymentAttempt();
    
    protected function getHttpFixtureItems() {
        return array(
            "HTTP/1.1 200 OK"
        );
    }
    
    protected function getStripeEventFixturePath() {
        return $this->getFixturesDataPath() . '/../../StripeEvents/invoice.payment_failed.json';
    }     
    
    protected function getFixtureReplacements() {
        $fixtureReplacements = parent::getFixtureReplacements();        
        $fixtureReplacements['0.1'] = ($this->getIsClosed() ? 'true' : 'false');
        $fixtureReplacements['0.2'] = $this->getAttemptCount();
        $fixtureReplacements['0.3'] = (is_null($this->getNextPaymentAttempt()) ? 'null' : $this->getNextPaymentAttempt());
        
        return $fixtureReplacements;
    }     
}
