<?php

namespace App\Exception\Services\TeamInvite;
use \Exception as BaseException;

class Exception extends BaseException {

    const INVITER_IS_NOT_A_LEADER = 1;
    const INVITEE_IS_A_LEADER = 2;
    const INVITEE_IS_ON_A_TEAM = 3;

    /**
     * @return bool
     */
    public function isIntiverIsNotALeaderException() {
        return $this->getCode() === self::INVITER_IS_NOT_A_LEADER;
    }

    /**
     * @return bool
     */
    public function isIntiveeIsALeaderException() {
        return $this->getCode() === self::INVITEE_IS_A_LEADER;
    }

    /**
     * @return bool
     */
    public function isIntiveeIsOnATeamException() {
        return $this->getCode() === self::INVITEE_IS_ON_A_TEAM;
    }
}