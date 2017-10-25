<?php

namespace SimplyTestable\ApiBundle\Controller;

use SimplyTestable\ApiBundle\Services\Team\InviteService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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
        $teamInviteService = $this->container->get('simplytestable.services.teaminviteservice');

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

        $userAccountPlanStripeCustomer = $userAccountPlan->getStripeCustomer();

        $includeStripeCustomerInSummary =
            !empty($userAccountPlanStripeCustomer) && $user->getId() == $userAccountPlan->getUser()->getId();

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

        $hasTeamInvite = !$plan->getIsPremium() && $teamInviteService->hasAnyForUser($user);

        $userSummary['team_summary'] = [
            'in' => $teamService->hasForUser($this->getUser()),
            'has_invite' => $hasTeamInvite
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
     * @param string $email_canonical
     *
     * @return Response
     */
    public function getTokenAction($email_canonical)
    {
        $userService = $this->container->get('simplytestable.services.userservice');

        $user = $userService->findUserByEmail($email_canonical);
        if (empty($user)) {
            throw new NotFoundHttpException();
        }

        $token = $userService->getConfirmationToken($user);

        return new JsonResponse($token);
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
            throw new NotFoundHttpException();
        }

        if ($user->isEnabled() === false) {
            throw new NotFoundHttpException();
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

        throw new NotFoundHttpException(404);
    }

    /**
     * @param $email_canonical
     *
     * @return Response
     */
    public function hasInvitesAction($email_canonical)
    {
        $teamInviteService = $this->container->get('simplytestable.services.teaminviteservice');
        $userService = $this->container->get('simplytestable.services.userservice');

        $user = $userService->findUserByEmail($email_canonical);

        if (empty($user)) {
            throw new NotFoundHttpException(404);
        }

        if ($teamInviteService->hasAnyForUser($user)) {
            return new Response('', 200);
        }

        throw new NotFoundHttpException(404);
    }
}
