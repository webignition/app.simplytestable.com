<?php

namespace Tests\ApiBundle\Functional\Services\Team;

use SimplyTestable\ApiBundle\Entity\Team\Team;
use SimplyTestable\ApiBundle\Services\Team\Service as TeamService;
use SimplyTestable\ApiBundle\Exception\Services\Team\Exception as TeamServiceException;
use SimplyTestable\ApiBundle\Services\Team\Service;
use Tests\ApiBundle\Factory\UserFactory;
use Tests\ApiBundle\Functional\AbstractBaseTestCase;

class ServiceTest extends AbstractBaseTestCase
{
    /**
     * @var TeamService
     */
    private $teamService;

    /**
     * @var UserFactory
     */
    private $userFactory;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->teamService = $this->container->get(Service::class);
        $this->userFactory = new UserFactory($this->container);
    }

    /**
     * @dataProvider createFailureDataProvider
     *
     * @param string $teamName
     * @param string $userName
     * @param string $expectedExceptionMessage
     * @param int $expectedExceptionCode
     */
    public function testCreateFailure($teamName, $userName, $expectedExceptionMessage, $expectedExceptionCode)
    {
        $users = $this->userFactory->createPublicPrivateAndTeamUserSet();
        $user = $users[$userName];

        $this->expectException(TeamServiceException::class);
        $this->expectExceptionMessage($expectedExceptionMessage);
        $this->expectExceptionCode($expectedExceptionCode);

        $this->teamService->create($teamName, $user);
    }

    /**
     * @return array
     */
    public function createFailureDataProvider()
    {
        return [
            'user already leads a team' => [
                'teamName' => 'bar',
                'userName' => 'leader',
                'expectedExceptionMessage' => 'User already leads a team',
                'expectedExceptionCode' => TeamServiceException::USER_ALREADY_LEADS_TEAM,
            ],
            'user is already on a team' => [
                'teamName' => 'bar',
                'userName' => 'member1',
                'expectedExceptionMessage' => 'User already on a team',
                'expectedExceptionCode' => TeamServiceException::USER_ALREADY_ON_TEAM,
            ],
            'null team name' => [
                'teamName' => null,
                'userName' => 'private',
                'expectedExceptionMessage' => 'Team name cannot be empty',
                'expectedExceptionCode' => TeamServiceException::CODE_NAME_EMPTY,
            ],
            'blank team name' => [
                'teamName' => '',
                'userName' => 'private',
                'expectedExceptionMessage' => 'Team name cannot be empty',
                'expectedExceptionCode' => TeamServiceException::CODE_NAME_EMPTY,
            ],
            'team name is already taken' => [
                'teamName' => 'Foo',
                'userName' => 'private',
                'expectedExceptionMessage' => 'Team name is already taken',
                'expectedExceptionCode' => TeamServiceException::CODE_NAME_TAKEN,
            ],
        ];
    }

    public function testCreateSuccess()
    {
        $user = $this->userFactory->create();
        $teamName = 'Team Name';

        $team = $this->teamService->create($teamName, $user);

        $this->assertInstanceOf(Team::class, $team);
        $this->assertNotNull($team->getId());
        $this->assertEquals($teamName, $team->getName());
        $this->assertEquals($user, $team->getLeader());
    }

    /**
     * @dataProvider getLeaderForDataProvider
     *
     * @param string $userName
     * @param string $expectedLeaderName
     */
    public function testGetLeaderFor($userName, $expectedLeaderName)
    {
        $users = $this->userFactory->createPublicPrivateAndTeamUserSet();
        $user = $users[$userName];

        $leader = $this->teamService->getLeaderFor($user);

        $expectedLeader = empty($expectedLeaderName)
            ? null
            : $users[$expectedLeaderName];

        $this->assertEquals($expectedLeader, $leader);
    }

    /**
     * @return array
     */
    public function getLeaderForDataProvider()
    {
        return [
            'private user has no leader' => [
                'userName' => 'private',
                'expectedLeaderName' => null,
            ],
            'team leader has self as leader' => [
                'userName' => 'leader',
                'expectedLeaderName' => 'leader',
            ],
            'team member has leader as leader' => [
                'userName' => 'member1',
                'expectedLeaderName' => 'leader',
            ],
        ];
    }

    /**
     * @dataProvider getForUserDataProvider
     *
     * @param string $userName
     * @param string $expectedTeamName
     */
    public function testGetForUser($userName, $expectedTeamName)
    {
        $users = $this->userFactory->createPublicPrivateAndTeamUserSet();
        $user = $users[$userName];

        $team = $this->teamService->getForUser($user);

        if (empty($expectedTeamName)) {
            $this->assertNull($team);
        } else {
            $this->assertEquals($expectedTeamName, $team->getName());
        }
    }

    /**
     * @return array
     */
    public function getForUserDataProvider()
    {
        return [
            'private user has no team' => [
                'userName' => 'private',
                'expectedTeamName' => null,
            ],
            'team leader has Foo team' => [
                'userName' => 'leader',
                'expectedTeamName' => 'Foo',
            ],
            'team member has Foo team' => [
                'userName' => 'member1',
                'expectedTeamName' => 'Foo',
            ],
        ];
    }

    /**
     * @dataProvider getPeopleForUserDataProvider
     *
     * @param string $userName
     * @param string[] $expectedPeopleEmails
     */
    public function testGetPeopleForUser($userName, $expectedPeopleEmails)
    {
        $users = $this->userFactory->createPublicPrivateAndTeamUserSet();
        $user = $users[$userName];

        $people = $this->teamService->getPeopleForUser($user);

        $peopleEmails = [];

        foreach ($people as $person) {
            $peopleEmails[] = $person->getEmail();
        }

        $this->assertEquals($expectedPeopleEmails, $peopleEmails);
    }

    /**
     * @return array
     */
    public function getPeopleForUserDataProvider()
    {
        return [
            'private user has no team' => [
                'userName' => 'private',
                'expectedPeopleEmails' => [
                    'private@example.com',
                ],
            ],
            'team leader has Foo team' => [
                'userName' => 'leader',
                'expectedPeopleEmails' => [
                    'leader@example.com',
                    'member1@example.com',
                    'member2@example.com',
                ],
            ],
            'team member has Foo team' => [
                'userName' => 'member1',
                'expectedPeopleEmails' => [
                    'leader@example.com',
                    'member1@example.com',
                    'member2@example.com',
                ],
            ],
        ];
    }

    /**
     * @dataProvider removeFailureDataProvider
     *
     * @param string $leaderName
     * @param string $userNameToRemove
     * @param string $expectedExceptionMessage
     * @param int $expectedExceptionCode
     */
    public function testRemoveFailure($leaderName, $userNameToRemove, $expectedExceptionMessage, $expectedExceptionCode)
    {
        $users = $this->userFactory->createPublicPrivateAndTeamUserSet();

        $leader = $users[$leaderName];
        $userToRemove = $users[$userNameToRemove];

        $this->expectException(TeamServiceException::class);
        $this->expectExceptionMessage($expectedExceptionMessage);
        $this->expectExceptionCode($expectedExceptionCode);

        $this->teamService->remove($leader, $userToRemove);
    }

    /**
     * @return array
     */
    public function removeFailureDataProvider()
    {
        return [
            'user is not a leader' => [
                'leaderName' => 'private',
                'userNameToRemove' => 'private',
                'expectedExceptionMessage' => 'User is not a leader',
                'expectedExceptionCode' => TeamServiceException::IS_NOT_LEADER,
            ],
            'user is not on leaders team' => [
                'leaderName' => 'leader',
                'userNameToRemove' => 'private',
                'expectedExceptionMessage' => 'User is not on leader\'s team',
                'expectedExceptionCode' => TeamServiceException::USER_IS_NOT_ON_LEADERS_TEAM,
            ],
        ];
    }

    public function testRemoveSuccess()
    {
        $users = $this->userFactory->createPublicPrivateAndTeamUserSet();

        $leader = $users['leader'];
        $memberToRemove = $users['member1'];
        $memberTeam = $this->teamService->getForUser($memberToRemove);

        $teamEmails = [];
        foreach ($this->teamService->getPeople($memberTeam) as $person) {
            $teamEmails[] = $person->getEmail();
        }

        $this->assertEquals([
            'leader@example.com',
            'member1@example.com',
            'member2@example.com',
        ], $teamEmails);

        $this->teamService->remove($leader, $memberToRemove);

        $teamEmails = [];
        foreach ($this->teamService->getPeople($memberTeam) as $person) {
            $teamEmails[] = $person->getEmail();
        }

        $this->assertEquals([
            'leader@example.com',
            'member2@example.com',
        ], $teamEmails);
    }
}
