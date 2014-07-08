<?php

namespace SimplyTestable\ApiBundle\Exception\Services\UserAccountPlan;
use \Exception as BaseException;

class Exception extends BaseException {

    const CODE_USER_IS_TEAM_MEMBER = 1;

    /**
     * @return bool
     */
    public function isUserIsTeamMemberException() {
        return $this->getCode() === self::CODE_USER_IS_TEAM_MEMBER;
    }
}