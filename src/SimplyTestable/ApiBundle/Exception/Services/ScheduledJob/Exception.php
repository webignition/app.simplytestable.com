<?php

namespace SimplyTestable\ApiBundle\Exception\Services\ScheduledJob;

class Exception extends \Exception {

    const CODE_USER_NOT_SET = 1;
    const CODE_MATCHING_SCHEDULED_JOB_EXISTS = 2;


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
    public function isMatchingScheduledJobExistsException() {
        return $this->getCode() === self::CODE_MATCHING_SCHEDULED_JOB_EXISTS;
    }
}