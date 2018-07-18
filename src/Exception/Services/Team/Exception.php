<?php

namespace App\Exception\Services\Team;
use \Exception as BaseException;

class Exception extends BaseException {

    const CODE_NAME_EMPTY = 1;
    const CODE_NAME_TAKEN = 2;
    const USER_ALREADY_LEADS_TEAM = 3;
    const USER_ALREADY_ON_TEAM = 4;
    const IS_NOT_LEADER = 5;
    const USER_IS_NOT_ON_LEADERS_TEAM = 6;

    /**
     * @return bool
     */
    public function isNameEmptyException() {
        return $this->getCode() === self::CODE_NAME_EMPTY;
    }


    /**
     * @return bool
     */
    public function isNameTakenException() {
        return $this->getCode() === self::CODE_NAME_TAKEN;
    }


    /**
     * @return bool
     */
    public function isUserAlreadyLeadsTeamException() {
        return $this->getCode() === self::USER_ALREADY_LEADS_TEAM;
    }


    /**
     * @return bool
     */
    public function isUserAlreadyOnTeamException() {
        return $this->getCode() === self::USER_ALREADY_ON_TEAM;
    }
}