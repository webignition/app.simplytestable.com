<?php

namespace SimplyTestable\ApiBundle\Exception\Services\Job\Configuration;

class Exception extends \Exception {

    const CODE_USER_NOT_SET = 1;
    const CODE_LABEL_NOT_UNIQUE = 2;
    const CODE_CONFIGURATION_ALREADY_EXISTS = 3;
    const CODE_NO_SUCH_CONFIGURATION = 4;
    const CODE_TASK_CONFIGURATION_COLLECTION_IS_EMPTY = 5;


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
        return $this->getCode() == self::CODE_CONFIGURATION_ALREADY_EXISTS;
    }


    /**
     * @return bool
     */
    public function isNoSuchConfigurationException() {
        return $this->code == self::CODE_NO_SUCH_CONFIGURATION;
    }


    /**
     * @return bool
     */
    public function isTaskConfigurationCollectionIsEmptyException() {
        return $this->code == self::CODE_TASK_CONFIGURATION_COLLECTION_IS_EMPTY;
    }
}