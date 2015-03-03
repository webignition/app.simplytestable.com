<?php

namespace SimplyTestable\ApiBundle\Exception\Services\Job\Configuration;

class Exception extends \Exception {

    const CODE_USER_NOT_SET = 1;
    const CODE_LABEL_NOT_UNIQUE = 2;
    const CONFIGURATION_ALREADY_EXISTS = 3;


    /**
     *
     * @return boolean
     */
    public function isUserNotSetException() {
        return $this->getCode() === self::CODE_USER_NOT_SET;
    }

    
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