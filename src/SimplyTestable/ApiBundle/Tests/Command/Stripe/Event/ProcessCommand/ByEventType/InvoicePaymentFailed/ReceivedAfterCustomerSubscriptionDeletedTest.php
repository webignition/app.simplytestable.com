<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Stripe\Event\ProcessCommand\ByEventType\InvoicePaymentFailed;

use SimplyTestable\ApiBundle\Tests\Command\Stripe\Event\ProcessCommand\ByEventType\ByEventTypeTest;

class ReceivedAfterCustomerSubscriptionDeletedTest extends ByEventTypeTest {
    
    protected function getHttpFixtureSet() {
        return array(
            "HTTP/1.1 200 OK",
            "HTTP/1.1 200 OK"
        );
    }    
    
    public function testUserIsDowngradedToBasicPlan() {
        $userAccountPlan = $this->getUserAccountPlanService()->getForUser($this->getUserService()->getUser());
        $this->assertEquals($this->getAccountPlanService()->find('basic'), $userAccountPlan->getPlan());
    }    
    
    protected function getExpectedNotificationBodyFields() {
        return array(
            array_merge(parent::getExpectedNotificationBodyFields(), array(
            'lines' => array(
                array(
                    'proration' => 0,
                    'plan_name' => 'Agency',
                    'period_start' => 1382368580,
                    'period_end' => 1385046980,
                    'amount' => 1900
                )
            ),
            'invoice_id' => 'in_2nL671LyaO5mbg',
            'total' => '1900',
            'amount_due' => '1900',
            'currency' => 'gbp'
            )),
            array(
                'event' => 'customer.subscription.deleted',
                'plan_name' => 'Agency',
                'actioned_by' => 'system'                
            )
        );
        
        return array_merge(parent::getExpectedNotificationBodyFields(), array(
            'lines' => array(
                array(
                    'proration' => 0,
                    'plan_name' => 'Agency',
                    'period_start' => 1382368580,
                    'period_end' => 1385046980,
                    'amount' => 1900
                )
            ),
            'invoice_id' => 'in_2nL671LyaO5mbg',
            'total' => '1900',
            'amount_due' => '1900',
        ));
    }   
    
    protected function getExpectedNotificationBodyEventName() {
        return 'invoice.payment_failed';
    }
    
    protected function getStripeEventFixturePaths() {        
        return array(
            $this->getFixturesDataPath() . '/../StripeEvents/customer.subscription.deleted.json',
            $this->getFixturesDataPath() . '/../StripeEvents/invoice.payment_failed.json'
        );
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
