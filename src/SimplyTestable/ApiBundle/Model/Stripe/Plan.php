<?php

namespace SimplyTestable\ApiBundle\Model\Stripe;

class Plan extends Object {    
    
    /**
     * 
     * @return string
     */
    public function getName() {
        return $this->getDataProperty('name');
    }
    
    
    /**
     * 
     * @return int
     */
    public function getTrialPeriodDays() {
        return $this->getDataProperty('trial_period_days');
    }
    
    
    /**
     * 
     * @return int
     */
    public function getAmount() {
        return $this->getDataProperty('amount');
    }
    
    
    /**
     * 
     * @return array
     */
    public function __toArray() {
        return (array)$this->getData();
    }
    
}