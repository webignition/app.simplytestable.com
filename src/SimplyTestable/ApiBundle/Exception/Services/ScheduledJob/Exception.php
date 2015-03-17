<?php

namespace SimplyTestable\ApiBundle\Exception\Services\ScheduledJob;

class Exception extends \Exception {

    const CODE_USER_NOT_SET = 1;
    const CODE_MATCHING_SCHEDULED_JOB_EXISTS = 2;
    const CODE_UNABLE_TO_PERFORM_AS_USER_IS_IN_A_TEAM = 3;


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


    /**
     * @return bool
     */
    public function isUnableToPerformAsUserIsInATeamException() {
        return $this->getCode() == self::CODE_UNABLE_TO_PERFORM_AS_USER_IS_IN_A_TEAM;
    }
}