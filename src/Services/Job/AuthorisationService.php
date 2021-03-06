<?php

namespace App\Services\Job;

use App\Entity\Team\Member;
use App\Entity\Team\Team;
use App\Entity\User;
use App\Repository\JobRepository;
use App\Repository\TeamMemberRepository;
use App\Repository\TeamRepository;
use App\Services\Team\Service;
use App\Services\UserService;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class AuthorisationService
{
    private $publicUser;
    private $jobRepository;
    private $teamMemberRepository;
    private $teamRepository;
    private $teamService;
    private $tokenStorage;

    public function __construct(
        UserService $userService,
        JobRepository $jobRepository,
        TeamMemberRepository $teamMemberRepository,
        TeamRepository $teamRepository,
        Service $teamService,
        TokenStorageInterface $tokenStorage
    ) {
        $this->publicUser = $userService->getPublicUser();
        $this->jobRepository = $jobRepository;
        $this->teamMemberRepository = $teamMemberRepository;
        $this->teamRepository = $teamRepository;
        $this->teamService = $teamService;
        $this->tokenStorage = $tokenStorage;
    }

    public function isAuthorised(int $jobId): bool
    {
        if ($this->jobRepository->isPublic($jobId)) {
            return true;
        }

        /* @var User $user */
        $user = $this->tokenStorage->getToken()->getUser();

        if ($user->equals($this->publicUser)) {
            return $this->jobRepository->isOwnedByUser($user, $jobId);
        }

        $users = [
            $this->publicUser,
        ];

        $team = null;

        /* @var Member|null $teamMember */
        $teamMember = $this->teamMemberRepository->findOneBy([
            'user' => $user,
        ]);

        $team = $teamMember instanceof Member
            ? $teamMember->getTeam()
            : $this->teamRepository->findOneBy([
                  'leader' => $user,
              ]);

        if ($team instanceof Team) {
            $users = array_merge($users, $this->teamService->getPeople($team));
        } else {
            $users[] = $user;
        }

        return $this->jobRepository->isOwnedByUsers($users, $jobId);
    }
}
