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
     * @return boolean
     */
    public function isCancelled() {
        return $this->getStatus() == 'canceled';
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
     * @return int|null
     */
    public function getCancelledAt() {
        return $this->getDataProperty('canceled_at');
    }    
    
    
    /**
     * 
     * @return boolean
     */
    public function wasCancelledDuringTrial() {
        if (!$this->isCancelled()) {
            return false;
        }
        
        return $this->getCancelledAt() <= $this->getTrialEnd();
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
    
    
    /**
     * 
     * @return array
     */
    public function __toArray() {
        $returnArray = (array)$this->getData();
        
        if ($this->getPlan() instanceof Plan) {
            $returnArray['plan'] = $this->getPlan()->__toArray();
        }
        
        return $returnArray;
    }
    
}