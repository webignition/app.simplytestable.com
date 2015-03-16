<?php

namespace SimplyTestable\ApiBundle\Exception\Controller\ScheduledJob\Update;

class Exception extends \Exception {

    const CODE_UNKNOWN_JOB_CONFIGURATION = 1;
    const CODE_INVALID_SCHEDULE = 2;

    /**
     *
     * @return boolean
     */
    public function isUnknownJobConfigurationException() {
        return $this->getCode() === self::CODE_UNKNOWN_JOB_CONFIGURATION;
    }

    /**
     *
     * @return boolean
     */
    public function isInvalidScheduleException() {
        return $this->getCode() === self::CODE_INVALID_SCHEDULE;
    }
}