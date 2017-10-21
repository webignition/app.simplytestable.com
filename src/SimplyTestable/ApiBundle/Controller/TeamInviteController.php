<?php

namespace SimplyTestable\ApiBundle\Controller;

use SimplyTestable\ApiBundle\Exception\Services\TeamInvite\Exception as TeamInviteServiceException;
use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Entity\Team\Team;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class TeamInviteController extends ApiController
{
    const DEFAULT_ACCOUNT_PLAN_NAME = 'basic';

    /**
     * @param Request $request
     * @param string $invitee_email
     *
     * @return Response
     */
    public function getAction(Request $request, $invitee_email)
    {
        $userService = $this->container->get('simplytestable.services.userservice');
        $userAccountPlanService = $this->container->get('simplytestable.services.useraccountplanservice');
        $teamInviteService = $this->container->get('simplytestable.services.teaminviteservice');
        $accountPlanService = $this->container->get('simplytestable.services.accountplanservice');
        $teamService = $this->container->get('simplytestable.services.teamservice');

        $inviter = $this->getUser();

        if (!$teamService->hasTeam($inviter)) {
            return $this->sendFailureResponse([
                'X-TeamInviteGet-Error-Code' => TeamInviteServiceException::INVITER_IS_NOT_A_LEADER,
                'X-TeamInviteGet-Error-Message' => 'Inviter is not a team leader',
            ]);
        }

        if (!$userService->exists($invitee_email)) {
            $user = $userService->create($invitee_email, md5(rand()));

            $requestData = $request->query;

            $planName = rawurldecode(trim($requestData->get('plan')));
            if (empty($planName) || !$accountPlanService->has($planName)) {
                $planName = self::DEFAULT_ACCOUNT_PLAN_NAME;
            }

            $plan = $accountPlanService->find($planName);

            $userAccountPlanService->subscribe($user, $plan);
        }

        $invitee = $userService->findUserByEmail($invitee_email);

        if ($userService->isSpecialUser($invitee)) {
            return $this->sendFailureResponse([
                'X-TeamInviteGet-Error-Code' => 10,
                'X-TeamInviteGet-Error-Message' => 'Special users cannot be invited',
            ]);
        }

        if ($userAccountPlanService->getForUser($invitee)->getPlan()->getIsPremium()) {
            return $this->sendFailureResponse([
                'X-TeamInviteGet-Error-Code' => 11,
                'X-TeamInviteGet-Error-Message' => 'Invitee has a premium plan',
            ]);
        }

        try {
            return $this->sendResponse($teamInviteService->get($inviter, $invitee));
        } catch (TeamInviteServiceException $teamInviteServiceException) {
            return $this->sendFailureResponse([
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
        $userAccountPlanService = $this->container->get('simplytestable.services.useraccountplanservice');
        $teamInviteService = $this->container->get('simplytestable.services.teaminviteservice');
        $scheduledJobService = $this->container->get('simplytestable.services.scheduledjob.service');
        $jobConfigurationService = $this->get('simplytestable.services.job.configurationservice');
        $teamMemberService = $this->container->get('simplytestable.services.teammemberservice');
        $entityManager = $this->container->get('doctrine.orm.entity_manager');
        $teamRepository = $entityManager->getRepository(Team::class);

        $requestData = $request->request;
        $requestTeam = $requestData->get('team');

        /* @var $team Team */
        $team = $teamRepository->findOneBy([
            'name' => $requestTeam,
        ]);

        if (is_null($team)) {
            return $this->sendFailureResponse([
                'X-TeamInviteAccept-Error-Code' => 1,
                'X-TeamInviteAccept-Error-Message' => 'Invalid team',
            ]);
        }

        if (!$teamInviteService->hasForTeamAndUser($team, $this->getUser())) {
            return $this->sendFailureResponse([
                'X-TeamInviteAccept-Error-Code' => 2,
                'X-TeamInviteAccept-Error-Message' => 'User has not been invited to join this team',
            ]);
        }

        if ($userAccountPlanService->getForUser($this->getUser())->getPlan()->getIsPremium()) {
            return new Response();
        }

        $scheduledJobService->setUser($this->getUser());
        $scheduledJobService->removeAll();

        $jobConfigurationService->setUser($this->getUser());
        $jobConfigurationService->removeAll();

        $invite = $teamInviteService->getForTeamAndUser($team, $this->getUser());

        $teamMemberService->add($invite->getTeam(), $invite->getUser());

        $invites = $teamInviteService->getForUser($this->getUser());

        foreach ($invites as $invite) {
            $teamInviteService->remove($invite);
        }

        return new Response();
    }

    /**
     * @return Response
     */
    public function listAction()
    {
        $userAccountPlanService = $this->container->get('simplytestable.services.useraccountplanservice');
        $teamInviteService = $this->container->get('simplytestable.services.teaminviteservice');
        $teamService = $this->container->get('simplytestable.services.teamservice');

        if (!$teamService->hasTeam($this->getUser())) {
            return $this->sendFailureResponse([
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

        return $this->sendResponse($invites);
    }

    /**
     * @return Response
     */
    public function userListAction()
    {
        $userAccountPlanService = $this->container->get('simplytestable.services.useraccountplanservice');
        $teamInviteService = $this->container->get('simplytestable.services.teaminviteservice');

        if ($userAccountPlanService->getForUser($this->getUser())->getPlan()->getIsPremium()) {
            return new JsonResponse([]);
        }

        return $this->sendResponse($teamInviteService->getForUser($this->getUser()));
    }

    /**
     * @param string $invitee_email
     *
     * @return Response
     */
    public function removeAction($invitee_email)
    {
        $userService = $this->container->get('simplytestable.services.userservice');
        $teamInviteService = $this->container->get('simplytestable.services.teaminviteservice');
        $teamService = $this->container->get('simplytestable.services.teamservice');

        if (!$teamService->hasTeam($this->getUser())) {
            return $this->sendFailureResponse([
                'X-TeamInviteRemove-Error-Code' => 1,
                'X-TeamInviteRemove-Error-Message' => 'User is not a team leader',
            ]);
        }


        if (!$userService->exists($invitee_email)) {
            return $this->sendFailureResponse([
                'X-TeamInviteRemove-Error-Code' => 2,
                'X-TeamInviteRemove-Error-Message' => 'Invitee is not a user',
            ]);
        }

        $team = $teamService->getForUser($this->getUser());

        $invitee = $userService->findUserByEmail($invitee_email);

        if (!$teamInviteService->hasForTeamAndUser($team, $invitee)) {
            return $this->sendFailureResponse([
                'X-TeamInviteRemove-Error-Code' => 3,
                'X-TeamInviteRemove-Error-Message' => 'Invitee does not have an invite for this team',
            ]);
        }

        $teamInviteService->remove($teamInviteService->getForTeamAndUser($team, $invitee));

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

        if ($team instanceof Team && $teamInviteService->hasForTeamAndUser($team, $this->getUser())) {
            $invite = $teamInviteService->getForTeamAndUser($team, $this->getUser());
            $teamInviteService->remove($invite);
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

        if (!$teamInviteService->hasForToken(trim($token))) {
            throw new NotFoundHttpException();
        }

        return $this->sendResponse($teamInviteService->getForToken($token));
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    public function activateAndAcceptAction(Request $request)
    {
        $teamInviteService = $this->container->get('simplytestable.services.teaminviteservice');
        $teamMemberService = $this->container->get('simplytestable.services.teammemberservice');
        $userManipulator = $this->container->get('fos_user.util.user_manipulator');
        $userService = $this->container->get('simplytestable.services.userservice');

        $requestData = $request->request;
        $token = trim($requestData->get('token'));

        if (!$teamInviteService->hasForToken($token)) {
            return $this->sendFailureResponse([
                'X-TeamInviteActivateAndAccept-Error-Code' => 1,
                'X-TeamInviteActivateAndAccept-Error-Message' => 'No invite for token',
            ]);
        }

        $invite = $teamInviteService->getForToken($token);

        $userManipulator->activate($invite->getUser()->getUsername());

        $password = rawurldecode(trim($requestData->get('password')));

        $invite->getUser()->setPlainPassword($password);
        $userService->updateUser($invite->getUser());

        $teamMemberService->add($invite->getTeam(), $invite->getUser());

        $invites = $teamInviteService->getForUser($invite->getUser());

        foreach ($invites as $invite) {
            $teamInviteService->remove($invite);
        }

        return new Response();
    }
}
