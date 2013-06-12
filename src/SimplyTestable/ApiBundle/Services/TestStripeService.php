<?php
namespace SimplyTestable\ApiBundle\Services;

use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Entity\UserAccountPlan;
use Stripe;
use Stripe_Customer;

class TestStripeService extends StripeService {
    
    
    /**
     * 
     * @param \SimplyTestable\ApiBundle\Entity\User $user
     * @return string
     */
    public function createCustomer(User $user) {
        return md5($user->getEmail());
    }
    
    

    /**
     * 
     * @param \SimplyTestable\ApiBundle\Entity\UserAccountPlan $userAccountPlan
     */
    public function subscribe(UserAccountPlan $userAccountPlan) {               
        return $userAccountPlan;
    }
    
    
    /**
     * 
     * @param \SimplyTestable\ApiBundle\Entity\UserAccountPlan $userAccountPlan
     */
    public function unsubscribe(UserAccountPlan $userAccountPlan) {    
    }
    
    
    /**
     * 
     * @param \SimplyTestable\ApiBundle\Entity\UserAccountPlan $accountPlan
     * @return Stripe_Plan
     */
    public function getPlan(UserAccountPlan $userAccountPlan) {
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