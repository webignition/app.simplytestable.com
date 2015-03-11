<?php

namespace SimplyTestable\ApiBundle\Controller;

use SimplyTestable\ApiBundle\Exception\Services\Team\Exception as TeamServiceException;
use Symfony\Component\HttpFoundation\Request;

class TeamController extends ApiController {

    /**
     * @var Request
     */
    private $request;

    public function getAction() {
        if (!$this->getTeamService()->hasForUser($this->getUser())) {
            return $this->sendNotFoundResponse();
        }

        $team = $this->getTeamService()->getForUser($this->getUser());
        $members = $this->getTeamService()->getMemberService()->getMembers($team);

        $serializedMembers = [];

        foreach ($members as $member) {
            $serializedMembers[] = $member->getUser()->getUsername();
        }

        return $this->sendResponse([
            'team' => $team,
            'members' => $serializedMembers
        ]);
    }
    
    public function createAction(Request $request) {
        $this->request = $request;

        if ($this->getUserService()->isSpecialUser($this->getUser())) {
            return $this->sendFailureResponse([
                'X-TeamCreate-Error-Code' => 9,
                'X-TeamCreate-Error-Message' => 'Special users cannot create teams',
            ]);
        }

        try {
            $this->getTeamService()->create($this->request->request->get('name'), $this->getUser());

            $invites = $this->getTeamInviteService()->getForUser($this->getUser());
            foreach ($invites as $invite) {
                $this->getTeamInviteService()->remove($invite);
            }

        } catch (TeamServiceException $teamServiceException) {

            if ($teamServiceException->isUserAlreadyLeadsTeamException() || $teamServiceException->isUserAlreadyOnTeamException()) {
                return $this->getTeamGetRedirectResponse();
            }

            return $this->sendFailureResponse([
                'X-TeamCreate-Error-Code' => $teamServiceException->getCode(),
                'X-TeamCreate-Error-Message' => $teamServiceException->getMessage(),
            ]);
        }

        return $this->getTeamGetRedirectResponse();
    }


    public function removeAction($member_email) {
        if (!$this->getUserService()->exists($member_email)) {
            return $this->sendFailureResponse([
                'X-TeamRemove-Error-Code' => 9,
                'X-TeamRemove-Error-Message' => 'Member is not a user',
            ]);
        }

        $member = $this->getUserService()->findUserBy([
            'email' => $member_email
        ]);

        try {
            $this->getTeamService()->remove($this->getUser(), $member);
            return $this->sendResponse();
        } catch (TeamServiceException $teamServiceException) {
            return $this->sendFailureResponse([
                'X-TeamRemove-Error-Code' => $teamServiceException->getCode(),
                'X-TeamRemove-Error-Message' => $teamServiceException->getMessage(),
            ]);
        }
    }


    public function leaveAction() {
        if ($this->getTeamService()->hasTeam($this->getUser())) {
            return $this->sendFailureResponse([
                'X-TeamLeave-Error-Code' => 9,
                'X-TeamLeave-Error-Message' => 'Leader cannot leave team',
            ]);
        }

        $this->getTeamService()->getMemberService()->remove($this->getUser());
        return $this->sendResponse();
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
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    private function getTeamGetRedirectResponse() {
        $response = $this->redirect($this->generateUrl('team_get'));

        $team = $this->getTeamService()->getForUser($this->getUser());

        $response->headers->set('X-Team-Name', $team->getName());
        $response->headers->set('X-Team-ID', $team->getId());

        return $response;
    }

}
