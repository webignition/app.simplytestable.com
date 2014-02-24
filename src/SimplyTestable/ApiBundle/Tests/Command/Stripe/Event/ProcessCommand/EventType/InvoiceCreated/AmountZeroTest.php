<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Stripe\Event\ProcessCommand\EventType\InvoiceCreated;

class AmountZeroTest extends InvoiceCreatedTest {   
    
    public function testNoWebClientRequestIsMade() {
        $this->assertEquals(0, $this->getHttpClientService()->getHistoryPlugin()->count());
    } 
    
    protected function getStripeEventFixturePath() {
        return $this->getFixturesDataPath() . '/../StripeEvents/invoice.created.json';
    } 
    
    protected function getTotal() {
        return 0;
    } 
    
    protected function getAmountDue() {
        return 0;
    }
}
