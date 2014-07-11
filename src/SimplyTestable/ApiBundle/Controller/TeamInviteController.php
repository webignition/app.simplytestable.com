<?php

namespace SimplyTestable\ApiBundle\Controller;

use SimplyTestable\ApiBundle\Exception\Services\TeamInvite\Exception as TeamInviteServiceException;

class TeamInviteController extends ApiController {

    public function getAction($invitee_email) {
        if (!$this->getUserService()->exists($invitee_email)) {
            return $this->sendFailureResponse([
                'X-TeamInviteGet-Error-Code' => 9,
                'X-TeamInviteGet-Error-Message' => 'Invitee is not a user',
            ]);
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

    /**
     * @return \SimplyTestable\ApiBundle\Services\Team\InviteService
     */
    private function getTeamInviteService() {
        return $this->container->get('simplytestable.services.teaminviteservice');
    }

}
