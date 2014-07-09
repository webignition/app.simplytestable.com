<?php

namespace SimplyTestable\ApiBundle\Exception\Services\Team;
use \Exception as BaseException;

class Exception extends BaseException {

    const CODE_NAME_EMPTY = 1;
    const CODE_NAME_TAKEN = 2;
    const USER_ALREADY_LEADS_TEAM = 3;
    const USER_ALREADY_ON_TEAM = 4;

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