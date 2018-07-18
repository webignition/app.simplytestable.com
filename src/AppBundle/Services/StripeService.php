<?php

namespace AppBundle\Services;

use AppBundle\Entity\User;
use AppBundle\Entity\UserAccountPlan;
use Stripe\Stripe;
use Stripe\Customer as StripeCustomer;
use AppBundle\Adapter\Stripe\Customer\StripeCustomerAdapter;
use webignition\Model\Stripe\Customer as StripeCustomerModel;

class StripeService
{
    /**
     * @var StripeCustomerAdapter
     */
    private $stripeCustomerAdapter;

    /**
     * @param string $apiKey
     */
    public function __construct($apiKey)
    {
        Stripe::setApiKey($apiKey);
        $this->stripeCustomerAdapter = new StripeCustomerAdapter();
    }

    /**
     * @param User $user
     * @param string|null $coupon
     *
     * @return StripeCustomerModel
     */
    public function createCustomer(User $user, $coupon = null)
    {
        $customerProperties = [
            'email' => $user->getEmail()
        ];

        if (!is_null($coupon)) {
            $customerProperties['coupon'] = $coupon;
        }

        $customer = StripeCustomer::create($customerProperties);

        return $this->stripeCustomerAdapter->getStripeCustomer($customer);
    }

    /**
     * @param UserAccountPlan $userAccountPlan
     *
     * @return StripeCustomerModel
     */
    public function getCustomer(UserAccountPlan $userAccountPlan)
    {
        $stripeCustomerId = $userAccountPlan->getStripeCustomer();
        if (empty($stripeCustomerId)) {
            return null;
        }

        $customer = StripeCustomer::retrieve($stripeCustomerId);

        return $this->stripeCustomerAdapter->getStripeCustomer($customer);
    }

    /**
     * @param UserAccountPlan $userAccountPlan
     * @param array $updatedProperties
     *
     * @return StripeCustomerModel
     */
    public function updateCustomer(UserAccountPlan $userAccountPlan, $updatedProperties)
    {
        $customer = StripeCustomer::retrieve($userAccountPlan->getStripeCustomer());

        foreach ($updatedProperties as $key => $value) {
            $customer->{$key} = $value;
        }

        $customer = $customer->save();

        return $this->stripeCustomerAdapter->getStripeCustomer($customer);
    }

    /**
     * @param UserAccountPlan $userAccountPlan
     */
    public function subscribe(UserAccountPlan $userAccountPlan)
    {
        $customer = StripeCustomer::retrieve($userAccountPlan->getStripeCustomer());

        $trialEndTimestamp = ($userAccountPlan->getStartTrialPeriod() <= 0)
            ? 'now'
            : time() + ($userAccountPlan->getStartTrialPeriod() * 86400);

        $customer->updateSubscription([
            'plan' => $userAccountPlan->getPlan()->getStripeId(),
            'trial_end' => $trialEndTimestamp
        ]);
    }

    /**
     * @param UserAccountPlan $userAccountPlan
     */
    public function unsubscribe(UserAccountPlan $userAccountPlan)
    {
        $customer = StripeCustomer::retrieve($userAccountPlan->getStripeCustomer());

        if (isset($customer->subscription) && !is_null($customer->subscription)) {
            $customer->cancelSubscription();
        }
    }
}
