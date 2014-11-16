<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Stripe\Event\ProcessCommand\ByEventType\InvoicePaymentSucceeded;

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
            'subtotal' => '2000',
            'total' => '2000',
            'amount_due' => '0',
            'has_discount' => 0,
            'currency' => 'gbp'
        ));
    }
    
    protected function getTotal() {
        return 2000;
    }   
}
