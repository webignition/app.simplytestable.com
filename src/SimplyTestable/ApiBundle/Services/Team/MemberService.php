<?php
namespace SimplyTestable\ApiBundle\Services\Team;

use Doctrine\ORM\EntityManager;
use SimplyTestable\ApiBundle\Services\EntityService;
use SimplyTestable\ApiBundle\Entity\Team\Team;
use SimplyTestable\ApiBundle\Entity\Team\Member;
use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Exception\Services\TeamMember\Exception as TeamMemberServiceException;

class MemberService extends EntityService {
    
    const ENTITY_NAME = 'SimplyTestable\ApiBundle\Entity\Team\Member';

    
    /**
     *
     * @return string
     */
    protected function getEntityName() {
        return self::ENTITY_NAME;
    }


    /**
     * @param Team $team
     * @return MemberService
     */
    public function setTeam(Team $team) {
        $this->team = $team;
        return $this;
    }


    /**
     * @param User $user
     * @return bool
     */
    public function belongsToTeam(User $user) {
        return $this->getEntityRepository()->getMemberCountByUser($user) > 0;
    }


    /**
     * @param Team $team
     * @param User $user
     * @return Member
     * @throws \SimplyTestable\ApiBundle\Exception\Services\TeamMember\Exception
     */
    public function add(Team $team, User $user) {
        if ($this->belongsToTeam($user)) {
            throw new TeamMemberServiceException(
                'User is already on a team',
                TeamMemberServiceException::USER_ALREADY_ON_TEAM
            );
        }

        $member = new Member();
        $member->setTeam($team);
        $member->setUser($user);

        return $this->persistAndFlush($member);
    }


    /**
     * @param User $user
     * @return bool
     */
    public function remove(User $user) {
        if (!$this->belongsToTeam($user)) {
            return true;
        }

        $member = $this->getEntityRepository()->getMemberByUser($user);

        $this->getEntityManager()->remove($member);
        $this->getEntityManager()->flush($member);

        $member->clear();

        return true;
    }


    /**
     * @param Member $member
     * @return Member
     */
    public function persistAndFlush(Member $member) {
        $this->getEntityManager()->persist($member);
        $this->getEntityManager()->flush();
        return $member;
    }

    
    /**
     *
     * @return \SimplyTestable\ApiBundle\Repository\TeamMemberRepository
     */
    public function getEntityRepository() {
        return parent::getEntityRepository();
    }


    /**
     * @param Team $team
     * @param User $user
     * @return bool
     */
    public function contains(Team $team, User $user) {
        return $this->getEntityRepository()->getTeamContainsUser($team, $user);
    }


    /**
     * @param User $user
     * @return null|Team
     */
    public function getTeamByUser(User $user) {
        if (!$this->belongsToTeam($user)) {
            return null;
        }

        $member = $this->getEntityRepository()->getMemberByUser($user);
        return $member->getTeam();
    }


    /**
     * @param Team $team
     * @return Member[]
     */
    public function getMembers(Team $team) {
        return $this->getEntityRepository()->findBy([
            'team' => $team
        ]);
    }
    
}