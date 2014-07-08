<?php
namespace SimplyTestable\ApiBundle\Services\Team;

use Doctrine\ORM\EntityManager;
use SimplyTestable\ApiBundle\Services\EntityService;
use SimplyTestable\ApiBundle\Entity\Team\Team;
use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Services\Team\MemberService;
use SimplyTestable\ApiBundle\Exception\Services\Team\Exception as TeamServiceException;

class Service extends EntityService {
    
    const ENTITY_NAME = 'SimplyTestable\ApiBundle\Entity\Team\Team';


    /**
     * @var MemberService
     */
    private $memberService;

    
    /**
     *
     * @return string
     */
    protected function getEntityName() {
        return self::ENTITY_NAME;
    }


    /**
     * @param MemberService $memberService
     * @param EntityManager $entityManager
     */
    public function __construct(MemberService $memberService, EntityManager $entityManager) {
        $this->memberService = $memberService;

        parent::__construct($entityManager);
    }


    /**
     * @param $name
     * @param User $leader
     * @return Team
     * @throws \SimplyTestable\ApiBundle\Exception\Services\Team\Exception
     */
    public function create($name, User $leader) {
        $name = trim($name);
        if ($name == '') {
            throw new TeamServiceException(
                'Team name cannot be empty',
                TeamServiceException::CODE_NAME_EMPTY
            );
        }

        if ($this->isNameTaken($name)) {
            throw new TeamServiceException(
                'Team name is already taken',
                TeamServiceException::CODE_NAME_TAKEN
            );
        }

        if ($this->hasTeam($leader)) {
            throw new TeamServiceException(
                'User already leads a team',
                TeamServiceException::USER_ALREADY_LEADS_TEAM
            );
        }

        if ($this->getMemberService()->belongsToTeam($leader)) {
            throw new TeamServiceException(
                'User already on a team',
                TeamServiceException::USER_ALREADY_ON_TEAM
            );
        }

        // Check if leader is already on a team

        $team = new Team();
        $team->setName($name);
        $team->setLeader($leader);

        return $this->persistAndFlush($team);
    }


    /**
     * @param User $leader
     * @return bool
     */
    public function hasTeam(User $leader) {
        return $this->getEntityRepository()->getTeamCountByLeader($leader) > 0;
    }


    /**
     * @param $name
     * @return bool
     */
    private function isNameTaken($name) {
        return $this->getEntityRepository()->getTeamCountByName($name) > 0;
    }


    /**
     * @param Team $team
     * @return Team
     */
    public function persistAndFlush(Team $team) {
        $this->getEntityManager()->persist($team);
        $this->getEntityManager()->flush();
        return $team;
    } 

    
    /**
     *
     * @return \SimplyTestable\ApiBundle\Repository\TeamRepository
     */
    public function getEntityRepository() {
        return parent::getEntityRepository();
    }


    /**
     * @return MemberService
     */
    public function getMemberService() {
        return $this->memberService;
    }
    
}