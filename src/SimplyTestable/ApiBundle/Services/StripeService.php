<?php
namespace SimplyTestable\ApiBundle\Services;

use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Entity\UserAccountPlan;
use Stripe;
use Stripe_Customer;
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
     * @return \webignition\Model\Stripe\Customer
     */
    public function createCustomer(User $user, $coupon = null) {
        $adapter = new StripeCustomerAdapter();

        $customerProperties = [
            'email' => $user->getEmail()
        ];

        if (!is_null($coupon)) {
            $customerProperties['coupon'] = $coupon;
        }

        return $adapter->getStripeCustomer(Stripe_Customer::create($customerProperties));
    }
    
    
    /**
     * 
     * @param \SimplyTestable\ApiBundle\Entity\UserAccountPlan $userAccountPlan
     * @return \webignition\Model\Stripe\Customer
     */
    public function getCustomer(UserAccountPlan $userAccountPlan) {        
        if ($userAccountPlan->hasStripeCustomer()) {            
            $adapter = new StripeCustomerAdapter();
            return $adapter->getStripeCustomer(Stripe_Customer::retrieve($userAccountPlan->getStripeCustomer()));
        }        
    }
    
    
    /**
     * 
     * @param \SimplyTestable\ApiBundle\Entity\UserAccountPlan $userAccountPlan
     * @param array $updatedProperties
     */
    public function updateCustomer(UserAccountPlan $userAccountPlan, $updatedProperties) {
        $customer = Stripe_Customer::retrieve($userAccountPlan->getStripeCustomer());
        
        foreach ($updatedProperties as $key => $value) {
            $customer->{$key} = $value;
        }

        $customer->save();        
    }


    /**
     * @param UserAccountPlan $userAccountPlan
     * @return UserAccountPlan
     */
    public function subscribe(UserAccountPlan $userAccountPlan) {
        $stripeCustomerObject = Stripe_Customer::retrieve($userAccountPlan->getStripeCustomer());
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
     * @param \SimplyTestable\ApiBundle\Entity\UserAccountPlan $userAccountPlan
     */
    public function unsubscribe(UserAccountPlan $userAccountPlan) {        
        $stripeCustomerObject = Stripe_Customer::retrieve($userAccountPlan->getStripeCustomer());
        
        if (isset($stripeCustomerObject->subscription) && !is_null($stripeCustomerObject->subscription)) {
            $stripeCustomerObject->cancelSubscription();
        }      
    }
    
}