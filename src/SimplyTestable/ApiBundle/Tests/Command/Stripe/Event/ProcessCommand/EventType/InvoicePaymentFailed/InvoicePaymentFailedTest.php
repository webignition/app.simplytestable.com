<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Stripe\Event\ProcessCommand\EventType\InvoicePaymentFailed;

use SimplyTestable\ApiBundle\Tests\Command\Stripe\Event\ProcessCommand\EventType\EventTypeTest;

abstract class InvoicePaymentFailedTest extends EventTypeTest {   
    
    public function testWebClientEventBody() {   
        $expectedWebClientBodyParts = array(
            'event=invoice.payment_failed',
            'user=user%40example.com',
            'has_card='.((int)$this->getHasCard()),
            'attempt_count='.$this->getAttemptCount(),
            'attempt_limit=4',
            'invoice_id=in_2nL671LyaO5mbg'
        );
        
        if (!is_null($this->getNextPaymentAttempt())) {
            $expectedWebClientBodyParts[] = 'next_payment_attempt=' . $this->getNextPaymentAttempt();
        }
        
        $this->assertEquals(
                implode('&', $expectedWebClientBodyParts),
                (string)$this->getHttpClientService()->getHistoryPlugin()->getLastRequest()->getPostFields()
        );        
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
