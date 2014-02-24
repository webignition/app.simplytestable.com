<?php

namespace SimplyTestable\ApiBundle\Model\Stripe;

class Customer extends Object {    
    
    /**
     * 
     * @return string
     */
    public function getId() {
        return $this->getDataProperty('id');
    }
    
    
    /**
     * 
     * @return boolean
     */
    public function hasCard() {
        return !is_null($this->getDataProperty('active_card'));        
    }
    
}