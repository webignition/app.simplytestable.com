<?php

namespace SimplyTestable\ApiBundle\Adapter\Stripe\Customer;

use SimplyTestable\ApiBundle\Model\Stripe\Customer as StripeCustomer;

/**
 * Translates Stripe-provided \Stripe_Customer into SimplyTestable\ApiBundle\Model\Stripe\Customer
 */
class StripeCustomerAdapter {
    
    public function getStripeCustomer(\Stripe_Customer $input) {
        $stripeCustomerStdObject = json_decode(json_encode($input->__toArray(true)));
        $stripeCustomerStdObject->id = $input->id;
        
        return new StripeCustomer($stripeCustomerStdObject);      
    }
    
}