<?php

namespace SimplyTestable\ApiBundle\Controller;

use SimplyTestable\ApiBundle\Services\UserPostActivationPropertiesService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class UserCreationController extends AbstractUserController
{
    const DEFAULT_ACCOUNT_PLAN_NAME = 'basic';

    /**
     * @param Request $request
     *
     * @return Response
     */
    public function createAction(Request $request)
    {
        $applicationStateService = $this->container->get('simplytestable.services.applicationstateservice');
        $userService = $this->container->get('simplytestable.services.userservice');
        $accountPlanService = $this->container->get('simplytestable.services.accountplanservice');
        $userAccountPlanService = $this->container->get('simplytestable.services.useraccountplanservice');
        $userPostActivationPropertiesService = $this->container->get(
            'simplytestable.services.job.userpostactivationpropertiesservice'
        );

        if ($applicationStateService->isInMaintenanceReadOnlyState()) {
            return $this->sendServiceUnavailableResponse();
        }

        if ($applicationStateService->isInMaintenanceBackupReadOnlyState()) {
            return $this->sendServiceUnavailableResponse();
        }

        $requestData = $request->request;

        $email = rawurldecode(trim($requestData->get('email')));

        if (empty($email)) {
            throw new BadRequestHttpException('"email" missing');
        }

        $password = rawurldecode(trim($requestData->get('password')));

        if (empty($password)) {
            throw new BadRequestHttpException('"password" missing');
        }

        $user = $userService->findUserByEmail($email);

        if (empty($user)) {
            $user = $userService->create($email, $password);
        } else {
            if ($user->isEnabled()) {
                return $this->redirect($this->generateUrl('user_get', [
                    'email_canonical' => $email
                ], true));
            }

            $user->setPlainPassword($password);
            $userService->updatePassword($user);
        }

        $coupon = rawurldecode(trim($requestData->get('coupon')));
        if (empty($coupon)) {
            $coupon = null;
        }

        $planName = rawurldecode(trim($requestData->get('plan')));
        if (empty($planName) || !$accountPlanService->has($planName)) {
            $planName = self::DEFAULT_ACCOUNT_PLAN_NAME;
        }

        $plan = $accountPlanService->find($planName);

        if ($plan->getIsPremium()) {
            $userPostActivationPropertiesService->create(
                $user,
                $plan,
                $coupon
            );
        } else {
            $userAccountPlanService->subscribe($user, $plan);
        }

        return $this->sendSuccessResponse();
    }


    public function activateAction($token = null) {
        $applicationStateService = $this->container->get('simplytestable.services.applicationstateservice');
        $userService = $this->container->get('simplytestable.services.userservice');

        if ($applicationStateService->isInMaintenanceReadOnlyState()) {
            return $this->sendServiceUnavailableResponse();
        }

        if ($applicationStateService->isInMaintenanceBackupReadOnlyState()) {
            return $this->sendServiceUnavailableResponse();
        }

        $token = trim($token);
        if ($token == '') {
            throw new \Symfony\Component\HttpKernel\Exception\HttpException(400);
        }

        $user = $userService->findUserByConfirmationToken($token);
        if (is_null($user)) {
            throw new \Symfony\Component\HttpKernel\Exception\HttpException(400);
        }

        $this->getUserManipulator()->activate($user->getUsername());

        if ($this->getUserPostActivationPropertiesService()->hasForUser($user)) {
            $postActivationProperties = $this->getUserPostActivationPropertiesService()->getForUser($user);

            $this->getUserAccountPlanService()->subscribe(
                $user,
                $postActivationProperties->getAccountPlan(),
                $postActivationProperties->getCoupon()
            );

            $this->getUserPostActivationPropertiesService()->getManager()->remove($postActivationProperties);
            $this->getUserPostActivationPropertiesService()->getManager()->flush($postActivationProperties);
        }

        return new Response();
    }

    /**
     *
     * @return \SimplyTestable\ApiBundle\Services\UserAccountPlanService
     */
    private function getUserAccountPlanService() {
        return $this->get('simplytestable.services.useraccountplanservice');
    }



    /**
     * @return UserPostActivationPropertiesService
     */
    protected function getUserPostActivationPropertiesService() {
        return $this->get('simplytestable.services.job.UserPostActivationPropertiesService');
    }

}
