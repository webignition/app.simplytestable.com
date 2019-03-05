<?php

namespace App\Tests\Functional\Services\Team;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Mockery\Mock;
use App\Entity\Team\Invite;
use App\Services\Team\InviteTokenGenerator;
use App\Tests\Functional\AbstractBaseTestCase;

class InviteTokenGeneratorTest extends AbstractBaseTestCase
{
    public function testGenerateToken()
    {
        /* @var Invite|Mock */
        $invite = \Mockery::mock(Invite::class);

        /* @var EntityRepository|Mock $teamInviteRepository */
        $teamInviteRepository = \Mockery::mock(EntityRepository::class);
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
