<?php

namespace App\Exception\Services\JobPreparation;
use \Exception as BaseException;

class Exception extends BaseException {

    const CODE_JOB_IN_WRONG_STATE_CODE = 1;


    /**
     *
     * @return boolean
     */
    public function isJobInWrongStateException() {
        return $this->getCode() === self::CODE_JOB_IN_WRONG_STATE_CODE;
    }


}