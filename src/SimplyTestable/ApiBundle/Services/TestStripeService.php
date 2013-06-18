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
     * @var array
     */
    private $responseData = array();
    
    
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
        
        return md5(microtime(true));
    }
    
    
    /**
     * 
     * @param array $responseData
     */
    public function addResponseData($method, $responseData = array()) {        
        if (!isset($this->responseData[$method])) {
            $this->responseData[$method] = array();
        }       
        
        $this->responseData[$method][] = $responseData;
    }
    
    
    /**
     * 
     * @param \SimplyTestable\ApiBundle\Entity\UserAccountPlan $userAccountPlan
     * @return array
     */
    public function getCustomer(UserAccountPlan $userAccountPlan) {
        $responseData = $this->getResponseData(__FUNCTION__);
        
        if ($userAccountPlan->hasStripeCustomer()) {            
            return $this->populateCustomerTemplate($this->getCustomerTemplate($userAccountPlan), $responseData);           
        }        
    }
    
    
    /**
     * 
     * @param \SimplyTestable\ApiBundle\Entity\UserAccountPlan $userAccountPlan
     * @param array $updatedProperties
     */
    public function updateCustomer(UserAccountPlan $userAccountPlan, $updatedProperties) {
        return null;        
    }    
    
    
    private function populateCustomerTemplate($template, $values) {
        $customer = array();
        
        foreach ($template as $key => $value) {            
            if (is_array($value)) {                
                $customer[$key] = $this->populateCustomerTemplate($value, isset($values[$key]) ? $values[$key] : array());
            } else {                
                $customer[$key] = isset($values[$key]) ? $values[$key] : $value;
            }
        }
        
        return $customer;
    }
    
    
    private function getCustomerTemplate(UserAccountPlan $userAccountPlan) {
        return array(
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
    }
    
    
    /**
     * 
     * @param string $method
     * @return array
     */
    private function getResponseData($method) {
        if (!isset($this->responseData[$method])) {
            return array();
        }
        
        if (count($this->responseData[$method]) === 0) {
            return array();
        }
        
        $responseData = $this->responseData[$method][0];
        
        if (count($this->responseData[$method])) {
            $this->responseData[$method] = array_slice($this->responseData[$method], 1);
        } else {
            $this->responseData[$method] = array();
        }
        
        return $responseData;
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