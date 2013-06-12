<?php
namespace SimplyTestable\ApiBundle\Services;

use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Entity\UserAccountPlan;
use Stripe;
use Stripe_Customer;
use Stripe_AuthenticationError;  

class TestStripeService extends StripeService {
    
    
    /**
     *
     * @var boolean
     */
    private $hasInvalidApiKey = false;
    
    
    /**
     * 
     * @param boolean $hasInvalidApiKey
     */
    public function setHasInvalidApiKey($hasInvalidApiKey) {
        $this->hasInvalidApiKey = $hasInvalidApiKey;
    }
    
    
    /**
     * 
     * @param \SimplyTestable\ApiBundle\Entity\User $user
     * @return string
     */
    public function createCustomer(User $user) {
        if ($this->hasInvalidApiKey === true) {
            throw new Stripe_AuthenticationError();
        }
        
        return md5($user->getEmail());
    }
    
    

    /**
     * 
     * @param \SimplyTestable\ApiBundle\Entity\UserAccountPlan $userAccountPlan
     */
    public function subscribe(UserAccountPlan $userAccountPlan) {               
        if ($this->hasInvalidApiKey === true) {
            throw new Stripe_AuthenticationError();
        }        
        
        return $userAccountPlan;
    }
    
    
    /**
     * 
     * @param \SimplyTestable\ApiBundle\Entity\UserAccountPlan $userAccountPlan
     */
    public function unsubscribe(UserAccountPlan $userAccountPlan) {    
        if ($this->hasInvalidApiKey === true) {
            throw new Stripe_AuthenticationError();
        }        
    }
    
    
    /**
     * 
     * @param \SimplyTestable\ApiBundle\Entity\UserAccountPlan $accountPlan
     * @return Stripe_Plan
     */
    public function getPlan(UserAccountPlan $userAccountPlan) {
        if ($this->hasInvalidApiKey === true) {
            throw new Stripe_AuthenticationError();
        }        
        
        $plan = $userAccountPlan->getPlan();
        if ($plan->getIsPremium()) {
            switch ($userAccountPlan->getPlan()->getName()) {
                case 'personal':
                    return array(
                        'id' => 'personal-9',
                        'interval' => 'month',
                        'name' => 'Personal',
                        'amount' => 900,
                        'currency' => 'gbp',
                        'object' => 'plan',
                        'livemode' => false,
                        'interval_count' => 1,
                        'trial_period_days' => 30,
                    );                    
            }
        }
        
        return null;       
    }    
    
}