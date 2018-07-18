<?php
namespace App\Services\Team;

use Doctrine\ORM\EntityManagerInterface;
use App\Repository\TeamInviteRepository;
use App\Services\Team\Service as TeamService;
use App\Entity\Team\Team;
use App\Entity\Team\Invite;
use App\Entity\User;
use App\Exception\Services\TeamInvite\Exception as TeamInviteServiceException;
use FOS\UserBundle\Util\TokenGeneratorInterface;

class InviteService
{
    /**
     * @var TeamService
     */
    private $teamService;

    /**
     * @var TeamInviteRepository
     */
    private $teamInviteRepository;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var TokenGeneratorInterface
     */
    private $tokenGenerator;

    /**
     * @param Service $teamService
     * @param EntityManagerInterface $entityManager
     * @param TokenGeneratorInterface $tokenGenerator
     */
    public function __construct(
        TeamService $teamService,
        EntityManagerInterface $entityManager,
        TokenGeneratorInterface $tokenGenerator
    ) {
        $this->entityManager = $entityManager;
        $this->teamService = $teamService;
        $this->tokenGenerator = $tokenGenerator;

        $this->teamInviteRepository = $entityManager->getRepository(Invite::class);
    }

    /**
     * @param User $inviter
     * @param User $invitee
     *
     * @return null|Invite
     * @throws TeamInviteServiceException
     */
    public function get(User $inviter, User $invitee)
    {
        if (!$this->teamService->hasTeam($inviter)) {
            throw new TeamInviteServiceException(
                'Inviter is not a team leader',
                TeamInviteServiceException::INVITER_IS_NOT_A_LEADER
            );
        }

        if ($this->teamService->hasTeam($invitee)) {
            throw new TeamInviteServiceException(
                'Invitee is a team leader',
                TeamInviteServiceException::INVITEE_IS_A_LEADER
            );
        }

        if ($this->teamService->getMemberService()->belongsToTeam($invitee)) {
            throw new TeamInviteServiceException(
                'Invitee is on a team',
                TeamInviteServiceException::INVITEE_IS_ON_A_TEAM
            );
        }

        $invite = $this->teamInviteRepository->findOneBy([
            'team' => $this->teamService->getForUser($inviter),
            'user' => $invitee
        ]);

        if (empty($invite)) {
            $invite = new Invite();
            $invite->setTeam($this->teamService->getForUser($inviter));
            $invite->setUser($invitee);
            $invite->setToken($this->tokenGenerator->generateToken());

            $this->entityManager->persist($invite);
            $this->entityManager->flush();
        }

        return $invite;
    }

    /**
     * @param Team $team
     * @param User $user
     *
     * @return Invite
     */
    public function getForTeamAndUser(Team $team, User $user)
    {
        return $this->teamInviteRepository->findOneBy([
            'team' => $team,
            'user' => $user
        ]);
    }

    /**
     * @param User $user
     *
     * @return bool
     */
    public function hasAnyForUser(User $user)
    {
        $invite = $this->teamInviteRepository->findOneBy([
            'user' => $user
        ]);

        return $invite instanceof Invite;
    }

    /**
     * @param $token
     *
     * @return null|Invite
     */
    public function getForToken($token)
    {
        return $this->teamInviteRepository->findOneBy([
            'token' => $token
        ]);
    }

    /**
     * @param Team $team
     *
     * @return Invite[]
     */
    public function getForTeam(Team $team)
    {
        return $this->teamInviteRepository->findBy([
            'team' => $team
        ]);
    }

    /**
     * @param User $user
     *
     * @return Invite[]
     */
    public function getForUser(User $user)
    {
        return $this->teamInviteRepository->findBy([
            'user' => $user
        ]);
    }
}
