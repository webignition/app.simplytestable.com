<?php

namespace App\Tests\Functional\Services;

use App\Entity\Team\Invite;
use App\Entity\Team\Team;
use App\Entity\User;
use App\Services\Team\InviteService;
use App\Tests\Services\UserFactory;
use App\Tests\Functional\AbstractBaseTestCase;
use App\Exception\Services\TeamInvite\Exception as TeamInviteServiceException;

class InviteServiceTest extends AbstractBaseTestCase
{
    /**
     * @var InviteService
     */
    private $inviteService;

    /**
     * @var User[]
     */
    private $users;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->inviteService = self::$container->get(InviteService::class);

        $userFactory = self::$container->get(UserFactory::class);
        $this->users = $userFactory->createPublicPrivateAndTeamUserSet();
    }

    /**
     * @dataProvider getFailureDataProvider
     *
     * @param string $inviterName
     * @param string $inviteeName
     * @param string $expectedExceptionMessage
     * @param string $expectedExceptionCode
     */
    public function testGetFailure($inviterName, $inviteeName, $expectedExceptionMessage, $expectedExceptionCode)
    {
        $inviter = $this->users[$inviterName];
        $invitee = $this->users[$inviteeName];

        $this->expectException(TeamInviteServiceException::class);
        $this->expectExceptionMessage($expectedExceptionMessage);
        $this->expectExceptionCode($expectedExceptionCode);

        $this->inviteService->get($inviter, $invitee);
    }

    /**
     * @return array
     */
    public function getFailureDataProvider()
    {
        return [
            'Inviter is not a team leader' => [
                'inviterName' => 'private',
                'inviteeName' => 'private',
                'expectedExceptionMessage' => 'Inviter is not a team leader',
                'expectedExceptionCode' => TeamInviteServiceException::INVITER_IS_NOT_A_LEADER,
            ],
            'Invitee is a team leader' => [
                'inviterName' => 'leader',
                'inviteeName' => 'leader',
                'expectedExceptionMessage' => 'Invitee is a team leader',
                'expectedExceptionCode' => TeamInviteServiceException::INVITEE_IS_A_LEADER,
            ],
            'Invitee is on a team' => [
                'inviterName' => 'leader',
                'inviteeName' => 'member1',
                'expectedExceptionMessage' => 'Invitee is on a team',
                'expectedExceptionCode' => TeamInviteServiceException::INVITEE_IS_ON_A_TEAM,
            ],
        ];
    }

    public function testGetSuccess()
    {
        $inviter = $this->users['leader'];
        $invitee = $this->users['private'];

        $invite = $this->inviteService->get($inviter, $invitee);

        $this->assertInstanceOf(Invite::class, $invite);
        $this->assertEquals($invitee, $invite->getUser());
        $this->assertEquals('Foo', $invite->getTeam()->getName());
        $this->assertNotNull($invite->getToken());
        $this->assertEquals($invite, $this->inviteService->get($inviter, $invitee));
    }

    public function testGetForTeam()
    {
        $entityManager = self::$container->get('doctrine.orm.entity_manager');
        $teamRepository = $entityManager->getRepository(Team::class);
        $userFactory = self::$container->get(UserFactory::class);

        $leader = $this->users['leader'];

        /* @var Team $team */
        $team = $teamRepository->findOneBy([
            'name' => 'Foo',
        ]);

        $teamInvites = $this->inviteService->getForTeam($team);
        $this->assertEmpty($teamInvites);

        $invitee1 = $userFactory->createAndActivateUser([
            UserFactory::KEY_EMAIL => 'invitee1@example.com',
        ]);

        $invite1 = $this->inviteService->get($leader, $invitee1);

        $teamInvites = $this->inviteService->getForTeam($team);
        $this->assertNotEmpty($teamInvites);
        $this->assertEquals([$invite1], $teamInvites);

        $invitee2 = $userFactory->createAndActivateUser([
            UserFactory::KEY_EMAIL => 'invitee2@example.com',
        ]);

        $invite2 = $this->inviteService->get($leader, $invitee2);

        $teamInvites = $this->inviteService->getForTeam($team);
        $this->assertEquals([$invite1, $invite2], $teamInvites);
    }

    public function testGetForTeamAndUser()
    {
        $entityManager = self::$container->get('doctrine.orm.entity_manager');
        $teamRepository = $entityManager->getRepository(Team::class);

        $leader = $this->users['leader'];
        $invitee = $this->users['private'];

        /* @var Team $team */
        $team = $teamRepository->findOneBy([
            'name' => 'Foo',
        ]);

        $this->assertEmpty($this->inviteService->getForTeamAndUser($team, $invitee));

        $invite = $this->inviteService->get($leader, $invitee);

        $this->assertEquals(
            $invite,
            $this->inviteService->getForTeamAndUser($team, $invitee)
        );
    }

    public function testGetForToken()
    {
        $leader = $this->users['leader'];
        $invitee = $this->users['private'];

        $invite = $this->inviteService->get($leader, $invitee);

        $this->assertEquals(
            $invite,
            $this->inviteService->getForToken($invite->getToken())
        );
    }

    public function testGetForUser()
    {
        $leader = $this->users['leader'];
        $invitee = $this->users['private'];

        $invite = $this->inviteService->get($leader, $invitee);

        $this->assertEquals(
            [$invite],
            $this->inviteService->getForUser($invitee)
        );
    }

    public function testHasAnyForUser()
    {
        $leader = $this->users['leader'];
        $invitee = $this->users['private'];

        $this->assertFalse($this->inviteService->hasAnyForUser($invitee));

        $this->inviteService->get($leader, $invitee);

        $this->assertTrue($this->inviteService->hasAnyForUser($invitee));
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        parent::tearDown();

        \Mockery::close();
    }
}
