<?php

namespace App\Controller;

use FOS\UserBundle\Util\UserManipulator;
use App\Entity\User;
use App\Services\AccountPlanService;
use App\Services\ApplicationStateService;
use App\Services\Team\InviteService;
use App\Services\UserAccountPlanService;
use App\Services\UserService;
use App\Services\UserSummaryFactory;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
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

    /**
     * @param ApplicationStateService $applicationStateService
     * @param UserManipulator $userManipulator
     * @param Request $request
     * @param string $token
     *
     * @return Response
     */
    public function resetPasswordAction(
        ApplicationStateService $applicationStateService,
        UserManipulator $userManipulator,
        Request $request,
        $token
    ) {
        if ($applicationStateService->isInReadOnlyMode()) {
            throw new ServiceUnavailableHttpException();
        }

        $user = $this->userService->findUserByConfirmationToken($token);
        if (empty($user)) {
            throw new NotFoundHttpException();
        }

        $requestData = $request->request;

        $password = rawurldecode(trim($requestData->get('password')));

        if (empty($password)) {
            throw new BadRequestHttpException('"password" missing');
        }

        if (!$user->isEnabled()) {
            $userManipulator->activate($user->getUsername());
        }

        $user->setPlainPassword($password);
        $user->setConfirmationToken(null);
        $user->setPasswordRequestedAt(null);

        $this->userService->updateUser($user);

        return new Response();
    }
}
