<?php

namespace SimplyTestable\ApiBundle\Controller;

use SimplyTestable\ApiBundle\Entity\Account\Plan\Plan;
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
        $userAccountPlanService = $this->container->get('simplytestable.services.useraccountplanservice');
        $entityManager = $this->container->get('doctrine.orm.entity_manager');
        $user = $this->getUser();

        $userAccountPlan = $userAccountPlanService->getForUser($user);
        if (empty($userAccountPlan)) {
            $accountPlanRepository = $entityManager->getRepository(Plan::class);

            /* @var Plan $basicPlan */
            $basicPlan = $accountPlanRepository->findOneBy([
                'name' => 'basic',
            ]);

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
