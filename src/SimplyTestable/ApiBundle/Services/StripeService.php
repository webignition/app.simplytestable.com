<?php
namespace SimplyTestable\ApiBundle\Services;

use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Entity\UserAccountPlan;
use Stripe;
use Stripe_Customer;
use Stripe_Plan;
use SimplyTestable\ApiBundle\Model\Stripe\Customer as StripeCustomer;

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
        return new StripeCustomer(json_decode(Stripe_Customer::create(array(
            'email' => $user->getEmail()
        ))->__toJSON()));
    }
    
    
    /**
     * 
     * @param \SimplyTestable\ApiBundle\Entity\UserAccountPlan $userAccountPlan
     * @return array
     */
    public function getCustomer(UserAccountPlan $userAccountPlan) {
        var_dump("cp03");
        exit();
        
        if ($userAccountPlan->hasStripeCustomer()) {
            /* @var $customer \Stripe_Customer */            
            $customer = Stripe_Customer::retrieve($userAccountPlan->getStripeCustomer());
            return new StripeCustomer(json_decode($customer->__toJSON()));
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
        $stripeCustomerObject->cancelSubscription();        
    }
    
}