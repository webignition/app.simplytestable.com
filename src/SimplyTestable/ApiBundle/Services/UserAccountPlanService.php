<?php
namespace SimplyTestable\ApiBundle\Services;

use Doctrine\ORM\EntityManager;
use SimplyTestable\ApiBundle\Entity\State;
use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Entity\Account\Plan\Plan as AccountPlan;
use SimplyTestable\ApiBundle\Entity\UserAccountPlan;


class UserAccountPlanService extends EntityService {
    
    const ENTITY_NAME = 'SimplyTestable\ApiBundle\Entity\UserAccountPlan';
    
    /**
     *
     * @return string
     */
    protected function getEntityName() {
        return self::ENTITY_NAME;
    }
    
    
    /**
     * 
     * @param \SimplyTestable\ApiBundle\Entity\User $user
     * @param \SimplyTestable\ApiBundle\Entity\Account\Plan\Plan $plan
     * @return UserAccountPlan
     */
    public function create(User $user, AccountPlan $plan) {
        $userAccountPlan = new UserAccountPlan();
        $userAccountPlan->setUser($user);
        $userAccountPlan->setPlan($plan);
        
        return $this->persistAndFlush($userAccountPlan);
    }
    
    /**
     *
     * @param UserAccountPlan $userAccountPlan
     * @return UserAccountPlan
     */
    private function persistAndFlush(UserAccountPlan $userAccountPlan) {
        $this->getEntityManager()->persist($userAccountPlan);
        $this->getEntityManager()->flush();
        return $userAccountPlan;
    }       
}