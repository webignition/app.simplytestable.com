<?php

namespace SimplyTestable\ApiBundle\Exception\Services\Team;
use \Exception as BaseException;

class Exception extends BaseException {

    const CODE_NAME_EMPTY = 1;
    const CODE_NAME_TAKEN = 2;
    const USER_ALREADY_LEADS_TEAM = 3;


    /**
     * @param Exception $exception
     * @return bool
     */
    public function isNameTakenException(Exception $exception) {
        return $exception->getCode() === self::CODE_NAME_TAKEN;
    }


    /**
     * @param Exception $exception
     * @return bool
     */
    public function isUserAlreadyLeadsTeamException(Exception $exception) {
        return $exception->getCode() === self::USER_ALREADY_LEADS_TEAM;
    }
}