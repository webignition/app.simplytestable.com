<?php

namespace SimplyTestable\ApiBundle\Model\Stripe\Invoice;

use SimplyTestable\ApiBundle\Model\Stripe\Object;
use SimplyTestable\ApiBundle\Model\Stripe\Plan;

class LineItem extends Object {
    
    public function __construct(\stdClass $data) {
        parent::__construct($data);
        
        if ($this->hasDataProperty('plan')) {
            $this->setDataProperty('plan', new Plan($this->getDataProperty('plan')));            
        }
    }     
    
    
    /**
     * 
     * @return boolean
     */
    public function getIsProrated() {
        return $this->getDataProperty('proration');
    }
    
    
    /**
     * 
     * @return \SimplyTestable\ApiBundle\Model\Stripe\Plan
     */
    public function getPlan() {
        return $this->getDataProperty('plan');
    }
    
    /**
     * 
     * @return int
     */
    public function getPeriodStart() {
        return $this->getDataProperty('period')->start;        
    }    
    
    
    /**
     * 
     * @return int
     */
    public function getPeriodEnd() {
        return $this->getDataProperty('period')->end;        
    }
    
    
    /**
     * 
     * @return int
     */
    public function getAmount() {
        return $this->getDataProperty('amount');
    }
    
}