<?php

namespace App\Exception\Services\TeamMember;
use \Exception as BaseException;

class Exception extends BaseException {

    const USER_ALREADY_ON_TEAM = 1;

    /**
     * @param Exception $exception
     * @return bool
     */
    public function isUserAlreadyOnTeamException(Exception $exception) {
        return $exception->getCode() === self::USER_ALREADY_ON_TEAM;
    }
}