<?php

namespace SimplyTestable\ApiBundle\Exception\Services\Job\Configuration;

class Exception extends \Exception {
    
    const CODE_LABEL_NOT_UNIQUE = 1;
    const CONFIGURATION_ALREADY_EXISTS = 2;
    
    /**
     * 
     * @return boolean
     */
    public function isLabelNotUniqueException() {
        return $this->getCode() === self::CODE_LABEL_NOT_UNIQUE;
    }


    /**
     * @return bool
     */
    public function isConfigurationAlreadyExistsException() {
        return $this->getCode() == self::CONFIGURATION_ALREADY_EXISTS;
    }
}