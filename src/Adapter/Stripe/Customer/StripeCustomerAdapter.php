<?php

namespace App\Adapter\Stripe\Customer;

use Stripe\Customer as StripeCustomer;
use webignition\Model\Stripe\Customer as StripeCustomerModel;

/**
 * Translates Stripe-provided Stripe\Customer into webignition\Model\Stripe\Customer
 */
class StripeCustomerAdapter
{
    public function getStripeCustomer(StripeCustomer $customer)
    {
        $stripeCustomerData = json_decode($customer->__toJSON());

        $stripeCustomerData->id = $customer->id;

        if (isset($stripeCustomerData->discount) && isset($stripeCustomerData->discount->coupon)) {
            $stripeCustomerData->discount->coupon->id = $customer->discount->coupon->id;
        }

        $stripeCustomerJson = json_encode($stripeCustomerData, JSON_PRETTY_PRINT);

        return new StripeCustomerModel($stripeCustomerJson);
    }
}
