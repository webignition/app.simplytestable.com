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
     * @return array
     */
    public function getCustomer(UserAccountPlan $userAccountPlan) {
        if ($userAccountPlan->hasStripeCustomer()) {
            $customer = array(
                'object' => 'customer',
                'created' => 1371075807,
                'livemode' => false,
                'description' => NULL,
                'active_card' => NULL,
                'email' => $userAccountPlan->getUser()->getEmail(),
                'delinquent' => false,
                'subscription' => array(
                    'plan' => array(
                        'interval' => 'month',
                        'name' => 'Personal',
                        'amount' => 900,
                        'currency' => 'gbp',
                        'object' => 'plan',
                        'livemode' => false,
                        'interval_count' => 1,
                        'trial_period_days' => 30,
                    ),
                    'object' => 'subscription',
                    'start' => 1371075809,
                    'status' => 'trialing',
                    'customer' => $userAccountPlan->getStripeCustomer(),
                    'cancel_at_period_end' => false,
                    'current_period_start' => 1371075809,
                    'current_period_end' => 1373667809,
                    'ended_at' => NULL,
                    'trial_start' => 1371075809,
                    'trial_end' => 1373667809,
                    'canceled_at' => NULL,
                    'quantity' => 1,
                ),
                'discount' => NULL,
                'account_balance' => 0,
            );          
            
            return $customer;            
        }        
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
    
}