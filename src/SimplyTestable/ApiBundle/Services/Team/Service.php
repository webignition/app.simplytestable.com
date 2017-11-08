<?php

namespace SimplyTestable\ApiBundle\Services\Team;

use Doctrine\ORM\EntityManagerInterface;
use SimplyTestable\ApiBundle\Repository\TeamRepository;
use SimplyTestable\ApiBundle\Entity\Team\Team;
use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Exception\Services\Team\Exception as TeamServiceException;

class Service
{
    /**
     * @var MemberService
     */
    private $memberService;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var TeamRepository
     */
    private $teamRepository;

    /**
     * @param MemberService $memberService
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(MemberService $memberService, EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->memberService = $memberService;

        $this->teamRepository = $entityManager->getRepository(Team::class);
    }

    /**
     * @param $name
     * @param User $leader
     *
     * @return Team
     * @throws TeamServiceException
     */
    public function create($name, User $leader)
    {
        if ($this->hasTeam($leader)) {
            throw new TeamServiceException(
                'User already leads a team',
                TeamServiceException::USER_ALREADY_LEADS_TEAM
            );
        }

        if ($this->memberService->belongsToTeam($leader)) {
            throw new TeamServiceException(
                'User already on a team',
                TeamServiceException::USER_ALREADY_ON_TEAM
            );
        }

        $name = trim($name);
        if (empty($name)) {
            throw new TeamServiceException(
                'Team name cannot be empty',
                TeamServiceException::CODE_NAME_EMPTY
            );
        }

        $isNameTaken = $this->teamRepository->getTeamCountByName($name) > 0;
        if ($isNameTaken) {
            throw new TeamServiceException(
                'Team name is already taken',
                TeamServiceException::CODE_NAME_TAKEN
            );
        }

        // Check if leader is already on a team

        $team = new Team();
        $team->setName($name);
        $team->setLeader($leader);

        $this->entityManager->persist($team);
        $this->entityManager->flush();

        return $team;
    }

    /**
     * @param User $leader
     *
     * @return bool
     */
    public function hasTeam(User $leader)
    {
        return $this->teamRepository->getTeamCountByLeader($leader) > 0;
    }

    /**
     * @return MemberService
     */
    public function getMemberService()
    {
        return $this->memberService;
    }

    /**
     * @param User $user
     *
     * @return null|User
     */
    public function getLeaderFor(User $user)
    {
        if ($this->hasTeam($user)) {
            return $user;
        }

        if ($this->memberService->belongsToTeam($user)) {
            return $this->memberService->getTeamByUser($user)->getLeader();
        }

        return null;
    }

    /**
     * @param User $user
     *
     * @return null|Team
     */
    public function getForUser(User $user)
    {
        if ($this->hasTeam($user)) {
            return $this->teamRepository->getTeamByLeader($user);
        }

        if ($this->memberService->belongsToTeam($user)) {
            return $this->memberService->getEntityRepository()->getMemberByUser($user)->getTeam();
        }

        return null;
    }

    /**
     * @param User $user
     *
     * @return User[]
     */
    public function getPeopleForUser(User $user)
    {
        if (!$this->hasForUser($user)) {
            return [$user];
        }

        $team = $this->getForUser($user);

        return $this->getPeople($team);
    }

    /**
     * @param User $user
     *
     * @return bool
     */
    public function hasForUser(User $user)
    {
        return !is_null($this->getForUser($user));
    }

    /**
     * @param User $leader
     * @param User $member
     *
     * @return bool
     * @throws TeamServiceException
     */
    public function remove(User $leader, User $member)
    {
        if (!$this->hasTeam($leader)) {
            throw new TeamServiceException(
                'User is not a leader',
                TeamServiceException::IS_NOT_LEADER
            );
        }

        $team = $this->getForUser($member);
        if (empty($team) || $team->getLeader()->getId() !== $leader->getId()) {
            throw new TeamServiceException(
                'User is not on leader\'s team',
                TeamServiceException::USER_IS_NOT_ON_LEADERS_TEAM
            );
        }

        return $this->memberService->remove($member);
    }

    /**
     * @param Team $team
     *
     * @return User[]
     */
    public function getPeople(Team $team)
    {
        $people = [$team->getLeader()];

        $members = $this->memberService->getMembers($team);

        foreach ($members as $member) {
            $people[] = $member->getUser();
        }

        return $people;
    }
}
