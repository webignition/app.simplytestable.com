<?php
namespace SimplyTestable\ApiBundle\Services;

use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Entity\UserAccountPlan;
use Stripe\Stripe;
use Stripe\Customer as StripeCustomer;
use SimplyTestable\ApiBundle\Adapter\Stripe\Customer\StripeCustomerAdapter;

class StripeService {

    /**
     *
     * @param string $apiKey
     */
    public function __construct($apiKey) {
        Stripe::setApiKey($apiKey);
    }

    /**
     *
     * @return string
     */
    public function getApiKey() {
        return Stripe::getApiKey();
    }


    /**
     * @param User $user
     * @param string|null $coupon
     *
     * @return StripeCustomer
     */
    public function createCustomer(User $user, $coupon = null)
    {
        $customerProperties = [
            'email' => $user->getEmail()
        ];

        if (!is_null($coupon)) {
            $customerProperties['coupon'] = $coupon;
        }

        return StripeCustomer::create($customerProperties);
    }

    /**
     * @param UserAccountPlan $userAccountPlan
     *
     * @return StripeCustomer
     */
    public function getCustomer(UserAccountPlan $userAccountPlan)
    {
        $stripeCustomerId = $userAccountPlan->getStripeCustomer();
        if (empty($stripeCustomerId)) {
            return null;
        }

        return StripeCustomer::retrieve($stripeCustomerId);
    }

    /**
     * @param UserAccountPlan $userAccountPlan
     * @param array $updatedProperties
     *
     * @return StripeCustomer
     */
    public function updateCustomer(UserAccountPlan $userAccountPlan, $updatedProperties)
    {
        $customer = StripeCustomer::retrieve($userAccountPlan->getStripeCustomer());

        foreach ($updatedProperties as $key => $value) {
            $customer->{$key} = $value;
        }

        $customer->save();

        return $customer;
    }


    /**
     * @param UserAccountPlan $userAccountPlan
     * @return UserAccountPlan
     */
    public function subscribe(UserAccountPlan $userAccountPlan) {
        $stripeCustomerObject = StripeCustomer::retrieve($userAccountPlan->getStripeCustomer());
        $stripeCustomerObject->updateSubscription(array(
            'plan' => $userAccountPlan->getPlan()->getStripeId(),
            'trial_end' => $this->getTrialEndTimestamp($userAccountPlan)
        ));

        return $userAccountPlan;
    }


    private function getTrialEndTimestamp(UserAccountPlan $userAccountPlan) {
        if ($userAccountPlan->getStartTrialPeriod() <= 0) {
            return 'now';
        }

        return time() + ($userAccountPlan->getStartTrialPeriod() * 86400);
    }


    /**
     *
     * @param UserAccountPlan $userAccountPlan
     */
    public function unsubscribe(UserAccountPlan $userAccountPlan) {
        $stripeCustomerObject = StripeCustomer::retrieve($userAccountPlan->getStripeCustomer());

        if (isset($stripeCustomerObject->subscription) && !is_null($stripeCustomerObject->subscription)) {
            $stripeCustomerObject->cancelSubscription();
        }
    }

}