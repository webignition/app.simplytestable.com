<?php

namespace App\Services\Team;

use Doctrine\ORM\EntityManagerInterface;
use App\Repository\TeamMemberRepository;
use App\Entity\Team\Team;
use App\Entity\Team\Member;
use App\Entity\User;
use App\Exception\Services\TeamMember\Exception as TeamMemberServiceException;

class MemberService
{
    private $entityManager;
    private $teamMemberRepository;

    public function __construct(EntityManagerInterface $entityManager, TeamMemberRepository $teamMemberRepository)
    {
        $this->entityManager = $entityManager;
        $this->teamMemberRepository = $teamMemberRepository;
    }

    /**
     * @param User $user
     *
     * @return bool
     */
    public function belongsToTeam(User $user)
    {
        return $this->teamMemberRepository->getMemberCountByUser($user) > 0;
    }

    /**
     * @param Team $team
     * @param User $user
     *
     * @return Member
     * @throws TeamMemberServiceException
     */
    public function add(Team $team, User $user)
    {
        if ($this->belongsToTeam($user)) {
            throw new TeamMemberServiceException(
                'User is already on a team',
                TeamMemberServiceException::USER_ALREADY_ON_TEAM
            );
        }

        $member = new Member();
        $member->setTeam($team);
        $member->setUser($user);

        $this->entityManager->persist($member);
        $this->entityManager->flush();

        return $member;
    }

    /**
     * @param User $user
     */
    public function remove(User $user)
    {
        if ($this->belongsToTeam($user)) {
            $member = $this->teamMemberRepository->findOneBy([
                'user' => $user,
            ]);

            $this->entityManager->remove($member);
            $this->entityManager->flush();

            $member->clear();
        }
    }

    /**
     * @param Team $team
     * @param User $user
     *
     * @return bool
     */
    public function contains(Team $team, User $user)
    {
        return $this->teamMemberRepository->getTeamContainsUser($team, $user);
    }

    /**
     * @param User $user
     *
     * @return null|Team
     */
    public function getTeamByMember(User $user)
    {
        if (!$this->belongsToTeam($user)) {
            return null;
        }

        $member = $this->teamMemberRepository->findOneBy([
            'user' => $user,
        ]);

        return $member->getTeam();
    }

    /**
     * @param Team $team
     *
     * @return Member[]
     */
    public function getMembers(Team $team)
    {
        return $this->teamMemberRepository->findBy([
            'team' => $team
        ]);
    }
}
