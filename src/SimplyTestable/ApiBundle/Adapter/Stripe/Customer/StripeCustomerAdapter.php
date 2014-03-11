<?php

namespace SimplyTestable\ApiBundle\Adapter\Stripe\Customer;

/**
 * Translates Stripe-provided \Stripe_Customer into webignition\Model\Stripe\Customer
 */
class StripeCustomerAdapter {
    
    public function getStripeCustomer(\Stripe_Customer $input) {
        $stripeCustomerStdObject = json_decode(json_encode($input->__toArray(true)));
        $stripeCustomerStdObject->id = $input->id;
        
        return new \webignition\Model\Stripe\Customer(json_encode($stripeCustomerStdObject));
    }
    
}