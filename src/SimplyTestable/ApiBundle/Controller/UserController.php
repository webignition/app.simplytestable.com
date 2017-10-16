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
        return $this->sendResponse($this->getUserSummary($this->getUser()));
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
     * @return array
     */
    private function getUserSummary(User $user)
    {
        $userAccountPlan = $this->getUserAccountPlanService()->getForUser($this->getUser());
        if (is_null($userAccountPlan)) {
            $userAccountPlan = $this->getUserAccountPlanService()->subscribe(
                $user,
                $this->getAccountPlanService()->find('basic')
            );
        }

        $userSummary = array(
            'email' => $user->getEmailCanonical(),
            'user_plan' => $userAccountPlan
        );

        if ($this->includeStripeCustomerInUserSummary($user, $userAccountPlan)) {
            $userSummary['stripe_customer'] = $this->getStripeService()->getCustomer($userAccountPlan)->__toArray();
        }

        $planConstraints = array();

        if ($userAccountPlan->getPlan()->hasConstraintNamed('credits_per_month')) {
            $this->getJobUserAccountPlanEnforcementService()->setUser($this->getUser());
            $planConstraints['credits'] = array(
                'limit' => $userAccountPlan->getPlan()->getConstraintNamed('credits_per_month')->getLimit(),
                'used' => $this->getJobUserAccountPlanEnforcementService()->getCreditsUsedThisMonth()
            );
        }

        if ($userAccountPlan->getPlan()->hasConstraintNamed('urls_per_job')) {
            $planConstraints['urls_per_job'] =
                $userAccountPlan->getPlan()->getConstraintNamed('urls_per_job')->getLimit();
        }

        $userSummary['plan_constraints'] = $planConstraints;

        $userSummary['team_summary'] = [
            'in' => $this->getTeamService()->hasForUser($this->getUser()),
            'has_invite' => $this->getHasAnyTeamInvitesForUser($this->getUser())
        ];

        return $userSummary;
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
     * @param User $user
     * @param UserAccountPlan $userAccountPlan
     *
     * @return bool
     */
    private function includeStripeCustomerInUserSummary(User $user, UserAccountPlan $userAccountPlan)
    {
        return $userAccountPlan->hasStripeCustomer() &&  $user->getId() == $userAccountPlan->getUser()->getId();
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
     * @return StripeService
     */
    private function getStripeService()
    {
        return $this->get('simplytestable.services.stripeservice');
    }

    /**
     * @return AccountPlanService
     */
    private function getAccountPlanService()
    {
        return $this->get('simplytestable.services.accountplanservice');
    }

    /**
     * @return UserAccountPlanService
     */
    private function getUserAccountPlanService()
    {
        return $this->get('simplytestable.services.useraccountplanservice');
    }

    /**
     * @return JobUserAccountPlanEnforcementService
     */
    private function getJobUserAccountPlanEnforcementService() {
        return $this->get('simplytestable.services.jobuseraccountplanenforcementservice');
    }

    /**
     * @return Service
     */
    private function getTeamService()
    {
        return $this->get('simplytestable.services.teamservice');
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
