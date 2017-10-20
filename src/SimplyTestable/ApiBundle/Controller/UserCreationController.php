<?php

namespace SimplyTestable\ApiBundle\Controller;

use SimplyTestable\ApiBundle\Entity\UserPostActivationProperties;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class UserCreationController extends ApiController
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
        $userManager = $this->container->get('fos_user.user_manager');

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

        $user = $userManager->findUserByEmail($email);

        if (empty($user)) {
            $user = $userService->create($email, $password);
        } else {
            if ($user->isEnabled()) {
                return $this->redirect($this->generateUrl('user_get', [
                    'email_canonical' => $email
                ], true));
            }

            $user->setPlainPassword($password);
            $userManager->updatePassword($user);
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

    /**
     * @param string $token
     *
     * @return Response
     */
    public function activateAction($token = null)
    {
        $applicationStateService = $this->container->get('simplytestable.services.applicationstateservice');
        $userService = $this->container->get('simplytestable.services.userservice');
        $userAccountPlanService = $this->container->get('simplytestable.services.useraccountplanservice');
        $entityManager = $this->container->get('doctrine.orm.entity_manager');
        $userManipulator = $this->container->get('fos_user.util.user_manipulator');
        $userManager = $this->container->get('fos_user.user_manager');

        if ($applicationStateService->isInMaintenanceReadOnlyState()) {
            return $this->sendServiceUnavailableResponse();
        }

        if ($applicationStateService->isInMaintenanceBackupReadOnlyState()) {
            return $this->sendServiceUnavailableResponse();
        }

        $token = trim($token);
        if (empty($token)) {
            throw new BadRequestHttpException();
        }

        $user = $userManager->findUserByConfirmationToken($token);
        if (empty($user)) {
            throw new BadRequestHttpException();
        }

        $userManipulator->activate($user->getUsername());

        $userPostActivationPropertiesRepository = $entityManager->getRepository(UserPostActivationProperties::class);
        $postActivationProperties = $userPostActivationPropertiesRepository->findOneBy([
            'user' => $user,
        ]);

        if (!empty($postActivationProperties)) {
            $userAccountPlanService->subscribe(
                $user,
                $postActivationProperties->getAccountPlan(),
                $postActivationProperties->getCoupon()
            );

            $entityManager->remove($postActivationProperties);
            $entityManager->flush($postActivationProperties);
        }

        return new Response();
    }
}
