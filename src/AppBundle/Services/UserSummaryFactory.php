<?php

namespace AppBundle\Services;

use AppBundle\Entity\Account\Plan\Plan;
use AppBundle\Entity\User;
use AppBundle\Entity\UserAccountPlan;
use AppBundle\Model\User\Summary\Summary as UserSummary;
use AppBundle\Model\User\Summary\PlanConstraints as PlanConstraintsSummary;
use AppBundle\Model\User\Summary\StripeCustomer as StripeCustomerSummary;
use AppBundle\Model\User\Summary\Team as TeamSummary;
use AppBundle\Services\Team\InviteService;
use AppBundle\Services\Team\Service as TeamService;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class UserSummaryFactory
{
    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var UserAccountPlanService
     */
    private $userAccountPlanService;

    /**
     * @var StripeService
     */
    private $stripeService;

    /**
     * @var JobUserAccountPlanEnforcementService
     */
    private $jobUserAccountPlanEnforcementService;

    /**
     * @var InviteService
     */
    private $teamInviteService;

    /**
     * @var TeamService
     */
    private $teamService;

    /**
     * @param TokenStorageInterface $tokenStorage
     * @param UserAccountPlanService $userAccountPlanService
     * @param StripeService $stripeService
     * @param JobUserAccountPlanEnforcementService $jobUserAccountPlanEnforcementService
     * @param InviteService $teamInviteService
     * @param TeamService $teamService
     */
    public function __construct(
        TokenStorageInterface $tokenStorage,
        UserAccountPlanService $userAccountPlanService,
        StripeService $stripeService,
        JobUserAccountPlanEnforcementService $jobUserAccountPlanEnforcementService,
        InviteService $teamInviteService,
        TeamService $teamService
    ) {
        $this->tokenStorage = $tokenStorage;
        $this->userAccountPlanService = $userAccountPlanService;
        $this->stripeService = $stripeService;
        $this->jobUserAccountPlanEnforcementService = $jobUserAccountPlanEnforcementService;
        $this->teamInviteService = $teamInviteService;
        $this->teamService = $teamService;
    }

    /**
     * @return UserSummary
     */
    public function create()
    {
        $user = $this->tokenStorage->getToken()->getUser();
        $userAccountPlan = $this->userAccountPlanService->getForUser($user);

        $this->jobUserAccountPlanEnforcementService->setUser($user);

        $stripeCustomerSummary = $this->createStripeCustomerSummary($userAccountPlan, $user);
        $planConstraintsSummary = $this->createPlanConstraintsSummary($userAccountPlan);
        $teamSummary = $this->createTeamSummary($userAccountPlan->getPlan(), $user);

        return new UserSummary(
            $user,
            $userAccountPlan,
            $stripeCustomerSummary,
            $planConstraintsSummary,
            $teamSummary
        );
    }

    /**
     * @param UserAccountPlan $userAccountPlan
     * @param User $user
     *
     * @return StripeCustomerSummary
     */
    private function createStripeCustomerSummary(UserAccountPlan $userAccountPlan, User $user)
    {
        $stripeCustomerModel = null;
        $userAccountPlanStripeCustomer = $userAccountPlan->getStripeCustomer();
        $hasStripeCustomer = !empty($userAccountPlanStripeCustomer);

        $shouldFetchStripeCustomer = $hasStripeCustomer && $user->getId() == $userAccountPlan->getUser()->getId();

        if ($shouldFetchStripeCustomer) {
            $stripeCustomerModel = $this->stripeService->getCustomer($userAccountPlan);
        }

        return new StripeCustomerSummary($stripeCustomerModel);
    }

    /**
     * @param UserAccountPlan $userAccountPlan
     *
     * @return PlanConstraintsSummary
     */
    private function createPlanConstraintsSummary(UserAccountPlan $userAccountPlan)
    {
        $creditsUsedThisMonth = 0;
        $plan = $userAccountPlan->getPlan();


        $creditsPerMonthConstraint = $plan->getConstraintNamed('credits_per_month');
        if (!empty($creditsPerMonthConstraint)) {
            $creditsUsedThisMonth = $this->jobUserAccountPlanEnforcementService->getCreditsUsedThisMonth();
        }

        return new PlanConstraintsSummary($userAccountPlan, $creditsUsedThisMonth);
    }

    /**
     * @param Plan $plan
     * @param User $user
     *
     * @return TeamSummary
     */
    private function createTeamSummary(Plan $plan, User $user)
    {
        $userIsInTeam = $this->teamService->hasForUser($user);
        $hasInvite = !$plan->getIsPremium() && $this->teamInviteService->hasAnyForUser($user);

        return new TeamSummary($userIsInTeam, $hasInvite);
    }
}
