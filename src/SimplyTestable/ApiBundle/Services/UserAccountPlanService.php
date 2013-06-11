<?php
namespace SimplyTestable\ApiBundle\Services;

use Doctrine\ORM\EntityManager;
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
    private function create(User $user, AccountPlan $plan, $stripeCustomer = null) {
        $this->deactivateAllForUser($user);
        
        $userAccountPlan = new UserAccountPlan();
        $userAccountPlan->setUser($user);
        $userAccountPlan->setPlan($plan);
        $userAccountPlan->setStripeCustomer($stripeCustomer);        
        $userAccountPlan->setIsActive(true);
        
        return $this->persistAndFlush($userAccountPlan);
    }
    
    
    /**
     * 
     * @param \SimplyTestable\ApiBundle\Entity\User $user
     * @param \SimplyTestable\ApiBundle\Entity\Account\Plan\Plan $newPlan
     * @return UserAccountPlan|false
     */
    public function subscribe(User $user, AccountPlan $newPlan) {
        if (!$this->hasForUser($user)) {
            return $this->create($user, $newPlan);
        }
        
        $currentUserAccountPlan = $this->getForUser($user);
        
        if ($this->isSameAccountPlan($currentUserAccountPlan->getPlan(), $newPlan)) {
            return $currentUserAccountPlan;
        }
        
        if ($this->isNonPremiumToNonPremiumChange($currentUserAccountPlan->getPlan(), $newPlan)) {
            return $this->create($user, $newPlan);
        }
        
        if ($this->isNonPremiumToPremiumChange($currentUserAccountPlan->getPlan(), $newPlan)) {
            $stripeCustomer = $this->stripeService->createCustomer($user);
            return $this->stripeService->subscribe($this->create($user, $newPlan, $stripeCustomer));
        }
        
        if ($this->isPremiumToNonPremiumChange($currentUserAccountPlan->getPlan(), $newPlan)) {
            $stripeCustomer = $currentUserAccountPlan->getStripeCustomer();
            $this->stripeService->unsubscribe($currentUserAccountPlan);
            return $this->create($user, $newPlan);
        }
        
        var_dump("cp02");
        exit();
        
        
//        else {
//            $stripeCustomer = $currentUserAccountPlan->getStripeCustomer();
//        }

        
    }
    
    
    /**
     * 
     * @param \SimplyTestable\ApiBundle\Entity\Account\Plan\Plan $currentPlan
     * @param \SimplyTestable\ApiBundle\Entity\Account\Plan\Plan $newPlan
     * @return boolean
     */
    private function isSameAccountPlan(AccountPlan $currentPlan, AccountPlan $newPlan) {
        return $currentPlan->getName() == $newPlan->getName();
    }
    
    
    /**
     * 
     * @param \SimplyTestable\ApiBundle\Entity\Account\Plan\Plan $currentPlan
     * @param \SimplyTestable\ApiBundle\Entity\Account\Plan\Plan $newPlan
     * @return boolean
     */
    private function isNonPremiumToPremiumChange(AccountPlan $currentPlan, AccountPlan $newPlan) {
        return $currentPlan->getIsPremium() == false && $newPlan->getIsPremium() === true;
    }
    
    
    /**
     * 
     * @param \SimplyTestable\ApiBundle\Entity\Account\Plan\Plan $currentPlan
     * @param \SimplyTestable\ApiBundle\Entity\Account\Plan\Plan $newPlan
     * @return boolean
     */
    private function isPremiumToPremiumChange(AccountPlan $currentPlan, AccountPlan $newPlan) {
        return $currentPlan->getIsPremium() === true && $newPlan->getIsPremium() === true;
    }    
    
    
    /**
     * 
     * @param \SimplyTestable\ApiBundle\Entity\Account\Plan\Plan $currentPlan
     * @param \SimplyTestable\ApiBundle\Entity\Account\Plan\Plan $newPlan
     * @return boolean
     */
    private function isPremiumToNonPremiumChange(AccountPlan $currentPlan, AccountPlan $newPlan) {
        return $currentPlan->getIsPremium() === true && $newPlan->getIsPremium() == false;
    }     
    
    
    /**
     * 
     * @param \SimplyTestable\ApiBundle\Entity\Account\Plan\Plan $currentPlan
     * @param \SimplyTestable\ApiBundle\Entity\Account\Plan\Plan $newPlan
     * @return boolean
     */
    private function isNonPremiumToNonPremiumChange(AccountPlan $currentPlan, AccountPlan $newPlan) {
        return $currentPlan->getIsPremium() == false && $newPlan->getIsPremium() == false;
    }
    
    
    /**
     * 
     * @param \SimplyTestable\ApiBundle\Entity\User $user
     * @return UserAccountPlan
     */
    public function getForUser(User $user) {
        return $this->getEntityRepository()->findOneBy(array(
            'user' => $user,
            'isActive' => true
        ));
    }
    
    
    
    /**
     * 
     * @param \SimplyTestable\ApiBundle\Entity\User $user
     */
    public function deactivateAllForUser(User $user) {
        $userAccountPlans = $this->getEntityRepository()->findBy(array(
            'user' => $user
        ));
        
        foreach ($userAccountPlans as $userAccountPlan) {
            /* @var $userAccountPlan UserAccountPlan */
            $userAccountPlan->setIsActive(false);
            $this->getEntityManager()->persist($userAccountPlan);
        }
        
        if (count($userAccountPlans)) {
            $this->getEntityManager()->flush();
        }
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