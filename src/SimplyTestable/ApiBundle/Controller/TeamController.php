<?php

namespace SimplyTestable\ApiBundle\Controller;

use SimplyTestable\ApiBundle\Exception\Services\Team\Exception as TeamServiceException;

class TeamController extends ApiController {

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
    
    public function createAction() {
        try {
            $this->getTeamService()->create($this->getRequest()->request->get('name'), $this->getUser());
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


    /**
     * @return \SimplyTestable\ApiBundle\Services\Team\Service
     */
    private function getTeamService() {
        return $this->container->get('simplytestable.services.teamservice');
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
