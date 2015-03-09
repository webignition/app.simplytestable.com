<?php

namespace SimplyTestable\ApiBundle\Exception\Services\Job\Configuration;

class Exception extends \Exception {

    const CODE_USER_NOT_SET = 1;
    const CODE_LABEL_NOT_UNIQUE = 2;
    const CODE_CONFIGURATION_ALREADY_EXISTS = 3;
    const CODE_NO_SUCH_CONFIGURATION = 4;
    const CODE_TASK_CONFIGURATION_COLLECTION_IS_EMPTY = 5;
    const CODE_UNABLE_TO_PERFORM_AS_USER_IS_IN_A_TEAM = 6;
    const CODE_LABEL_CANNOT_BE_EMPTY = 7;
    const CODE_WEBSITE_CANNOT_BE_EMPTY = 8;
    const CODE_TYPE_CANNOT_BE_EMPTY = 9;


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
        return $this->getCode() == self::CODE_NO_SUCH_CONFIGURATION;
    }


    /**
     * @return bool
     */
    public function isTaskConfigurationCollectionIsEmptyException() {
        return $this->getCode() == self::CODE_TASK_CONFIGURATION_COLLECTION_IS_EMPTY;
    }


    /**
     * @return bool
     */
    public function isUnableToPerformAsUserIsInATeamException() {
        return $this->getCode() == self::CODE_UNABLE_TO_PERFORM_AS_USER_IS_IN_A_TEAM;
    }


    /**
     * @return bool
     */
    public function isLabelCannotBeEmptyException() {
        return $this->getCode() == self::CODE_LABEL_CANNOT_BE_EMPTY;
    }


    /**
     * @return bool
     */
    public function isWebsiteCannotBeEmptyException() {
        return $this->getCode() == self::CODE_WEBSITE_CANNOT_BE_EMPTY;
    }


    /**
     * @return bool
     */
    public function isTypeCannotBeEmptyException() {
        return $this->getCode() == self::CODE_TYPE_CANNOT_BE_EMPTY;
    }
}