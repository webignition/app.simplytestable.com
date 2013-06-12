<?php
namespace SimplyTestable\ApiBundle\Services;

use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Entity\UserAccountPlan;
use Stripe;
use Stripe_Customer;
use Stripe_Plan;

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
     * @return string
     */
    public function createCustomer(User $user) {
        return Stripe_Customer::create(array(
            'email' => $user->getEmail()
        ))->id; 
    }
    
    

    /**
     * 
     * @param \SimplyTestable\ApiBundle\Entity\UserAccountPlan $userAccountPlan
     */
    public function subscribe(UserAccountPlan $userAccountPlan) {
        $stripeCustomerObject = Stripe_Customer::retrieve($userAccountPlan->getStripeCustomer());
        $stripeCustomerObject->updateSubscription(array(
            'plan' => $userAccountPlan->getPlan()->getStripeId()
        ));
        
        return $userAccountPlan;
    }
    
    
    /**
     * 
     * @param \SimplyTestable\ApiBundle\Entity\UserAccountPlan $userAccountPlan
     */
    public function unsubscribe(UserAccountPlan $userAccountPlan) {
        $stripeCustomerObject = Stripe_Customer::retrieve($userAccountPlan->getStripeCustomer());
        $stripeCustomerObject->cancelSubscription();        
    }
    
    
    /**
     * 
     * @param \SimplyTestable\ApiBundle\Entity\UserAccountPlan $accountPlan
     * @return Stripe_Plan
     */
    public function getPlan(UserAccountPlan $userAccountPlan) {
        $plan = $userAccountPlan->getPlan();
        if ($plan->getIsPremium()) {
            return Stripe_Plan::retrieve($userAccountPlan->getPlan()->getStripeId())->__toArray();
        }
        
        return null;       
    }
    
}