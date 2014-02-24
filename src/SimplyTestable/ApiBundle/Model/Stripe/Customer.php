<?php

namespace SimplyTestable\ApiBundle\Model\Stripe;

class Customer extends Object {
    
    public function __construct(\stdClass $data) {
        parent::__construct($data);
        if ($this->hasDataProperty('subscription')) {
            $this->setDataProperty('subscription', new Subscription(($this->getDataProperty('subscription'))));
        }
        
        if ($this->hasDataProperty('active_card')) {            
            $this->setDataProperty('active_card', new Card(($this->getDataProperty('active_card'))));
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
    
    
    /**
     * 
     * @return boolean
     */
    public function hasSubscription() {
        return !is_null($this->getSubscription());
    }
    
    
    /**
     * 
     * @return \SimplyTestable\ApiBundle\Model\Stripe\Card
     */
    public function getActiveCard() {
        return $this->getDataProperty('active_card');  
    }
    
    
    /**
     * 
     * @return array
     */
    public function __toArray() {
        $returnArray = (array)$this->getData();
        
        if ($this->getSubscription() instanceof Subscription) {
            $returnArray['subscription'] = $this->getSubscription()->__toArray();
        }
        
        if ($this->getActiveCard() instanceof Card) {
            $returnArray['active_card'] = $this->getActiveCard()->__toArray();
        }
        
        return $returnArray;
    }
    
}