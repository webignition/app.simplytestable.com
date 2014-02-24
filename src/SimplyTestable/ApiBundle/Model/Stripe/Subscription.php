<?php

namespace SimplyTestable\ApiBundle\Model\Stripe;

class Subscription extends Object {    
    
    public function __construct(\stdClass $data) {
        parent::__construct($data);
        
        if ($this->hasDataProperty('plan')) {
            $this->setDataProperty('plan', new Plan($this->getDataProperty('plan')));            
        }
    }
    
    
    /**
     * 
     * @return string
     */
    public function getStatus() {
        return $this->getDataProperty('status');
    }
    
    
    /**
     * 
     * @return Plan
     */
    public function getPlan() {
        return $this->getDataProperty('plan');
    }
    
    /**
     * 
     * @return boolean
     */
    public function isActive() {
        return $this->getStatus() == 'active';
    }    
    
    
    /**
     * 
     * @return boolean
     */
    public function isTrialing() {
        return $this->getStatus() == 'trialing';
    }
    
    
    /**
     * 
     * @return int|null
     */
    public function getTrialStart() {
        return $this->getDataProperty('trial_start');
    }
    
    
    /**
     * 
     * @return int|null
     */
    public function getTrialEnd() {
        return $this->getDataProperty('trial_end');
    }
    
    
    /**
     * 
     * @return int
     */
    public function getCurrentPeriodStart() {
        return $this->getDataProperty('current_period_start');
    }
    
    
    /**
     * 
     * @return int
     */
    public function getCurrentPeriodEnd() {
        return $this->getDataProperty('current_period_end');
    }    
    
}