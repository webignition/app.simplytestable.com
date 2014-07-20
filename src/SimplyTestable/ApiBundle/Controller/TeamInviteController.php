<?php

namespace SimplyTestable\ApiBundle\Controller;

use SimplyTestable\ApiBundle\Exception\Services\TeamInvite\Exception as TeamInviteServiceException;
use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Entity\Account\Plan\Plan;

class TeamInviteController extends ApiController {

    const DEFAULT_ACCOUNT_PLAN_NAME = 'basic';

    public function getAction($invitee_email) {
        if (!$this->getUserService()->exists($invitee_email)) {
            $user = $this->getUserService()->create($invitee_email, md5(rand()));

            if ($user instanceof User) {
                $this->getUserAccountPlanService()->subscribe($user, $this->getNewUserPlan());
            }
        }

        $invitee = $this->getUserService()->findUserBy([
            'email' => $invitee_email
        ]);

        if ($this->getUserService()->isSpecialUser($invitee)) {
            return $this->sendFailureResponse([
                'X-TeamInviteGet-Error-Code' => 10,
                'X-TeamInviteGet-Error-Message' => 'Special users cannot be invited',
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


    public function acceptAction() {
        if (!$this->getTeamInviteService()->hasForToken($this->getRequestToken())) {
            return $this->sendFailureResponse([
                'X-TeamInviteAccept-Error-Code' => 2,
                'X-TeamInviteAccept-Error-Message' => 'Invalid token',
            ]);
        }

        $invite = $this->getTeamInviteService()->getForToken($this->getRequestToken());

        $this->getTeamService()->getMemberService()->add($invite->getTeam(), $invite->getUser());

        $invites = $this->getTeamInviteService()->getForUser($this->getUser());

        foreach ($invites as $invite) {
            $this->getTeamInviteService()->remove($invite);
        }

        return $this->sendResponse();
    }


    public function listAction() {
        if (!$this->getTeamService()->hasTeam($this->getUser())) {
            return $this->sendFailureResponse([
                'X-TeamInviteList-Error-Code' => 1,
                'X-TeamInviteList-Error-Message' => 'User is not a team leader',
            ]);
        }

        return $this->sendResponse($this->getTeamInviteService()->getForTeam($this->getTeamService()->getForUser($this->getUser())));
    }


    public function userListAction() {
        return $this->sendResponse($this->getTeamInviteService()->getForUser($this->getUser()));
    }


    public function removeAction($invitee_email) {
        if (!$this->getTeamService()->hasTeam($this->getUser())) {
            return $this->sendFailureResponse([
                'X-TeamInviteRemove-Error-Code' => 1,
                'X-TeamInviteRemove-Error-Message' => 'User is not a team leader',
            ]);
        }


        if (!$this->getUserService()->exists($invitee_email)) {
            return $this->sendFailureResponse([
                'X-TeamInviteRemove-Error-Code' => 2,
                'X-TeamInviteRemove-Error-Message' => 'Invitee is not a user',
            ]);
        }

        $team = $this->getTeamService()->getForUser($this->getUser());

        $invitee = $this->getUserService()->findUserBy([
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
     * @return string
     */
    private function getRequestToken() {
        return trim($this->getRequest()->request->get('token'));
    }


    /**
     * @return \SimplyTestable\ApiBundle\Services\Team\Service
     */
    private function getTeamService() {
        return $this->container->get('simplytestable.services.teamservice');
    }


    /**
     * @return \SimplyTestable\ApiBundle\Services\Team\InviteService
     */
    private function getTeamInviteService() {
        return $this->container->get('simplytestable.services.teaminviteservice');
    }


    /**
     *
     * @return \SimplyTestable\ApiBundle\Services\UserAccountPlanService
     */
    private function getUserAccountPlanService() {
        return $this->get('simplytestable.services.useraccountplanservice');
    }


    /**
     *
     * @return \SimplyTestable\ApiBundle\Services\AccountPlanService
     */
    private function getAccountPlanService() {
        return $this->get('simplytestable.services.accountplanservice');
    }


    /**
     *
     * @return Plan
     */
    private function getNewUserPlan() {
        $planName = $this->getArguments('createAction')->get('plan');
        if (is_null($planName) || !$this->getAccountPlanService()->has($planName)) {
            $planName = self::DEFAULT_ACCOUNT_PLAN_NAME;
        }

        return $this->getAccountPlanService()->find($planName);
    }

}
