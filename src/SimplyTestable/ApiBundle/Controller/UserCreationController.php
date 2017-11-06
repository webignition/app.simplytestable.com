<?php

namespace SimplyTestable\ApiBundle\Controller;

use SimplyTestable\ApiBundle\Entity\Account\Plan\Plan;
use SimplyTestable\ApiBundle\Entity\UserPostActivationProperties;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class UserCreationController extends Controller
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
        $userAccountPlanService = $this->container->get('simplytestable.services.useraccountplanservice');
        $userPostActivationPropertiesService = $this->container->get(
            'simplytestable.services.job.userpostactivationpropertiesservice'
        );
        $accountPlanRepository = $this->container->get('simplytestable.repository.accountplan');

        if ($applicationStateService->isInReadOnlyMode()) {
            throw new ServiceUnavailableHttpException();
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
                ], UrlGeneratorInterface::ABSOLUTE_URL));
            }

            $user->setPlainPassword($password);
            $userService->updatePassword($user);
        }

        $coupon = rawurldecode(trim($requestData->get('coupon')));
        if (empty($coupon)) {
            $coupon = null;
        }

        $planName = rawurldecode(trim($requestData->get('plan')));

        /* @var Plan $plan */
        $plan = $accountPlanRepository->findOneBy([
            'name' => $planName,
        ]);

        if (empty($plan)) {
            $plan = $accountPlanRepository->findOneBy([
                'name' => self::DEFAULT_ACCOUNT_PLAN_NAME,
            ]);
        }

        if ($plan->getIsPremium()) {
            $userPostActivationPropertiesService->create(
                $user,
                $plan,
                $coupon
            );
        } else {
            $userAccountPlanService->subscribe($user, $plan);
        }

        return new Response();
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

        if ($applicationStateService->isInReadOnlyMode()) {
            throw new ServiceUnavailableHttpException();
        }

        $token = trim($token);
        if (empty($token)) {
            throw new BadRequestHttpException();
        }

        $user = $userService->findUserByConfirmationToken($token);
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
