<?php

namespace SimplyTestable\ApiBundle\Adapter\Stripe\Customer;

/**
 * Translates Stripe-provided \Stripe_Customer into webignition\Model\Stripe\Customer
 */
class StripeCustomerAdapter {
    
    public function getStripeCustomer(\Stripe_Customer $customer) {
        return new \webignition\Model\Stripe\Customer($this->stripeCustomerToJson($customer));
    }


    private function stripeCustomerToJson(\Stripe_Customer $customer) {
        $stripeCustomerStdObject = json_decode(json_encode($customer->__toArray(true)));

        $stripeCustomerStdObject->id = $customer->id;

        if (isset($stripeCustomerStdObject->discount) && isset($stripeCustomerStdObject->discount->coupon)) {
            $stripeCustomerStdObject->discount->coupon->id = $customer->discount->coupon->id;
        }

        return json_encode($stripeCustomerStdObject);
    }
    
}