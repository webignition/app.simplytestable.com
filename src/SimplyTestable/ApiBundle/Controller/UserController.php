<?php

namespace SimplyTestable\ApiBundle\Controller;

use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Services\AccountPlanService;
use SimplyTestable\ApiBundle\Services\JobUserAccountPlanEnforcementService;
use SimplyTestable\ApiBundle\Services\StripeService;
use SimplyTestable\ApiBundle\Services\Team\InviteService;
use SimplyTestable\ApiBundle\Services\Team\Service;
use SimplyTestable\ApiBundle\Services\UserAccountPlanService;
use Symfony\Component\HttpFoundation\Response;
use SimplyTestable\ApiBundle\Entity\UserAccountPlan;
use Symfony\Component\HttpKernel\Exception\HttpException;

class UserController extends ApiController
{
    /**
     * @return Response
     */
    public function getAction()
    {
        $userAccountPlanService = $this->container->get('simplytestable.services.useraccountplanservice');
        $accountPlanService = $this->container->get('simplytestable.services.accountplanservice');
        $stripeService = $this->container->get('simplytestable.services.stripeservice');
        $jobUserAccountPlanEnforcementService = $this->container->get(
            'simplytestable.services.jobuseraccountplanenforcementservice'
        );
        $teamService = $this->container->get('simplytestable.services.teamservice');

        $user = $this->getUser();

        $userAccountPlan = $userAccountPlanService->getForUser($this->getUser());
        if (empty($userAccountPlan)) {
            $basicPlan = $accountPlanService->find('basic');

            $userAccountPlan = $userAccountPlanService->subscribe(
                $user,
                $basicPlan
            );
        }

        $userSummary = [
            'email' => $user->getEmailCanonical(),
            'user_plan' => $userAccountPlan
        ];

        $includeStripeCustomerInSummary =
            $userAccountPlan->hasStripeCustomer() && $user->getId() == $userAccountPlan->getUser()->getId();

        if ($includeStripeCustomerInSummary) {
            $userSummary['stripe_customer'] = $stripeService->getCustomer($userAccountPlan)->__toArray();
        }

        $planConstraints = [];

        $plan = $userAccountPlan->getPlan();

        $creditsPerMonthConstraint = $plan->getConstraintNamed('credits_per_month');
        if (!empty($creditsPerMonthConstraint)) {
            $jobUserAccountPlanEnforcementService->setUser($this->getUser());
            $planConstraints['credits'] = [
                'limit' => $plan->getConstraintNamed('credits_per_month')->getLimit(),
                'used' => $jobUserAccountPlanEnforcementService->getCreditsUsedThisMonth()
            ];
        }

        $urlsPerJobConstraint = $userAccountPlan->getPlan()->getConstraintNamed('urls_per_job');
        if (!empty($urlsPerJobConstraint)) {
            $planConstraints['urls_per_job'] = $urlsPerJobConstraint->getLimit();
        }

        $userSummary['plan_constraints'] = $planConstraints;

        $userSummary['team_summary'] = [
            'in' => $teamService->hasForUser($this->getUser()),
            'has_invite' => $this->getHasAnyTeamInvitesForUser($this->getUser())
        ];

        return $this->sendResponse($userSummary);
    }

    /**
     * @return Response
     */
    public function authenticateAction()
    {
        return new Response('');
    }

    /**
     * @param User $user
     *
     * @return bool
     */
    private function getHasAnyTeamInvitesForUser(User $user)
    {
        $userAccountPlan = $this->getUserAccountPlanService()->getForUser($this->getUser());

        if ($userAccountPlan->getPlan()->getIsPremium()) {
            return false;
        }

        return $this->getTeamInviteService()->hasAnyForUser($this->getUser());
    }

    /**
     *
     * @param string $email_canonical
     *
     * @return Response
     * @throws HttpException
     */
    public function getTokenAction($email_canonical)
    {
        $userService = $this->container->get('simplytestable.services.userservice');
        $user = $userService->findUserByEmail($email_canonical);
        if (is_null($user)) {
            throw new HttpException(404);
        }

        $token = $userService->getConfirmationToken($user);

        return $this->sendResponse($token);
    }

    /**
     * @param string $email_canonical
     *
     * @return Response
     */
    public function isEnabledAction($email_canonical)
    {
        $userService = $this->container->get('simplytestable.services.userservice');
        $user = $userService->findUserByEmail($email_canonical);

        if (is_null($user)) {
            throw new HttpException(404);
        }

        if ($user->isEnabled() === false) {
            throw new HttpException(404);
        }

        return new Response('', 200);
    }

    /**
     * @param string $email_canonical
     *
     * @return Response
     */
    public function existsAction($email_canonical)
    {
        $userService = $this->container->get('simplytestable.services.userservice');
        if ($userService->exists($email_canonical)) {
            return new Response('', 200);
        }

        throw new HttpException(404);
    }

    /**
     * @return UserAccountPlanService
     */
    private function getUserAccountPlanService()
    {
        return $this->get('simplytestable.services.useraccountplanservice');
    }

    /**
     * @return InviteService
     */
    private function getTeamInviteService()
    {
        return $this->get('simplytestable.services.teaminviteservice');
    }

    /**
     * @param $email_canonical
     *
     * @return Response
     * @throws HttpException
     */
    public function hasInvitesAction($email_canonical)
    {
        $userService = $this->container->get('simplytestable.services.userservice');

        if (!$userService->exists($email_canonical)) {
            throw new HttpException(404);
        }

        if ($this->getTeamInviteService()->hasAnyForUser($userService->findUserByEmail($email_canonical))) {
            return new Response('', 200);
        }

        throw new HttpException(404);
    }
}
