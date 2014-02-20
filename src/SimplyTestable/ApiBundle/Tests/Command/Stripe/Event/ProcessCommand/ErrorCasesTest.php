<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Stripe\Event\ProcessCommand;

class ErrorCasesTest extends ProcessCommandTest {
    
    public function testEventWithNoUser() {
        $fixture = str_replace(
            '{{stripe_customer}}',
            'invalid_stripe_customer',
            $this->getFixture($this->getFixturesDataPath() . '/StripeEvents/invoice.payment_failed.attempt-1.json')
        );
        
        $response = $this->getStripeWebHookController('indexAction', array(
            'event' => $fixture
        ))->indexAction();
        
        $this->assertReturnCode(3, array(
            'stripeId' => json_decode($response->getContent())->stripe_id
        ));       
    }
}
