<?php

namespace SimplyTestable\ApiBundle\Model\Stripe;

class Customer extends Object {
    
    public function __construct(\stdClass $data) {
        parent::__construct($data);
        if ($this->hasDataProperty('subscription')) {
            $this->setDataProperty('subscription', new Subscription(($this->getDataProperty('subscription'))));
        }
    }
    
    
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
    
    
    /**
     * 
     * @return \SimplyTestable\ApiBundle\Model\Stripe\Subscription
     */
    public function getSubscription() {
        return $this->getDataProperty('subscription');
    }
    
}