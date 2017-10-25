<?php

namespace SimplyTestable\ApiBundle\Model\User\Summary;

class Team implements \JsonSerializable
{
    /**
     * @var bool
     */
    private $userIsInTeam;

    /**
     * @var bool
     */
    private $hasInvite;

    /**
     * @param bool $userIsInTeam
     * @param bool $hasInvite
     */
    public function __construct($userIsInTeam, $hasInvite)
    {
        $this->userIsInTeam = $userIsInTeam;
        $this->hasInvite = $hasInvite;
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return [
            'in' => $this->userIsInTeam,
            'has_invite' => $this->hasInvite,
        ];
    }
}
