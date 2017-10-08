<?php

namespace SimplyTestable\ApiBundle\Controller;

use FOS\UserBundle\Util\UserManipulator;
use SimplyTestable\ApiBundle\Exception\Services\TeamInvite\Exception as TeamInviteServiceException;
use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Entity\Account\Plan\Plan;
use SimplyTestable\ApiBundle\Entity\Team\Team;
use SimplyTestable\ApiBundle\Services\AccountPlanService;
use SimplyTestable\ApiBundle\Services\Team\InviteService;
use SimplyTestable\ApiBundle\Services\Team\Service as TeamService;
use SimplyTestable\ApiBundle\Services\UserAccountPlanService;
use Symfony\Component\HttpFoundation\Request;
use SimplyTestable\ApiBundle\Services\ScheduledJob\Service as ScheduledJobService;
use SimplyTestable\ApiBundle\Services\Job\ConfigurationService as JobConfigurationService;
use Symfony\Component\HttpFoundation\Response;

class TeamInviteController extends ApiController
{
    const DEFAULT_ACCOUNT_PLAN_NAME = 'basic';

    /**
     * @var Request
     */
    private $request;

    /**
     * @param $invitee_email
     *
     * @return Response
     */
    public function getAction($invitee_email)
    {
        $userService = $this->container->get('simplytestable.services.userservice');

        if (!$userService->exists($invitee_email)) {
            $user = $userService->create($invitee_email, md5(rand()));

            if ($user instanceof User) {
                $this->getUserAccountPlanService()->subscribe($user, $this->getNewUserPlan());
            }
        }

        $invitee = $userService->findUserBy([
            'email' => $invitee_email
        ]);

        if ($userService->isSpecialUser($invitee)) {
            return $this->sendFailureResponse([
                'X-TeamInviteGet-Error-Code' => 10,
                'X-TeamInviteGet-Error-Message' => 'Special users cannot be invited',
            ]);
        }

        if ($this->getUserAccountPlanService()->getForUser($invitee)->getPlan()->getIsPremium()) {
            return $this->sendFailureResponse([
                'X-TeamInviteGet-Error-Code' => 11,
                'X-TeamInviteGet-Error-Message' => 'Invitee has a premium plan',
            ]);
        }

        try {
            return $this->sendResponse($this->getTeamInviteService()->get($this->getUser(), $invitee));
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
        $this->request = $request;

        /* @var $team Team */
        $team = $this->getTeamService()->getEntityRepository()->findOneBy([
            'name' => $this->getRequestTeam()
        ]);

        if (is_null($team)) {
            return $this->sendFailureResponse([
                'X-TeamInviteAccept-Error-Code' => 1,
                'X-TeamInviteAccept-Error-Message' => 'Invalid team',
            ]);
        }

        if (!$this->getTeamInviteService()->hasForTeamAndUser($team, $this->getUser())) {
            return $this->sendFailureResponse([
                'X-TeamInviteAccept-Error-Code' => 2,
                'X-TeamInviteAccept-Error-Message' => 'User has not been invited to join this team',
            ]);
        }

        if ($this->getUserAccountPlanService()->getForUser($this->getUser())->getPlan()->getIsPremium()) {
            return $this->sendResponse();
        }

        $this->getScheduledJobService()->setUser($this->getUser());
        $this->getScheduledJobService()->removeAll();

        $this->getJobConfigurationService()->setUser($this->getUser());
        $this->getJobConfigurationService()->removeAll();

        $invite = $this->getTeamInviteService()->getForTeamAndUser($team, $this->getUser());

        $this->getTeamService()->getMemberService()->add($invite->getTeam(), $invite->getUser());

        $invites = $this->getTeamInviteService()->getForUser($this->getUser());

        foreach ($invites as $invite) {
            $this->getTeamInviteService()->remove($invite);
        }

        return $this->sendResponse();
    }

    /**
     * @return Response
     */
    public function listAction()
    {
        if (!$this->getTeamService()->hasTeam($this->getUser())) {
            return $this->sendFailureResponse([
                'X-TeamInviteList-Error-Code' => 1,
                'X-TeamInviteList-Error-Message' => 'User is not a team leader',
            ]);
        }

        $allInvites = $this->getTeamInviteService()->getForTeam($this->getTeamService()->getForUser($this->getUser()));
        $invites = [];

        foreach ($allInvites as $invite) {
            $userAccountPlan = $this->getUserAccountPlanService()->getForUser($invite->getUser());

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
        if ($this->getUserAccountPlanService()->getForUser($this->getUser())->getPlan()->getIsPremium()) {
            return $this->sendResponse([]);
        }

        return $this->sendResponse($this->getTeamInviteService()->getForUser($this->getUser()));
    }

    /**
     * @param string $invitee_email
     *
     * @return Response
     */
    public function removeAction($invitee_email)
    {
        $userService = $this->container->get('simplytestable.services.userservice');

        if (!$this->getTeamService()->hasTeam($this->getUser())) {
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

        $team = $this->getTeamService()->getForUser($this->getUser());

        $invitee = $userService->findUserBy([
            'email' => $invitee_email
        ]);

        if (!$this->getTeamInviteService()->hasForTeamAndUser($team, $invitee)) {
            return $this->sendFailureResponse([
                'X-TeamInviteRemove-Error-Code' => 3,
                'X-TeamInviteRemove-Error-Message' => 'Invitee does not have an invite for this team',
            ]);
        }

        $this->getTeamInviteService()->remove($this->getTeamInviteService()->getForTeamAndUser($team, $invitee));

        return $this->sendResponse();
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    public function declineAction(Request $request)
    {
        $this->request = $request;

        $team = $this->getTeamService()->getEntityRepository()->findOneBy([
            'name' => trim($this->request->request->get('team'))
        ]);

        if ($team instanceof Team && $this->getTeamInviteService()->hasForTeamAndUser($team, $this->getUser())) {
            $invite = $this->getTeamInviteService()->getForTeamAndUser($team, $this->getUser());
            $this->getTeamInviteService()->remove($invite);
        }

        return $this->sendResponse();
    }

    /**
     * @param $token
     *
     * @return Response
     */
    public function getByTokenAction($token)
    {
        if (!$this->getTeamInviteService()->hasForToken(trim($token))) {
            return $this->sendNotFoundResponse();
        }

        return $this->sendResponse($this->getTeamInviteService()->getForToken($token));
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    public function activateAndAcceptAction(Request $request)
    {
        $userService = $this->container->get('simplytestable.services.userservice');

        $this->request = $request;

        $token = trim($this->request->request->get('token'));

        if (!$this->getTeamInviteService()->hasForToken($token)) {
            return $this->sendFailureResponse([
                'X-TeamInviteActivateAndAccept-Error-Code' => 1,
                'X-TeamInviteActivateAndAccept-Error-Message' => 'No invite for token',
            ]);
        }

        $invite = $this->getTeamInviteService()->getForToken($token);

        $this->getUserManipulator()->activate($invite->getUser()->getUsername());

        $invite->getUser()->setPlainPassword(rawurldecode(trim($this->request->request->get('password'))));
        $userService->updateUser($invite->getUser());

        $this->getTeamService()->getMemberService()->add($invite->getTeam(), $invite->getUser());

        $invites = $this->getTeamInviteService()->getForUser($invite->getUser());

        foreach ($invites as $invite) {
            $this->getTeamInviteService()->remove($invite);
        }

        return $this->sendResponse();
    }

    /**
     * @return string
     */
    private function getRequestTeam()
    {
        return trim($this->request->request->get('team'));
    }

    /**
     * @return TeamService
     */
    private function getTeamService()
    {
        return $this->container->get('simplytestable.services.teamservice');
    }

    /**
     * @return InviteService
     */
    private function getTeamInviteService()
    {
        return $this->container->get('simplytestable.services.teaminviteservice');
    }

    /**
     * @return UserAccountPlanService
     */
    private function getUserAccountPlanService()
    {
        return $this->get('simplytestable.services.useraccountplanservice');
    }

    /**
     * @return AccountPlanService
     */
    private function getAccountPlanService()
    {
        return $this->get('simplytestable.services.accountplanservice');
    }

    /**
     * @return Plan
     */
    private function getNewUserPlan()
    {
        $planName = $this->getArguments('createAction')->get('plan');
        if (is_null($planName) || !$this->getAccountPlanService()->has($planName)) {
            $planName = self::DEFAULT_ACCOUNT_PLAN_NAME;
        }

        return $this->getAccountPlanService()->find($planName);
    }

    /**
     * @return UserManipulator
     */
    protected function getUserManipulator()
    {
        return $this->get('fos_user.util.user_manipulator');
    }

    /**
     * @return ScheduledJobService
     */
    private function getScheduledJobService()
    {
        return $this->get('simplytestable.services.scheduledjob.service');
    }

    /**
     * @return JobConfigurationService
     */
    private function getJobConfigurationService()
    {
        return $this->get('simplytestable.services.job.configurationservice');
    }
}
