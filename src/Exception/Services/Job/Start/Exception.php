<?php

namespace App\Exception\Services\Job\Start;

class Exception extends \Exception {

    const CODE_UNROUTABLE_WEBSITE = 1;


    /**
     *
     * @return boolean
     */
    public function isUnroutableWebsiteException() {
        return $this->getCode() === self::CODE_UNROUTABLE_WEBSITE;
    }
}