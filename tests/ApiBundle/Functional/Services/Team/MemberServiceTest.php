<?php

namespace Tests\ApiBundle\Functional\Services\Team;

use SimplyTestable\ApiBundle\Entity\Team\Member;
use SimplyTestable\ApiBundle\Entity\Team\Team;
use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Services\Team\MemberService;
use Tests\ApiBundle\Factory\UserFactory;
use Tests\ApiBundle\Functional\AbstractBaseTestCase;
use SimplyTestable\ApiBundle\Exception\Services\TeamMember\Exception as TeamMemberServiceException;

class MemberServiceTest extends AbstractBaseTestCase
{
    /**
     * @var MemberService
     */
    private $memberService;

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

        $this->memberService = $this->container->get('simplytestable.services.teammemberservice');
        $userFactory = new UserFactory($this->container);

        $this->users = $userFactory->createPublicPrivateAndTeamUserSet();
    }

    public function testBelongsToTeam()
    {
        $this->assertFalse($this->memberService->belongsToTeam($this->users['private']));
        $this->assertFalse($this->memberService->belongsToTeam($this->users['leader']));
        $this->assertTrue($this->memberService->belongsToTeam($this->users['member1']));
    }

    public function testAddFailure()
    {
        $this->expectException(TeamMemberServiceException::class);
        $this->expectExceptionMessage('User is already on a team');
        $this->expectExceptionCode(TeamMemberServiceException::USER_ALREADY_ON_TEAM);

        $this->memberService->add(new Team(), $this->users['member1']);
    }

    public function testAddSuccess()
    {
        $entityManager = $this->container->get('doctrine.orm.entity_manager');
        $teamRepository = $entityManager->getRepository(Team::class);

        /* @var Team $team */
        $team = $teamRepository->findOneBy([
            'name' => 'Foo',
        ]);

        $user = $this->users['private'];

        $member = $this->memberService->add($team, $user);

        $this->assertInstanceOf(Member::class, $member);
        $this->assertEquals($user, $member->getUser());
        $this->assertEquals($team, $member->getTeam());
    }

    public function testRemove()
    {
        $entityManager = $this->container->get('doctrine.orm.entity_manager');
        $teamRepository = $entityManager->getRepository(Team::class);
        $teamService = $this->container->get('simplytestable.services.teamservice');

        /* @var Team $team */
        $team = $teamRepository->findOneBy([
            'name' => 'Foo',
        ]);

        $teamPeople = $teamService->getPeople($team);
        $this->assertEquals(
            [
                $this->users['leader'],
                $this->users['member1'],
                $this->users['member2'],
            ],
            $teamPeople
        );

        $this->memberService->remove($this->users['member1']);

        $teamPeople = $teamService->getPeople($team);
        $this->assertEquals(
            [
                $this->users['leader'],
                $this->users['member2'],
            ],
            $teamPeople
        );
    }

    public function testContains()
    {
        $entityManager = $this->container->get('doctrine.orm.entity_manager');
        $teamRepository = $entityManager->getRepository(Team::class);

        /* @var Team $team */
        $team = $teamRepository->findOneBy([
            'name' => 'Foo',
        ]);

        $this->assertFalse($this->memberService->contains($team, $this->users['private']));
        $this->assertFalse($this->memberService->contains($team, $this->users['leader']));
        $this->assertTrue($this->memberService->contains($team, $this->users['member1']));
    }

    public function testGetTeamByMember()
    {
        $entityManager = $this->container->get('doctrine.orm.entity_manager');
        $teamRepository = $entityManager->getRepository(Team::class);

        /* @var Team $team */
        $team = $teamRepository->findOneBy([
            'name' => 'Foo',
        ]);

        $this->assertNull($this->memberService->getTeamByMember($this->users['private']));
        $this->assertNull($this->memberService->getTeamByMember($this->users['leader']));
        $this->assertEquals($team, $this->memberService->getTeamByMember($this->users['member1']));
        $this->assertEquals($team, $this->memberService->getTeamByMember($this->users['member2']));
    }

    public function testGetMembers()
    {
        $entityManager = $this->container->get('doctrine.orm.entity_manager');
        $teamRepository = $entityManager->getRepository(Team::class);

        /* @var Team $team */
        $team = $teamRepository->findOneBy([
            'name' => 'Foo',
        ]);

        $members = $this->memberService->getMembers($team);

        $this->assertInternalType('array', $members);
        $this->assertCount(2, $members);

        foreach ($members as $member) {
            $this->assertInstanceOf(Member::class, $member);
            $this->assertEquals($team, $member->getTeam());
        }
    }
}
