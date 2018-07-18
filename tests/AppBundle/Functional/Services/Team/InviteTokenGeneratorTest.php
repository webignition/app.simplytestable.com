<?php

namespace Tests\AppBundle\Functional\Services\Team;

use Doctrine\ORM\EntityManagerInterface;
use Mockery\Mock;
use AppBundle\Entity\Team\Invite;
use AppBundle\Repository\TeamInviteRepository;
use AppBundle\Services\Team\InviteTokenGenerator;
use Tests\AppBundle\Functional\AbstractBaseTestCase;

class InviteTokenGeneratorTest extends AbstractBaseTestCase
{
    public function testGenerateToken()
    {
        /* @var Invite|Mock */
        $invite = \Mockery::mock(Invite::class);

        /* @var TeamInviteRepository|Mock $teamInviteRepository */
        $teamInviteRepository = \Mockery::mock(TeamInviteRepository::class);
        $teamInviteRepository
            ->shouldReceive('findOneBy')
            ->andReturnValues([
                $invite,
                null,
            ]);

        /* @var EntityManagerInterface|Mock $entityManager */
        $entityManager = \Mockery::mock(EntityManagerInterface::class);
        $entityManager
            ->shouldReceive('getRepository')
            ->with(Invite::class)
            ->andReturn($teamInviteRepository);

        $inviteTokenGenerator = new InviteTokenGenerator($entityManager);

        $token = $inviteTokenGenerator->generateToken();

        $this->assertNotNull($token);
        $this->assertInternalType('string', $token);

        $this->assertEmpty($teamInviteRepository->findOneBy([
            'token' => $token,
        ]));

        \Mockery::close();
    }
}
