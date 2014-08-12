<?php
namespace SimplyTestable\ApiBundle\Services;

use Doctrine\ORM\EntityManager;
use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Entity\Account\Plan\Plan as AccountPlan;
use SimplyTestable\ApiBundle\Entity\UserAccountPlan;
use SimplyTestable\ApiBundle\Services\UserService;
use SimplyTestable\ApiBundle\Services\StripeService;
use SimplyTestable\ApiBundle\Services\Team\Service as TeamService;
use SimplyTestable\ApiBundle\Exception\Services\UserAccountPlan\Exception as UserAccountPlanServiceException;


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
     * @var TeamService
     */
    private $teamService;
    
    
    /**
     *
     * @var int 
     */
    private $defaultTrialPeriod = null;


    /**
     * @param EntityManager $entityManager
     * @param UserService $userService
     * @param StripeService $stripeService
     * @param TeamService $teamService
     * @param $defaultTrialPeriod
     */
    public function __construct(
        EntityManager $entityManager,
        UserService $userService,
        StripeService $stripeService,
        TeamService $teamService,
        $defaultTrialPeriod
    ) {
        $this->entityManager = $entityManager;      
        $this->userService = $userService;
        $this->stripeService = $stripeService;
        $this->teamService = $teamService;
        $this->defaultTrialPeriod = $defaultTrialPeriod;
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
    private function create(User $user, AccountPlan $plan, $stripeCustomer = null, $startTrialPeriod = null) {
        $this->deactivateAllForUser($user);        
        
        $userAccountPlan = new UserAccountPlan();
        $userAccountPlan->setUser($user);
        $userAccountPlan->setPlan($plan);
        $userAccountPlan->setStripeCustomer($stripeCustomer);        
        $userAccountPlan->setIsActive(true);
        
        if (is_null($startTrialPeriod)) {
            $startTrialPeriod = $this->defaultTrialPeriod;
        }
        
        $userAccountPlan->setStartTrialPeriod($startTrialPeriod); 
        
        return $this->persistAndFlush($userAccountPlan);
    }


    /**
     * @param User $user
     * @param AccountPlan $newPlan
     * @param string|null $coupon
     * @return UserAccountPlan
     * @throws \SimplyTestable\ApiBundle\Exception\Services\UserAccountPlan\Exception
     */
    public function subscribe(User $user, AccountPlan $newPlan, $coupon = null) {
        if ($this->teamService->getMemberService()->belongsToTeam($user)) {
            throw new UserAccountPlanServiceException(
                '',
                UserAccountPlanServiceException::CODE_USER_IS_TEAM_MEMBER
            );
        }

        if (!$this->hasForUser($user)) {            
            if ($newPlan->getIsPremium()) {
                return $this->stripeService->subscribe($this->create(
                    $user,
                    $newPlan,
                    $this->stripeService->createCustomer($user, $coupon)->getId()
                ));                
            } else {
                return $this->create($user, $newPlan);
            }
        }
        
        $currentUserAccountPlan = $this->getForUser($user);        
        
        if ($this->isSameAccountPlan($currentUserAccountPlan->getPlan(), $newPlan)) {
            return $currentUserAccountPlan;
        }
        
        if ($this->isNonPremiumToNonPremiumChange($currentUserAccountPlan->getPlan(), $newPlan)) {
            return $this->create($user, $newPlan);
        }
        
        $stripeCustomer = $this->stripeService->getCustomer($currentUserAccountPlan);
        $stripeCustomerId = $currentUserAccountPlan->hasStripeCustomer() ? $currentUserAccountPlan->getStripeCustomer() : $this->stripeService->createCustomer($user, $coupon)->getId();
        
        if ($this->isNonPremiumToPremiumChange($currentUserAccountPlan->getPlan(), $newPlan)) {                        
            return $this->stripeService->subscribe($this->create(
                $user,
                $newPlan,
                $stripeCustomerId,
                $currentUserAccountPlan->getStartTrialPeriod()
            ));
        }
        
        if ($this->isPremiumToNonPremiumChange($currentUserAccountPlan->getPlan(), $newPlan)) {            
            $this->stripeService->unsubscribe($currentUserAccountPlan);
            return $this->create(
                $user,
                $newPlan,
                $stripeCustomerId,
                $this->getStartTrialPeriod($stripeCustomer)    
            );
        }        

        return $this->stripeService->subscribe($this->create(
            $user,
            $newPlan,
            $stripeCustomerId,
            $this->getStartTrialPeriod($stripeCustomer) 
        ));
    }
    
    
    /**
     * 
     * @param \webignition\Model\Stripe\Customer $stripeCustomer
     * @return int
     */
    private function getStartTrialPeriod(\webignition\Model\Stripe\Customer $stripeCustomer) {                    
        if (!$stripeCustomer->hasSubscription()) {
            return 0;
        }
        
        $trialEndTimestamp = $stripeCustomer->getSubscription()->getTrialPeriod()->getEnd();        
        $difference = $trialEndTimestamp - time();
        return (int)ceil($difference / 86400);
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
        $targetUser = $this->teamService->getMemberService()->belongsToTeam($user)
            ? $this->teamService->getLeaderFor($user)
            : $user;

        $isActiveValues = array(
            true, false, null
        );
        
        foreach ($isActiveValues as $isActiveValue) {
            $userAccountPlans = $this->getEntityRepository()->findBy(array(
                'user' => $targetUser,
                'isActive' => $isActiveValue                
            ), array(
                'id' => 'DESC'
            ), 1);
            
            if (count($userAccountPlans)) {
                return $userAccountPlans[0];
            }           
        }
    }
    
    
    /**
     * 
     * @param \SimplyTestable\ApiBundle\Entity\User $user
     * @return int
     */
    public function countForUser(User $user) {
        return count($this->getEntityRepository()->findBy(array(
            'user' => $user
        )));
    }
    
    
    /**
     * 
     * @param string $stripeCustomer
     * @return User
     */
    public function getUserByStripeCustomer($stripeCustomer) {
        $userAccountPlan = $this->getEntityRepository()->findOneBy(array(
            'stripeCustomer' => $stripeCustomer
        ));
        
        if (!is_null($userAccountPlan)) {
            return $userAccountPlan->getUser();
        }
    }
    

    /**
     * 
     * @param \SimplyTestable\ApiBundle\Entity\User $user
     */
    public function removeCurrentForUser(User $user) {
        $userAccountPlans = $this->getEntityRepository()->findBy(array(
            'user' => $user,            
        ), array(
            'id' => 'DESC'
        ), 1);
        
        if (count($userAccountPlans) === 1) {        
            $this->getEntityManager()->remove($userAccountPlans[0]);
            $this->getEntityManager()->flush();
        }
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
    
    
    /**
     * 
     * @param \SimplyTestable\ApiBundle\Entity\Account\Plan\Plan $plan
     * @return array
     */
    public function findAllByPlan(AccountPlan $plan) {
        return $this->getEntityRepository()->findBy(array(
            'plan' => $plan            
        ));
    }
    

}