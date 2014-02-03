<?php

namespace SimplyTestable\ApiBundle\Exception\Services\Job;
use \Exception as BaseException;

class WebsiteResolutionException extends BaseException {    
    
    const CODE_JOB_IN_WRONG_STATE_CODE = 1;
    
    
    /**
     * 
     * @return boolean
     */
    public function isJobInWrongStateException() {
        return $this->getCode() === self::CODE_JOB_IN_WRONG_STATE_CODE;
    }
}