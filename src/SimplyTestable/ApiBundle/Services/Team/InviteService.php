<?php
namespace SimplyTestable\ApiBundle\Services\Team;

use Doctrine\ORM\EntityManager;
use SimplyTestable\ApiBundle\Services\EntityService;
use SimplyTestable\ApiBundle\Services\Team\Service as TeamService;
use SimplyTestable\ApiBundle\Entity\Team\Team;
use SimplyTestable\ApiBundle\Entity\Team\Member;
use SimplyTestable\ApiBundle\Entity\Team\Invite;
use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Exception\Services\TeamInvite\Exception as TeamInviteServiceException;

class InviteService extends EntityService {
    
    const ENTITY_NAME = 'SimplyTestable\ApiBundle\Entity\Team\Invite';

    /**
     * @var TeamService
     */
    private $teamService;


    /**
     * @param Service $teamService
     * @param EntityManager $entityManager
     */
    public function __construct(TeamService $teamService, EntityManager $entityManager) {
        $this->teamService = $teamService;
        parent::__construct($entityManager);
    }


    /**
     *
     * @return string
     */
    protected function getEntityName() {
        return self::ENTITY_NAME;
    }


    /**
     * @param Invite $invite
     * @return Invite
     */
    public function persistAndFlush(Invite $invite) {
        $this->getEntityManager()->persist($invite);
        $this->getEntityManager()->flush();
        return $invite;
    }


    /**
     *
     * @return \SimplyTestable\ApiBundle\Repository\TeamInviteRepository
     */
    public function getEntityRepository() {
        return parent::getEntityRepository();
    }


    public function get(User $inviter, User $invitee) {
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

        if($this->teamService->getMemberService()->belongsToTeam($invitee)) {
            throw new TeamInviteServiceException(
                'Invitee is a team leader',
                TeamInviteServiceException::INVITEE_IS_ON_A_TEAM
            );
        }

        if ($this->has($inviter, $invitee)) {
            return $this->fetch($inviter, $invitee);
        }

        return $this->create($inviter, $invitee);
    }


    /**
     * @param $inviter
     * @param $invitee
     * @return Invite
     */
    private function create($inviter, $invitee) {
        $invite = new Invite();
        $invite->setTeam($this->teamService->getForUser($inviter));
        $invite->setUser($invitee);
        $invite->setToken(md5(rand()));

        return $this->persistAndFlush($invite);
    }


    /**
     * @param $inviter
     * @param $invitee
     * @return null|Invite
     */
    private function fetch($inviter, $invitee) {
        return $this->getEntityRepository()->findOneBy([
            'team' => $this->teamService->getForUser($inviter),
            'user' => $invitee
        ]);
    }


    /**
     * @param User $inviter
     * @param User $invitee
     * @return bool
     */
    private function has(User $inviter, User $invitee) {
        return $this->fetch($inviter, $invitee) instanceof Invite;
    }

    
}