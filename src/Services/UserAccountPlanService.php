<?php

namespace App\Services;

use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;
use App\Entity\Account\Plan\Plan as AccountPlan;
use App\Entity\UserAccountPlan;
use App\Repository\UserAccountPlanRepository;
use App\Repository\UserRepository;
use App\Services\Team\Service as TeamService;
use App\Exception\Services\UserAccountPlan\Exception as UserAccountPlanServiceException;
use webignition\Model\Stripe\Customer as StripeCustomerModel;

class UserAccountPlanService
{
    private $userService;
    private $stripeService;
    private $teamService;
    private $userRepository;
    private $entityManager;
    private $userAccountPlanRepository;

    /**
     * @var int
     */
    private $defaultTrialPeriod = null;

    public function __construct(
        EntityManagerInterface $entityManager,
        UserService $userService,
        StripeService $stripeService,
        TeamService $teamService,
        UserRepository $userRepository,
        UserAccountPlanRepository $userAccountPlanRepository,
        $defaultTrialPeriod
    ) {
        $this->entityManager = $entityManager;
        $this->userService = $userService;
        $this->stripeService = $stripeService;
        $this->teamService = $teamService;
        $this->defaultTrialPeriod = $defaultTrialPeriod;
        $this->userRepository = $userRepository;
        $this->userAccountPlanRepository = $userAccountPlanRepository;
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

        $this->entityManager->persist($userAccountPlan);
        $this->entityManager->flush();

        return $userAccountPlan;
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

        $currentUserAccountPlan = $this->getForUser($user);

        if (empty($currentUserAccountPlan)) {
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

        $currentPlan = $currentUserAccountPlan->getPlan();

        $isNewPlanCurrentPlan = $currentPlan->getName() === $newPlan->getName();
        if ($isNewPlanCurrentPlan) {
            return $currentUserAccountPlan;
        }

        $isNonPremiumToNonPremiumChange = !$currentPlan->getIsPremium() && !$newPlan->getIsPremium();
        if ($isNonPremiumToNonPremiumChange) {
            return $this->create($user, $newPlan);
        }

        if (empty($currentUserAccountPlan->getStripeCustomer())) {
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
            $userAccountPlans = $this->userAccountPlanRepository->findBy([
                'user' => $targetUser,
                'isActive' => $isActiveValue
            ], [
                'id' => 'DESC'
            ], 1);

            if (count($userAccountPlans)) {
                return $userAccountPlans[0];
            }
        }

        return null;
    }

    /**
     * @param User $user
     */
    public function removeCurrentForUser(User $user)
    {
        $userAccountPlan = $this->userAccountPlanRepository->findOneBy([
            'user' => $user,
        ], [
            'id' => 'DESC'
        ]);

        if (!empty($userAccountPlan)) {
            $this->entityManager->remove($userAccountPlan);
            $this->entityManager->flush();
        }
    }

    /**
     * @param User $user
     */
    public function deactivateAllForUser(User $user)
    {
        $userAccountPlans = $this->userAccountPlanRepository->findBy([
            'user' => $user
        ]);

        foreach ($userAccountPlans as $userAccountPlan) {
            /* @var $userAccountPlan UserAccountPlan */
            $userAccountPlan->setIsActive(false);
            $this->entityManager->persist($userAccountPlan);
        }

        if (count($userAccountPlans)) {
            $this->entityManager->flush();
        }
    }

    /**
     * @param AccountPlan $plan
     *
     * @return UserAccountPlan[]
     */
    public function findAllByPlan(AccountPlan $plan)
    {
        return $this->userAccountPlanRepository->findBy([
            'plan' => $plan
        ]);
    }
}
