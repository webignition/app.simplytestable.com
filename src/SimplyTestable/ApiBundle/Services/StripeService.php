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
        return $this->apiKey;
    }
    
    /**
     * 
     * @param \SimplyTestable\ApiBundle\Entity\User $user
     * @return \webignition\Model\Stripe\Customer
     */
    public function createCustomer(User $user) {
        $adapter = new StripeCustomerAdapter();
        return $adapter->getStripeCustomer(Stripe_Customer::create(array(
            'email' => $user->getEmail()
        )));
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
     * 
     * @param \SimplyTestable\ApiBundle\Entity\UserAccountPlan $userAccountPlan
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