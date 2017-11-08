<?php

namespace Tests\ApiBundle\Functional\Services\Team;

use Mockery\MockInterface;
use SimplyTestable\ApiBundle\Entity\Team\Invite;
use SimplyTestable\ApiBundle\Repository\TeamInviteRepository;
use SimplyTestable\ApiBundle\Services\Team\InviteTokenGenerator;
use Tests\ApiBundle\Functional\AbstractBaseTestCase;

class InviteTokenGeneratorTest extends AbstractBaseTestCase
{
    public function testGenerateToken()
    {
        /* @var Invite|MockInterface */
        $invite = \Mockery::mock(Invite::class);

        /* @var TeamInviteRepository|MockInterface $teamInviteRepository */
        $teamInviteRepository = \Mockery::mock(TeamInviteRepository::class);
        $teamInviteRepository
            ->shouldReceive('findOneBy')
            ->andReturnValues([
                $invite,
                null,
            ]);

        $inviteTokenGenerator = new InviteTokenGenerator($teamInviteRepository);

        $token = $inviteTokenGenerator->generateToken();

        $this->assertNotNull($token);
        $this->assertInternalType('string', $token);

        $this->assertEmpty($teamInviteRepository->findOneBy([
            'token' => $token,
        ]));

        \Mockery::close();
    }
}
