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
     * @var Team
     */
    private $team;
    
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
     * @param User $user
     * @return Member
     * @throws \SimplyTestable\ApiBundle\Exception\Services\TeamMember\Exception
     */
    public function add(User $user) {
        if (!$this->hasTeam()) {
            throw new TeamMemberServiceException(
                'MemberService has no Team set',
                TeamMemberServiceException::NO_TEAM_SET
            );
        }

        if ($this->belongsToTeam($user)) {
            throw new TeamMemberServiceException(
                'User is already on a team',
                TeamMemberServiceException::USER_ALREADY_ON_TEAM
            );
        }

        $member = new Member();
        $member->setTeam($this->team);
        $member->setUser($user);

        return $this->persistAndFlush($member);
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
     * @return bool
     */
    private function hasTeam() {
        return $this->team instanceof Team;
    }
    
}