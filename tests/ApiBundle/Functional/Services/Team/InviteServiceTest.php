<?php

namespace Tests\ApiBundle\Functional\Services;

use SimplyTestable\ApiBundle\Entity\Team\Invite;
use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Repository\TeamInviteRepository;
use SimplyTestable\ApiBundle\Services\Team\InviteService;
use Tests\ApiBundle\Factory\UserFactory;
use Tests\ApiBundle\Functional\AbstractBaseTestCase;
use SimplyTestable\ApiBundle\Exception\Services\TeamInvite\Exception as TeamInviteServiceException;

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

        $this->inviteService = $this->container->get('simplytestable.services.teaminviteservice');

        $userFactory = new UserFactory($this->container);
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
        $this->assertNotNull($invite->getId());
        $this->assertNotNull($invite->getToken());

        $this->assertEquals($invite, $this->inviteService->get($inviter, $invitee));
    }
}
