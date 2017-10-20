<?php

namespace SimplyTestable\ApiBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class UserPasswordResetController extends UserController
{
    /**
     * @param Request $request
     * @param string $token
     *
     * @return Response
     */
    public function resetPasswordAction(Request $request, $token)
    {
        $applicationStateService = $this->container->get('simplytestable.services.applicationstateservice');
        $userManipulator = $this->container->get('fos_user.util.user_manipulator');
        $userManager = $this->container->get('fos_user.user_manager');

        if ($applicationStateService->isInMaintenanceReadOnlyState()) {
            return $this->sendServiceUnavailableResponse();
        }

        if ($applicationStateService->isInMaintenanceBackupReadOnlyState()) {
            return $this->sendServiceUnavailableResponse();
        }

        $user = $userManager->findUserByConfirmationToken($token);

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

        $userManager->updateUser($user);

        return $this->sendSuccessResponse();
    }
}
