<?php

namespace Tests\ApiBundle\Unit\User\Summary;

use SimplyTestable\ApiBundle\Model\User\Summary\Team as TeamSummary;

class TeamTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider jsonSerializeDataProvider
     *
     * @param bool $userIsInTeam
     * @param bool $hasInvite
     * @param array $expectedReturnValue
     */
    public function testJsonSerialize(
        $userIsInTeam,
        $hasInvite,
        $expectedReturnValue
    ) {
        $teamSummary = new TeamSummary($userIsInTeam, $hasInvite);

        $this->assertEquals($expectedReturnValue, $teamSummary->jsonSerialize());
    }

    /**
     * @return array
     */
    public function jsonSerializeDataProvider()
    {
        return [
            'not in team, no invite' => [
                'userIsInTeam' => false,
                'hasInvite' => false,
                'expectedReturnValue' => [
                    'in' => false,
                    'has_invite' => false,
                ],
            ],
            'not in team, has invite' => [
                'userIsInTeam' => false,
                'hasInvite' => true,
                'expectedReturnValue' => [
                    'in' => false,
                    'has_invite' => true,
                ],
            ],
            'in team, no invite' => [
                'userIsInTeam' => true,
                'hasInvite' => false,
                'expectedReturnValue' => [
                    'in' => true,
                    'has_invite' => false,
                ],
            ],
            'in team, has invite' => [
                'userIsInTeam' => true,
                'hasInvite' => true,
                'expectedReturnValue' => [
                    'in' => true,
                    'has_invite' => true,
                ],
            ],
        ];
    }
}
