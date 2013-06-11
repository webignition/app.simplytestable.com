<?php
namespace SimplyTestable\ApiBundle\Services;

use Doctrine\ORM\EntityManager;
use SimplyTestable\ApiBundle\Entity\State;
use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Entity\Account\Plan\Plan as AccountPlan;
use SimplyTestable\ApiBundle\Entity\UserAccountPlan;
use SimplyTestable\ApiBundle\Services\UserService;
use SimplyTestable\ApiBundle\Services\StripeService;


class UserAccountPlanService extends EntityService {
    
    const ENTITY_NAME = 'SimplyTestable\ApiBundle\Entity\UserAccountPlan';
    
    /**
     *
     * @var \SimplyTestable\ApiBundle\Services\UserService 
     */
    private $userService;
    
    
    /**
     *
     * @var \SimplyTestable\ApiBundle\Services\StripeService 
     */
    private $stripeService;
    
    /**
     *
     * @param \Doctrine\ORM\EntityManager $entityManager
     * @param \SimplyTestable\ApiBundle\Services\UserService $userService 
     */
    public function __construct(EntityManager $entityManager, UserService $userService, StripeService $stripeService) {
        $this->entityManager = $entityManager;      
        $this->userService = $userService;
        $this->stripeService = $stripeService;
    }     
    
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
    private function create(User $user, AccountPlan $plan) {
        $userAccountPlan = new UserAccountPlan();
        $userAccountPlan->setUser($user);
        $userAccountPlan->setPlan($plan);
        
        return $this->persistAndFlush($userAccountPlan);
    }
    
    
    /**
     * 
     * @param \SimplyTestable\ApiBundle\Entity\User $user
     * @param \SimplyTestable\ApiBundle\Entity\Account\Plan\Plan $newPlan
     * @return UserAccountPlan|false
     */
    public function subscribe(User $user, AccountPlan $newPlan) {
        $existingUserAccountPlan = $this->getForUser($user);
        if (!is_null($existingUserAccountPlan)) {
            $this->getEntityManager()->remove($existingUserAccountPlan);
        }        
        
        return $this->create($user, $newPlan);
    }
    
    
    /**
     * 
     * @param \SimplyTestable\ApiBundle\Entity\User $user
     * @return UserAccountPlan
     */
    public function getForUser(User $user) {
        return $this->getEntityRepository()->findOneByUser($user);
    }
    
    
    /**
     * 
     * @param \SimplyTestable\ApiBundle\Entity\User $user
     * @return boolean
     */
    public function hasForUser(User $user) {
        return !is_null($this->getForUser($user));
    }
    
    
    public function getAll() {
        return $this->getEntityRepository()->findAll();
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
    
    
    /**
     * 
     * @return array
     */
    public function findUsersWithNoPlan() {
        return $this->userService->getEntityRepository()->findAllNotWithIds(array_merge(
           $this->getEntityRepository()->findUserIdsWithPlan(),
           array($this->userService->getAdminUser()->getId())
        ));
    }     
    

}