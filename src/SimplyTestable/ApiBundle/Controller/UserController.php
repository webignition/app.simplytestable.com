<?php

namespace SimplyTestable\ApiBundle\Controller;

use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Services\AccountPlanService;
use SimplyTestable\ApiBundle\Services\Team\InviteService;
use SimplyTestable\ApiBundle\Services\UserAccountPlanService;
use SimplyTestable\ApiBundle\Services\UserService;
use SimplyTestable\ApiBundle\Services\UserSummaryFactory;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\User\UserInterface;

class UserController
{
    /**
     * @var UserService
     */
    private $userService;

    /**
     * @param UserService $userService
     */
    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * @param UserAccountPlanService $userAccountPlanService
     * @param AccountPlanService $accountPlanService
     * @param UserSummaryFactory $userSummaryFactory
     * @param UserInterface|User $user
     *
     * @return JsonResponse
     */
    public function getAction(
        UserAccountPlanService $userAccountPlanService,
        AccountPlanService $accountPlanService,
        UserSummaryFactory $userSummaryFactory,
        UserInterface $user
    ) {
        $userAccountPlan = $userAccountPlanService->getForUser($user);
        if (empty($userAccountPlan)) {
            $basicPlan = $accountPlanService->getBasicPlan();

            $userAccountPlanService->subscribe(
                $user,
                $basicPlan
            );
        }

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
        $user = $this->userService->findUserByEmail($email_canonical);
        if (empty($user)) {
            throw new NotFoundHttpException();
        }

        $token = $this->userService->getConfirmationToken($user);

        return new JsonResponse($token);
    }

    /**
     * @param string $email_canonical
     *
     * @return Response
     */
    public function isEnabledAction($email_canonical)
    {
        $user = $this->userService->findUserByEmail($email_canonical);

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
        if ($this->userService->exists($email_canonical)) {
            return new Response('', 200);
        }

        throw new NotFoundHttpException(404);
    }

    /**
     * @param InviteService $teamInviteService
     * @param string $email_canonical
     *
     * @return Response
     */
    public function hasInvitesAction(InviteService $teamInviteService, $email_canonical)
    {
        $user = $this->userService->findUserByEmail($email_canonical);

        if (empty($user)) {
            throw new NotFoundHttpException(404);
        }

        if ($teamInviteService->hasAnyForUser($user)) {
            return new Response('', 200);
        }

        throw new NotFoundHttpException(404);
    }
}
