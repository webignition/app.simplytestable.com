<?php

namespace SimplyTestable\ApiBundle\Exception\Services\ScheduledJob;

class Exception extends \Exception {

    const CODE_USER_NOT_SET = 1;

    /**
     *
     * @return boolean
     */
    public function isUserNotSetException() {
        return $this->getCode() === self::CODE_USER_NOT_SET;
    }
}