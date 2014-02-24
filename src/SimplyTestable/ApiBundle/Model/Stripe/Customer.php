<?php

namespace SimplyTestable\ApiBundle\Model\Stripe;

class Customer {
    
    
    /**
     *
     * @var \stdClass
     */
    private $data;
    
    
    public function __construct(\stdClass $data) {
        $this->data = $data;
    }
    
    
    /**
     * 
     * @return string
     */
    public function getId() {        
        return $this->data->id;
    }
    
    
    /**
     * 
     * @return boolean
     */
    public function hasCard() {
        return isset($this->data->active_card) && !is_null($this->data->active_card);
    }
    
}