<?php

namespace SimplyTestable\ApiBundle\Controller;

use SimplyTestable\ApiBundle\Exception\Services\TeamInvite\Exception as TeamInviteServiceException;
use SimplyTestable\ApiBundle\Entity\Team\Team;
use SimplyTestable\ApiBundle\Services\AccountPlanService;
use SimplyTestable\ApiBundle\Services\Team\MemberService;
use SimplyTestable\ApiBundle\Services\UserAccountPlanService;
use SimplyTestable\ApiBundle\Services\UserService;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class TeamInviteController extends Controller
{
    const DEFAULT_ACCOUNT_PLAN_NAME = 'basic';

    /**
     * @param Request $request
     * @param string $invitee_email
     *
     * @return JsonResponse|Response
     */
    public function getAction(Request $request, $invitee_email)
    {
        $userService = $this->container->get(UserService::class);
        $userAccountPlanService = $this->container->get(UserAccountPlanService::class);
        $teamInviteService = $this->container->get('simplytestable.services.teaminviteservice');
        $teamService = $this->container->get('simplytestable.services.teamservice');
        $accountPlanService = $this->container->get(AccountPlanService::class);

        $inviter = $this->getUser();

        if (!$teamService->hasTeam($inviter)) {
            return Response::create('', 400, [
                'X-TeamInviteGet-Error-Code' => TeamInviteServiceException::INVITER_IS_NOT_A_LEADER,
                'X-TeamInviteGet-Error-Message' => 'Inviter is not a team leader',
            ]);
        }

        if (!$userService->exists($invitee_email)) {
            $user = $userService->create($invitee_email, md5(rand()));

            $requestData = $request->query;

            $planName = rawurldecode(trim($requestData->get('plan')));
            $plan = $accountPlanService->get($planName);

            if (empty($plan)) {
                $plan = $accountPlanService->get(self::DEFAULT_ACCOUNT_PLAN_NAME);
            }

            $userAccountPlanService->subscribe($user, $plan);
        }

        $invitee = $userService->findUserByEmail($invitee_email);

        if ($userService->isSpecialUser($invitee)) {
            return Response::create('', 400, [
                'X-TeamInviteGet-Error-Code' => 10,
                'X-TeamInviteGet-Error-Message' => 'Special users cannot be invited',
            ]);
        }

        if ($userAccountPlanService->getForUser($invitee)->getPlan()->getIsPremium()) {
            return Response::create('', 400, [
                'X-TeamInviteGet-Error-Code' => 11,
                'X-TeamInviteGet-Error-Message' => 'Invitee has a premium plan',
            ]);
        }

        try {
            return new JsonResponse($teamInviteService->get($inviter, $invitee));
        } catch (TeamInviteServiceException $teamInviteServiceException) {
            return Response::create('', 400, [
                'X-TeamInviteGet-Error-Code' => $teamInviteServiceException->getCode(),
                'X-TeamInviteGet-Error-Message' => $teamInviteServiceException->getMessage(),
            ]);
        }
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    public function acceptAction(Request $request)
    {
        $userAccountPlanService = $this->container->get(UserAccountPlanService::class);
        $teamInviteService = $this->container->get('simplytestable.services.teaminviteservice');
        $scheduledJobService = $this->container->get('simplytestable.services.scheduledjob.service');
        $jobConfigurationService = $this->get('simplytestable.services.job.configurationservice');
        $teamMemberService = $this->container->get(MemberService::class);
        $entityManager = $this->container->get('doctrine.orm.entity_manager');

        $teamRepository = $entityManager->getRepository(Team::class);

        $requestData = $request->request;
        $requestTeam = $requestData->get('team');

        /* @var $team Team */
        $team = $teamRepository->findOneBy([
            'name' => $requestTeam,
        ]);

        if (is_null($team)) {
            return Response::create('', 400, [
                'X-TeamInviteAccept-Error-Code' => 1,
                'X-TeamInviteAccept-Error-Message' => 'Invalid team',
            ]);
        }

        $invite = $teamInviteService->getForTeamAndUser($team, $this->getUser());

        if (empty($invite)) {
            return Response::create('', 400, [
                'X-TeamInviteAccept-Error-Code' => 2,
                'X-TeamInviteAccept-Error-Message' => 'User has not been invited to join this team',
            ]);
        }

        if ($userAccountPlanService->getForUser($this->getUser())->getPlan()->getIsPremium()) {
            return new Response();
        }

        $scheduledJobService->removeAll();

        $jobConfigurationService->removeAll();

        $teamMemberService->add($invite->getTeam(), $invite->getUser());

        $invites = $teamInviteService->getForUser($this->getUser());

        foreach ($invites as $invite) {
            $entityManager->remove($invite);
            $entityManager->flush();
        }

        return new Response();
    }

    /**
     * @return JsonResponse|Response
     */
    public function listAction()
    {
        $userAccountPlanService = $this->container->get(UserAccountPlanService::class);
        $teamInviteService = $this->container->get('simplytestable.services.teaminviteservice');
        $teamService = $this->container->get('simplytestable.services.teamservice');

        if (!$teamService->hasTeam($this->getUser())) {
            return Response::create('', 400, [
                'X-TeamInviteList-Error-Code' => 1,
                'X-TeamInviteList-Error-Message' => 'User is not a team leader',
            ]);
        }

        $allInvites = $teamInviteService->getForTeam($teamService->getForUser($this->getUser()));
        $invites = [];

        foreach ($allInvites as $invite) {
            $userAccountPlan = $userAccountPlanService->getForUser($invite->getUser());

            if (!$userAccountPlan->getPlan()->getIsPremium()) {
                $invites[] = $invite;
            }
        }

        return new JsonResponse($invites);
    }

    /**
     * @return Response
     */
    public function userListAction()
    {
        $userAccountPlanService = $this->container->get(UserAccountPlanService::class);
        $teamInviteService = $this->container->get('simplytestable.services.teaminviteservice');

        if ($userAccountPlanService->getForUser($this->getUser())->getPlan()->getIsPremium()) {
            return new JsonResponse([]);
        }

        return new JsonResponse($teamInviteService->getForUser($this->getUser()));
    }

    /**
     * @param string $invitee_email
     *
     * @return Response
     */
    public function removeAction($invitee_email)
    {
        $userService = $this->container->get(UserService::class);
        $teamInviteService = $this->container->get('simplytestable.services.teaminviteservice');
        $teamService = $this->container->get('simplytestable.services.teamservice');
        $entityManager = $this->container->get('doctrine.orm.entity_manager');

        if (!$teamService->hasTeam($this->getUser())) {
            return Response::create('', 400, [
                'X-TeamInviteRemove-Error-Code' => 1,
                'X-TeamInviteRemove-Error-Message' => 'User is not a team leader',
            ]);
        }

        if (!$userService->exists($invitee_email)) {
            return Response::create('', 400, [
                'X-TeamInviteRemove-Error-Code' => 2,
                'X-TeamInviteRemove-Error-Message' => 'Invitee is not a user',
            ]);
        }

        $team = $teamService->getForUser($this->getUser());
        $invitee = $userService->findUserByEmail($invitee_email);
        $invite = $teamInviteService->getForTeamAndUser($team, $invitee);

        if (empty($invite)) {
            return Response::create('', 400, [
                'X-TeamInviteRemove-Error-Code' => 3,
                'X-TeamInviteRemove-Error-Message' => 'Invitee does not have an invite for this team',
            ]);
        }

        $entityManager->remove($invite);
        $entityManager->flush();

        return new Response();
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    public function declineAction(Request $request)
    {
        $teamInviteService = $this->container->get('simplytestable.services.teaminviteservice');
        $entityManager = $this->container->get('doctrine.orm.entity_manager');

        $teamRepository = $entityManager->getRepository(Team::class);

        $requestData = $request->request;
        $requestTeam = $requestData->get('team');

        $team = $teamRepository->findOneBy([
            'name' => $requestTeam,
        ]);

        if ($team instanceof Team) {
            $invite = $teamInviteService->getForTeamAndUser($team, $this->getUser());

            if (!empty($invite)) {
                $entityManager->remove($invite);
                $entityManager->flush();
            }
        }

        return new Response();
    }

    /**
     * @param $token
     *
     * @return Response
     */
    public function getByTokenAction($token)
    {
        $teamInviteService = $this->container->get('simplytestable.services.teaminviteservice');

        $invite = $teamInviteService->getForToken($token);

        if (empty($invite)) {
            throw new NotFoundHttpException();
        }

        return new JsonResponse($invite);
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    public function activateAndAcceptAction(Request $request)
    {
        $teamInviteService = $this->container->get('simplytestable.services.teaminviteservice');
        $teamMemberService = $this->container->get(MemberService::class);
        $userManipulator = $this->container->get('fos_user.util.user_manipulator');
        $userService = $this->container->get(UserService::class);
        $entityManager = $this->container->get('doctrine.orm.entity_manager');

        $requestData = $request->request;
        $token = trim($requestData->get('token'));

        $invite = $teamInviteService->getForToken($token);

        if (empty($invite)) {
            return Response::create('', 400, [
                'X-TeamInviteActivateAndAccept-Error-Code' => 1,
                'X-TeamInviteActivateAndAccept-Error-Message' => 'No invite for token',
            ]);
        }

        $invitee = $invite->getUser();
        $team = $invite->getTeam();

        $userManipulator->activate($invitee->getUsername());

        $password = rawurldecode(trim($requestData->get('password')));

        $invitee->setPlainPassword($password);
        $userService->updateUser($invitee);

        $teamMemberService->add($team, $invitee);

        $invites = $teamInviteService->getForUser($invitee);

        foreach ($invites as $invite) {
            $entityManager->remove($invite);
            $entityManager->flush();
        }

        return new Response();
    }
}
