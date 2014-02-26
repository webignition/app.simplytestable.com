<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Stripe\Event\ProcessCommand\SingleEvent\InvoicePaymentSucceeded;

class AmountGreaterThanZeroTest extends InvoicePaymentSucceededTest {       
    
    protected function getExpectedNotificationBodyFields() {
        return array_merge(parent::getExpectedNotificationBodyFields(), array(
            'lines' => array(
                array(
                    'proration' => 0,
                    'plan_name' => 'Agency',
                    'period_start' => 1379776581,
                    'period_end' => 1382368580,
                    'amount' => 2000
                )
            ),
            'invoice_id' => 'in_2c6Kz0tw4CBlOL',
            'total' => '2000',
            'amount_due' => '0',
        ));
    }
    
    protected function getTotal() {
        return 2000;
    }   
}
