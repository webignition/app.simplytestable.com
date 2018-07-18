<?php

namespace AppBundle\Exception\Services\Job;
use \Exception as BaseException;

class RetrievalServiceException extends BaseException {

    const CODE_USER_NOT_SET = 1;
    const CODE_JOB_NOT_FOUND = 2;
    const CODE_NOT_AUTHORISED = 3;


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
    public function isJobNotFoundException() {
        return $this->getCode() === self::CODE_JOB_NOT_FOUND;
    }


    /**
     *
     * @return boolean
     */
    public function isNotAuthorisedException() {
        return $this->getCode() === self::CODE_NOT_AUTHORISED;
    }
}