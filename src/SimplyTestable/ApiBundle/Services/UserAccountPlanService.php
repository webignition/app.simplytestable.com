<?php
namespace SimplyTestable\ApiBundle\Services;

use Doctrine\ORM\EntityManager;
use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Entity\Account\Plan\Plan as AccountPlan;
use SimplyTestable\ApiBundle\Entity\UserAccountPlan;
use SimplyTestable\ApiBundle\Repository\UserRepository;
use SimplyTestable\ApiBundle\Services\Team\Service as TeamService;
use SimplyTestable\ApiBundle\Exception\Services\UserAccountPlan\Exception as UserAccountPlanServiceException;
use webignition\Model\Stripe\Customer as StripeCustomerModel;

class UserAccountPlanService extends EntityService
{
    /**
     * @var UserService
     */
    private $userService;

    /**
     * @var StripeService
     */
    private $stripeService;

    /**
     * @var TeamService
     */
    private $teamService;

    /**
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
        parent::__construct($entityManager);

        $this->userService = $userService;
        $this->stripeService = $stripeService;
        $this->teamService = $teamService;
        $this->defaultTrialPeriod = $defaultTrialPeriod;
    }

    /**
     * @return string
     */
    protected function getEntityName()
    {
        return UserAccountPlan::class;
    }

    /**
     * @param User $user
     * @param AccountPlan $plan
     * @param string $stripeCustomer
     * @param int $startTrialPeriod
     *
     * @return UserAccountPlan
     */
    private function create(User $user, AccountPlan $plan, $stripeCustomer = null, $startTrialPeriod = null)
    {
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
     *
     * @return UserAccountPlan
     * @throws UserAccountPlanServiceException
     */
    public function subscribe(User $user, AccountPlan $newPlan, $coupon = null)
    {
        if ($this->teamService->getMemberService()->belongsToTeam($user)) {
            throw new UserAccountPlanServiceException(
                '',
                UserAccountPlanServiceException::CODE_USER_IS_TEAM_MEMBER
            );
        }

        if (!$this->hasForUser($user)) {
            if ($newPlan->getIsPremium()) {
                $stripeCustomer = $this->stripeService->createCustomer($user, $coupon);

                $userAccountPlan = $this->create(
                    $user,
                    $newPlan,
                    $stripeCustomer->getId()
                );

                $this->stripeService->subscribe($userAccountPlan);

                return $userAccountPlan;
            } else {
                return $this->create($user, $newPlan);
            }
        }

        $currentUserAccountPlan = $this->getForUser($user);
        $currentPlan = $currentUserAccountPlan->getPlan();

        $isNewPlanCurrentPlan = $currentPlan->getName() === $newPlan->getName();
        if ($isNewPlanCurrentPlan) {
            return $currentUserAccountPlan;
        }

        $isNonPremiumToNonPremiumChange = !$currentPlan->getIsPremium() && !$newPlan->getIsPremium();
        if ($isNonPremiumToNonPremiumChange) {
            return $this->create($user, $newPlan);
        }

        if (!$currentUserAccountPlan->hasStripeCustomer()) {
            $stripeCustomerModel = $this->stripeService->createCustomer($user, $coupon);
            $currentUserAccountPlan->setStripeCustomer($stripeCustomerModel->getId());
        }

        $isNonPremiumToPremiumChange = !$currentPlan->getIsPremium() && $newPlan->getIsPremium();
        if ($isNonPremiumToPremiumChange) {
            $userAccountPlan = $this->create(
                $user,
                $newPlan,
                $currentUserAccountPlan->getStripeCustomer(),
                $currentUserAccountPlan->getStartTrialPeriod()
            );

            $this->stripeService->subscribe($userAccountPlan);

            return $userAccountPlan;
        }

        $stripeCustomerModel = $this->stripeService->getCustomer($currentUserAccountPlan);

        $isPremiumToNonPremiumChange = $currentPlan->getIsPremium() && !$newPlan->getIsPremium();
        if ($isPremiumToNonPremiumChange) {
            $this->stripeService->unsubscribe($currentUserAccountPlan);

            return $this->create(
                $user,
                $newPlan,
                $stripeCustomerModel->getId(),
                $this->getStartTrialPeriod($stripeCustomerModel)
            );
        }

        $userAccountPlan = $this->create(
            $user,
            $newPlan,
            $stripeCustomerModel->getId(),
            $this->getStartTrialPeriod($stripeCustomerModel)
        );

        $this->stripeService->subscribe($userAccountPlan);

        return $userAccountPlan;
    }

    /**
     * @param StripeCustomerModel $stripeCustomer
     *
     * @return int
     */
    private function getStartTrialPeriod(StripeCustomerModel $stripeCustomer)
    {
        $trialEndTimestamp = $stripeCustomer->getSubscription()->getTrialPeriod()->getEnd();
        $difference = $trialEndTimestamp - time();

        return (int)ceil($difference / 86400);
    }

    /**
     * @param User $user
     *
     * @return UserAccountPlan
     */
    public function getForUser(User $user)
    {
        $targetUser = $this->teamService->getMemberService()->belongsToTeam($user)
            ? $this->teamService->getLeaderFor($user)
            : $user;

        $isActiveValues = [
            true, false, null
        ];

        foreach ($isActiveValues as $isActiveValue) {
            $userAccountPlans = $this->getEntityRepository()->findBy(array(
                'user' => $targetUser,
                'isActive' => $isActiveValue
            ), [
                'id' => 'DESC'
            ], 1);

            if (count($userAccountPlans)) {
                return $userAccountPlans[0];
            }
        }
    }

    /**
     * @param User $user
     */
    public function removeCurrentForUser(User $user)
    {
        $userAccountPlan = $this->getEntityRepository()->findOneBy([
            'user' => $user,
        ], [
            'id' => 'DESC'
        ]);

        if (!empty($userAccountPlan)) {
            $this->getManager()->remove($userAccountPlan);
            $this->getManager()->flush();
        }
    }

    /**
     * @param User $user
     */
    public function deactivateAllForUser(User $user)
    {
        $userAccountPlans = $this->getEntityRepository()->findBy([
            'user' => $user
        ]);

        foreach ($userAccountPlans as $userAccountPlan) {
            /* @var $userAccountPlan UserAccountPlan */
            $userAccountPlan->setIsActive(false);
            $this->getManager()->persist($userAccountPlan);
        }

        if (count($userAccountPlans)) {
            $this->getManager()->flush();
        }
    }

    /**
     * @param User $user
     * @return boolean
     */
    public function hasForUser(User $user)
    {
        return !is_null($this->getForUser($user));
    }

    /**
     * @param UserAccountPlan $userAccountPlan
     *
     * @return UserAccountPlan
     */
    private function persistAndFlush(UserAccountPlan $userAccountPlan)
    {
        $this->getManager()->persist($userAccountPlan);
        $this->getManager()->flush();

        return $userAccountPlan;
    }

    /**
     * @return array
     */
    public function findUsersWithNoPlan()
    {
        /* @var UserRepository $userRepository */
        $userRepository = $this->entityManager->getRepository(User::class);

        return $userRepository->findAllNotWithIds(array_merge(
            $this->getEntityRepository()->findUserIdsWithPlan(),
            array($this->userService->getAdminUser()->getId())
        ));
    }

    /**
     * @param AccountPlan $plan
     *
     * @return UserAccountPlan[]
     */
    public function findAllByPlan(AccountPlan $plan)
    {
        return $this->getEntityRepository()->findBy(array(
            'plan' => $plan
        ));
    }
}
