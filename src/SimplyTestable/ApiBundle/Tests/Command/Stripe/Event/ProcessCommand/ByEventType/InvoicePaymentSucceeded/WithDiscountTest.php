<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Stripe\Event\ProcessCommand\ByEventType\InvoicePaymentSucceeded;

class WithDiscountTest extends InvoicePaymentSucceededTest {
    
    protected function getExpectedNotificationBodyFields() {
        return array_merge(parent::getExpectedNotificationBodyFields(), array(
            'lines' => array(
                array(
                    'proration' => 0,
                    'plan_name' => 'Personal',
                    'period_start' => 1408031226,
                    'period_end' => 1410709626,
                    'amount' => 900
                )
            ),
            'invoice_id' => 'in_4abfD1nt0ael6N',
            'subtotal' => '900',
            'total' => '720',
            'amount_due' => '720',
            'discount' => [
                'coupon' => 'TMS',
                'percent_off' => '20',
                'discount' => 180
            ],
            'has_discount' => 1
        ));
    }

    protected function getStripeEventFixturePaths() {
        return array(
            $this->getFixturesDataPath() . '/../StripeEvents/invoice.withsubscription.withdiscount.payment_succeeded.json'
        );
    }

    protected function getTotal() {
        return 2000;
    }   
}
