<?php

namespace SimplyTestable\ApiBundle\Controller;

use SimplyTestable\ApiBundle\Services\AccountPlanService;
use SimplyTestable\ApiBundle\Services\Team\InviteService;
use SimplyTestable\ApiBundle\Services\UserAccountPlanService;
use SimplyTestable\ApiBundle\Services\UserService;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class UserController extends Controller
{
    /**
     * @return JsonResponse
     */
    public function getAction()
    {
        $userAccountPlanService = $this->container->get(UserAccountPlanService::class);
        $accountPlanService = $this->container->get(AccountPlanService::class);

        $user = $this->getUser();

        $userAccountPlan = $userAccountPlanService->getForUser($user);
        if (empty($userAccountPlan)) {
            $basicPlan = $accountPlanService->getBasicPlan();

            $userAccountPlanService->subscribe(
                $user,
                $basicPlan
            );
        }

        $userSummaryFactory = $this->container->get('simplytestable.services.usersummaryfactory');

        return new JsonResponse($userSummaryFactory->create());
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
        $userService = $this->container->get(UserService::class);

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
        $userService = $this->container->get(UserService::class);
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
        $userService = $this->container->get(UserService::class);
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
        $teamInviteService = $this->container->get(InviteService::class);
        $userService = $this->container->get(UserService::class);

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
