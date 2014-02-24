<?php

namespace SimplyTestable\ApiBundle\Model\Stripe;

abstract class Object {
    
    
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
     * @param string $name
     * @return mixed
     */
    protected function getDataProperty($name) {
        if (!$this->hasDataProperty($name)) {
            return null;
        }
        
        return $this->data->{$name};
    }  
    
    
    /**
     * 
     * @param string $name
     * @param mixed $value
     */
    protected function setDataProperty($name, $value) {
        $this->data->{$name} = $value;
    }
    
    
    /**
     * 
     * @param string $name
     * @return boolean
     */
    protected function hasDataProperty($name) {
        return isset($this->data->{$name});
    }
    
    
    /**
     * 
     * @return \stdClass
     */
    protected function getData() {
        return $this->data;
    }
    
}