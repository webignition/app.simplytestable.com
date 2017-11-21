<?php

namespace SimplyTestable\ApiBundle\Controller;

use FOS\UserBundle\Util\UserManipulator;
use SimplyTestable\ApiBundle\Services\ApplicationStateService;
use SimplyTestable\ApiBundle\Services\UserService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

class UserPasswordResetController
{
    /**
     * @var ApplicationStateService
     */
    private $applicationStateService;

    /**
     * @var UserManipulator
     */
    private $userManipulator;

    /**
     * @var UserService
     */
    private $userService;

    /**
     * @param ApplicationStateService $applicationStateService
     * @param UserService $userService
     * @param UserManipulator $userManipulator
     */
    public function __construct(
        ApplicationStateService $applicationStateService,
        UserService $userService,
        UserManipulator $userManipulator
    ) {
        $this->applicationStateService = $applicationStateService;
        $this->userService = $userService;
        $this->userManipulator = $userManipulator;
    }

    /**
     * @param Request $request
     * @param string $token
     *
     * @return Response
     */
    public function resetPasswordAction(Request $request, $token)
    {
        if ($this->applicationStateService->isInReadOnlyMode()) {
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
            $this->userManipulator->activate($user->getUsername());
        }

        $user->setPlainPassword($password);
        $user->setConfirmationToken(null);
        $user->setPasswordRequestedAt(null);

        $this->userService->updateUser($user);

        return new Response();
    }
}
