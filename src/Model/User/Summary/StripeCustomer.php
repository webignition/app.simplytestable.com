<?php

namespace App\Model\User\Summary;

use webignition\Model\Stripe\Customer as StripeCustomerModel;

class StripeCustomer implements \JsonSerializable
{
    /**
     * @var StripeCustomerModel
     */
    private $stripeCustomerModel;

    /**
     * @param StripeCustomerModel|null $stripeCustomerModel
     */
    public function __construct(StripeCustomerModel $stripeCustomerModel = null)
    {
        $this->stripeCustomerModel = $stripeCustomerModel;
    }

    /**
     * @return bool
     */
    public function isEmpty()
    {
        return empty($this->stripeCustomerModel);
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return (empty($this->stripeCustomerModel))
            ? []
            : $this->stripeCustomerModel->__toArray();
    }
}
