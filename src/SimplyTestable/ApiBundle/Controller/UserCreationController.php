<?php

namespace SimplyTestable\ApiBundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use FOS\UserBundle\Util\UserManipulator;
use SimplyTestable\ApiBundle\Entity\UserPostActivationProperties;
use SimplyTestable\ApiBundle\Services\AccountPlanService;
use SimplyTestable\ApiBundle\Services\ApplicationStateService;
use SimplyTestable\ApiBundle\Services\UserAccountPlanService;
use SimplyTestable\ApiBundle\Services\UserPostActivationPropertiesService;
use SimplyTestable\ApiBundle\Services\UserService;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class UserCreationController
{
    const DEFAULT_ACCOUNT_PLAN_NAME = 'basic';

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var ApplicationStateService
     */
    private $applicationStateService;

    /**
     * @var UserService
     */
    private $userService;

    /**
     * @var UserAccountPlanService
     */
    private $userAccountPlanService;

    /**
     * @var UserPostActivationPropertiesService
     */
    private $userPostActivationPropertiesService;

    /**
     * @var AccountPlanService
     */
    private $accountPlanService;

    /**
     * @var EntityManagerInterface $entityManager
     */
    private $entityManager;

    /**
     * @var UserManipulator
     */
    private $userManipulator;

    /**
     * @param RouterInterface $router
     * @param ApplicationStateService $applicationStateService
     * @param UserService $userService
     * @param UserAccountPlanService $userAccountPlanService
     * @param UserPostActivationPropertiesService $userPostActivationPropertiesService
     * @param AccountPlanService $accountPlanService
     * @param EntityManagerInterface $entityManager
     * @param UserManipulator $userManipulator
     */
    public function __construct(
        RouterInterface $router,
        ApplicationStateService $applicationStateService,
        UserService $userService,
        UserAccountPlanService $userAccountPlanService,
        UserPostActivationPropertiesService $userPostActivationPropertiesService,
        AccountPlanService $accountPlanService,
        EntityManagerInterface $entityManager,
        UserManipulator $userManipulator
    ) {
        $this->router = $router;
        $this->applicationStateService = $applicationStateService;
        $this->userService = $userService;
        $this->userAccountPlanService = $userAccountPlanService;
        $this->userPostActivationPropertiesService = $userPostActivationPropertiesService;
        $this->accountPlanService = $accountPlanService;
        $this->entityManager = $entityManager;
        $this->userManipulator = $userManipulator;
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    public function createAction(Request $request)
    {
        if ($this->applicationStateService->isInReadOnlyMode()) {
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

        $user = $this->userService->findUserByEmail($email);

        if (empty($user)) {
            $user = $this->userService->create($email, $password);
        } else {
            if ($user->isEnabled()) {
                return $this->redirect(
                    'user_get',
                    [
                        'email_canonical' => $email
                    ]
                );
            }

            $user->setPlainPassword($password);
            $this->userService->updatePassword($user);
        }

        $coupon = rawurldecode(trim($requestData->get('coupon')));
        if (empty($coupon)) {
            $coupon = null;
        }

        $planName = rawurldecode(trim($requestData->get('plan')));
        $plan = $this->accountPlanService->get($planName);

        if (empty($plan)) {
            $plan = $this->accountPlanService->get(self::DEFAULT_ACCOUNT_PLAN_NAME);
        }

        if ($plan->getIsPremium()) {
            $this->userPostActivationPropertiesService->create(
                $user,
                $plan,
                $coupon
            );
        } else {
            $this->userAccountPlanService->subscribe($user, $plan);
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
        if ($this->applicationStateService->isInReadOnlyMode()) {
            throw new ServiceUnavailableHttpException();
        }

        $token = trim($token);
        if (empty($token)) {
            throw new BadRequestHttpException();
        }

        $user = $this->userService->findUserByConfirmationToken($token);
        if (empty($user)) {
            throw new BadRequestHttpException();
        }

        $this->userManipulator->activate($user->getUsername());

        $userPostActivationPropertiesRepository = $this->entityManager->getRepository(
            UserPostActivationProperties::class
        );

        $postActivationProperties = $userPostActivationPropertiesRepository->findOneBy([
            'user' => $user,
        ]);

        if (!empty($postActivationProperties)) {
            $this->userAccountPlanService->subscribe(
                $user,
                $postActivationProperties->getAccountPlan(),
                $postActivationProperties->getCoupon()
            );

            $this->entityManager->remove($postActivationProperties);
            $this->entityManager->flush();
        }

        return new Response();
    }

    /**
     * @param string  $routeName
     * @param array $routeParameters
     *
     * @return RedirectResponse
     */
    private function redirect($routeName, $routeParameters = [])
    {
        $url = $this->router->generate(
            $routeName,
            $routeParameters,
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        return new RedirectResponse($url);
    }
}
