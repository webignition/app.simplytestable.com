<?php

namespace Tests\ApiBundle\Functional\Services\Team;

use Doctrine\ORM\EntityManagerInterface;
use Mockery\Mock;
use SimplyTestable\ApiBundle\Entity\Team\Invite;
use SimplyTestable\ApiBundle\Repository\TeamInviteRepository;
use SimplyTestable\ApiBundle\Services\Team\InviteTokenGenerator;
use Tests\ApiBundle\Functional\AbstractBaseTestCase;

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
