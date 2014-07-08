<?php

namespace SimplyTestable\ApiBundle\Exception\Services\TeamMember;
use \Exception as BaseException;

class Exception extends BaseException {

    const USER_ALREADY_ON_TEAM = 1;
    const NO_TEAM_SET = 2;

    /**
     * @param Exception $exception
     * @return bool
     */
    public function isUserAlreadyOnTeamException(Exception $exception) {
        return $exception->getCode() === self::USER_ALREADY_ON_TEAM;
    }


    /**
     * @param Exception $exception
     * @return bool
     */
    public function isNoTeamSetException(Exception $exception) {
        return $exception->getCode() === self::NO_TEAM_SET;
    }
}